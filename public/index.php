<?php
/**
 * Environmental Science LMS - Main Application Router
 * 
 * This file serves as the central entry point for the Learning Management System.
 * It handles routing, authentication, access logging, and page rendering.
 * 
 * @author Environmental Science LMS Team
 * @version 2.0
 */

// Define initialization constant
define('LMS_INIT', true);

// Start session management
session_start();

// Include core system files
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

/**
 * Enhanced error handling and logging
 */
function handleError($errno, $errstr, $errfile, $errline) {
    error_log("LMS Error: [$errno] $errstr in $errfile on line $errline");
    return false;
}
set_error_handler('handleError');

/**
 * Set flash message for user feedback
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Get current page from URL parameter
$page = $_GET['page'] ?? 'home';

// Record access for analytics (exclude certain pages and assets)
$excludeFromLogging = ['assets', 'qrcodes', 'logout', 'download'];
if (!in_array($page, $excludeFromLogging)) {
    record_access($page);
}

// Enhanced routing system with better organization
try {
    switch($page) {
        // Authentication routes
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = login($_POST['email'] ?? '', $_POST['password'] ?? '');
                if ($result['success']) {
                    setFlashMessage('success', $result['message']);
                    $redirect = $_SESSION['redirect_after_login'] ?? $result['redirect'];
                    unset($_SESSION['redirect_after_login']);
                    header("Location: $redirect");
                    exit;
                } else {
                    setFlashMessage('error', $result['message']);
                }
            }
            require __DIR__ . '/../templates/auth/login.php';
            break;
            
        case 'register':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = register($_POST);
                if ($result['success']) {
                    setFlashMessage('success', $result['message']);
                    header('Location: index.php?page=login');
                    exit;
                } else {
                    setFlashMessage('error', $result['message']);
                }
            }
            require __DIR__ . '/../templates/auth/register.php';
            break;
            
        case 'logout':
            logout();
            break;

        // Teacher routes (require teacher role)
        case 'teacher_dashboard':
            require_role('teacher');
            require __DIR__ . '/../templates/teacher/dashboard.php';
            break;
            
        case 'teacher_create':
            require_role('teacher');
            require __DIR__ . '/../templates/teacher/create_lesson.php';
            break;
            
        case 'teacher_edit':
            require_role('teacher');
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                setFlashMessage('error', 'Invalid lesson ID provided.');
                header('Location: index.php?page=teacher_dashboard');
                exit;
            }
            require __DIR__ . '/../templates/teacher/edit_lesson.php';
            break;
            
        case 'teacher_delete':
            require_role('teacher');
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                setFlashMessage('error', 'Invalid lesson ID provided.');
                header('Location: index.php?page=teacher_dashboard');
                exit;
            }
            require __DIR__ . '/../templates/teacher/delete_lesson.php';
            break;
            
        case 'teacher_submissions':
            require_role('teacher');
            require __DIR__ . '/../templates/teacher/view_submissions.php';
            break;

        case 'get_submission_activity':
            require_login();
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Invalid submission ID']);
                exit;
            }

            try {
                $stmt = $pdo->prepare('SELECT activity_id FROM submissions WHERE id = ?');
                $stmt->execute([$_GET['id']]);
                $submission = $stmt->fetch();

                header('Content-Type: application/json');
                if ($submission) {
                    echo json_encode(['activity_id' => $submission['activity_id']]);
                } else {
                    echo json_encode(['error' => 'Submission not found']);
                }
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Database error']);
            }
            exit;

        case 'generate_qr_codes':
            require_role('teacher');
            $me = user();

            $input = json_decode(file_get_contents('php://input'), true);
            $lesson_ids = $input['lesson_ids'] ?? [];

            if (empty($lesson_ids)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'No lesson IDs provided']);
                exit;
            }

            $generated = 0;
            $errors = [];
            $qr_codes = [];

            foreach ($lesson_ids as $lesson_id) {
                try {
                    // Verify ownership
                    $stmt = $pdo->prepare('SELECT id, title FROM lessons WHERE id = ? AND teacher_id = ?');
                    $stmt->execute([$lesson_id, $me['id']]);
                    $lesson = $stmt->fetch();
                    if (!$lesson) {
                        $errors[] = "Lesson $lesson_id not found or access denied";
                        continue;
                    }

                    // Generate QR code
                    $qrcodeDir = __DIR__.'/qrcodes/';
                    if(!is_dir($qrcodeDir)) mkdir($qrcodeDir, 0755, true);

                    $lessonUrl = 'http://'.$_SERVER['HTTP_HOST'].'/public/index.php?page=view_lesson&id='.$lesson_id;
                    $outfile = $qrcodeDir.'lesson_'.$lesson_id.'.png';
                    $qrUrl = 'http://'.$_SERVER['HTTP_HOST'].'/public/qrcodes/lesson_'.$lesson_id.'.png';

                    require_once __DIR__.'/../lib/phpqrcode/qrlib.php';

                    if (QRcode::png($lessonUrl, $outfile, 'L', 4, 2)) {
                        $generated++;
                        $qr_codes[] = [
                            'lesson_id' => $lesson_id,
                            'lesson_title' => $lesson['title'],
                            'qr_url' => $qrUrl,
                            'lesson_url' => $lessonUrl
                        ];
                    } else {
                        $errors[] = "Failed to generate QR code for lesson $lesson_id (no image data)";
                    }
                } catch (Exception $e) {
                    $errors[] = "Error processing lesson $lesson_id: " . $e->getMessage();
                }
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => $generated > 0,
                'generated' => $generated,
                'qr_codes' => $qr_codes,
                'errors' => $errors,
                'message' => $generated > 0 ? "Generated $generated QR codes" : 'No QR codes generated'
            ]);
            exit;

        // Student routes (require student role)
        case 'student_dashboard':
            require_role('student');
            require __DIR__ . '/../templates/student/dashboard.php';
            break;

        case 'student_grades':
            require_role('student');
            require __DIR__ . '/../templates/student/view_grades.php';
            break;

        // Profile route (accessible to all authenticated users)
        case 'profile':
            require_login();
            require __DIR__ . '/../templates/profile.php';
            break;

        // Admin routes (require admin role)
        case 'admin_dashboard':
            require_role('admin');
            require __DIR__ . '/../templates/admin/dashboard.php';
            break;

        // Content viewing routes (accessible to all authenticated users)
        case 'view_lesson':
            require_login();
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                setFlashMessage('error', 'Invalid lesson ID provided.');
                header('Location: index.php?page=home');
                exit;
            }
            require __DIR__ . '/lesson.php';
            break;
            
        case 'submit_activity':
            require_login();
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                setFlashMessage('error', 'Invalid activity ID provided.');
                header('Location: index.php?page=' . (is_logged_in() && user()['role'] === 'student' ? 'student_dashboard' : 'teacher_dashboard'));
                exit;
            }
            require __DIR__ . '/../templates/submit_activity.php';
            break;

        // Utility routes
        case 'download':
            require_login();
            if (!isset($_GET['file'])) {
                setFlashMessage('error', 'No file specified for download.');
                header('Location: index.php?page=home');
                exit;
            }
            require __DIR__ . '/../templates/download.php';
            break;

        // Home page (default)
        case 'home':
        default:
            // Redirect authenticated users to their appropriate dashboard
            if (is_logged_in()) {
                $user = user();
                switch($user['role']) {
                    case 'admin':
                        header('Location: index.php?page=admin_dashboard');
                        break;
                    case 'teacher':
                        header('Location: index.php?page=teacher_dashboard');
                        break;
                    case 'student':
                        header('Location: index.php?page=student_dashboard');
                        break;
                    default:
                        require __DIR__ . '/../templates/home.php';
                }
            } else {
                require __DIR__ . '/../templates/home.php';
            }
            break;
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("LMS Router Error: " . $e->getMessage());
    
    // Set error message for user
    setFlashMessage('error', 'An unexpected error occurred. Please try again.');
    
    // Redirect to appropriate page
    if (is_logged_in()) {
        $user = user();
        $redirectPage = match($user['role']) {
            'admin' => 'admin_dashboard',
            'teacher' => 'teacher_dashboard',
            'student' => 'student_dashboard',
            default => 'home'
        };
        header("Location: index.php?page=$redirectPage");
    } else {
        header('Location: index.php?page=home');
    }
    exit;
}

/**
 * Security headers for enhanced protection
 */
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Optional: Add Content Security Policy for enhanced security
// header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:;");
?>