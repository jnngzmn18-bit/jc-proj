<?php
/**
 * Environmental Science LMS - Authentication System
 * 
 * Handles user authentication, authorization, session management,
 * and security functions for the LMS system.
 */

// Prevent direct access
if (!defined('LMS_INIT')) {
    require_once __DIR__ . '/config.php';
}

require_once __DIR__ . '/db.php';

/**
 * Check if user is logged in
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

/**
 * Get current logged-in user
 * @return array|null
 */
function user() {
    return $_SESSION['user'] ?? null;
}

/**
 * Require user to be logged in
 * Redirects to login if not authenticated
 */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: index.php?page=login');
        exit;
    }
}

/**
 * Require specific role
 * @param string $required_role
 */
function require_role($required_role) {
    require_login();
    
    $user = user();
    if (!$user || $user['role'] !== $required_role) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Access denied. Insufficient permissions.'
        ];
        
        // Redirect based on user's actual role
        if ($user) {
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
                    header('Location: index.php?page=home');
            }
        } else {
            header('Location: index.php?page=login');
        }
        exit;
    }
}

/**
 * Check if user has permission for specific action
 * @param string $permission
 * @return bool
 */
function has_permission($permission) {
    $user = user();
    if (!$user) return false;
    
    $permissions = [
        'admin' => ['*'], // Admin has all permissions
        'teacher' => [
            'create_lesson', 'edit_lesson', 'delete_lesson', 'view_submissions',
            'grade_submissions', 'view_students', 'create_activity'
        ],
        'student' => [
            'view_lessons', 'submit_activity', 'view_grades', 'view_profile'
        ]
    ];
    
    $user_permissions = $permissions[$user['role']] ?? [];
    
    return in_array('*', $user_permissions) || in_array($permission, $user_permissions);
}

/**
 * Login user with credentials
 * @param string $email
 * @param string $password
 * @return array Result with success status and message
 */
function login($email, $password) {
    global $pdo;
    
    try {
        // Check for too many failed attempts
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE ip = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$ip, LOGIN_LOCKOUT_TIME]);
        $attempts = $stmt->fetch()['attempts'];
        
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            return [
                'success' => false,
                'message' => 'Too many failed login attempts. Please try again later.'
            ];
        }
        
        // Find user by email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            // Record failed attempt
            record_login_attempt($email, $ip, false);
            
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }
        
        // Successful login
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'created_at' => $user['created_at']
        ];
        
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Record successful login
        record_login_attempt($email, $ip, true);
        record_login_history($user['id'], $ip);
        
        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Clear failed attempts for this IP
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?");
        $stmt->execute([$ip]);
        
        return [
            'success' => true,
            'message' => 'Login successful!',
            'redirect' => get_user_dashboard_url($user['role'])
        ];
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred during login. Please try again.'
        ];
    }
}

/**
 * Register new user
 * @param array $data User registration data
 * @return array Result with success status and message
 */
function register($data) {
    global $pdo;
    
    try {
        // Validate input
        $validation = validate_registration($data);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'Email address is already registered.'
            ];
        }
        
        // Hash password
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, status, created_at) 
            VALUES (?, ?, ?, ?, 'active', NOW())
        ");
        
        $role = $data['role'] ?? 'student'; // Default to student
        $stmt->execute([
            $data['name'],
            $data['email'],
            $hashed_password,
            $role
        ]);
        
        return [
            'success' => true,
            'message' => 'Registration successful! You can now log in.'
        ];
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred during registration. Please try again.'
        ];
    }
}

/**
 * Validate registration data
 * @param array $data
 * @return array
 */
function validate_registration($data) {
    $errors = [];
    
    if (empty($data['name']) || strlen($data['name']) < 2) {
        $errors[] = 'Name must be at least 2 characters long.';
    }
    
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }
    
    if (empty($data['password']) || strlen($data['password']) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
    }
    
    if ($data['password'] !== $data['confirm_password']) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (!empty($data['role']) && !in_array($data['role'], ['student', 'teacher', 'admin'])) {
        $errors[] = 'Invalid role selected.';
    }
    
    return [
        'valid' => empty($errors),
        'message' => implode(' ', $errors)
    ];
}

/**
 * Logout user
 */
function logout() {
    if (is_logged_in()) {
        $user = user();
        
        // Update logout time in login history
        global $pdo;
        try {
            $stmt = $pdo->prepare("
                UPDATE login_history 
                SET logout_at = NOW(), 
                    duration_seconds = TIMESTAMPDIFF(SECOND, login_at, NOW())
                WHERE user_id = ? AND logout_at IS NULL
                ORDER BY login_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$user['id']]);
        } catch (Exception $e) {
            error_log("Logout update error: " . $e->getMessage());
        }
    }
    
    // Clear session
    $_SESSION = [];
    session_destroy();
    
    // Redirect to home
    header('Location: index.php?page=home');
    exit;
}

/**
 * Record login attempt
 * @param string $email
 * @param string $ip
 * @param bool $success
 */
function record_login_attempt($email, $ip, $success) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO login_attempts (email, ip, success, attempted_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$email, $ip, $success ? 1 : 0]);
    } catch (Exception $e) {
        error_log("Login attempt recording error: " . $e->getMessage());
    }
}

/**
 * Record login history
 * @param int $user_id
 * @param string $ip
 */
function record_login_history($user_id, $ip) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO login_history (user_id, ip, login_at, user_agent) 
            VALUES (?, ?, NOW(), ?)
        ");
        $stmt->execute([$user_id, $ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);
    } catch (Exception $e) {
        error_log("Login history recording error: " . $e->getMessage());
    }
}

/**
 * Record page access
 * @param string $page
 */
function record_access($page) {
    global $pdo;
    
    try {
        $user = user();
        $user_id = $user ? $user['id'] : null;
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $stmt = $pdo->prepare("
            INSERT INTO access_logs (user_id, page, ip, accessed_at, user_agent) 
            VALUES (?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([$user_id, $page, $ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);
    } catch (Exception $e) {
        error_log("Access logging error: " . $e->getMessage());
    }
}

/**
 * Get dashboard URL for user role
 * @param string $role
 * @return string
 */
function get_user_dashboard_url($role) {
    switch($role) {
        case 'admin':
            return 'index.php?page=admin_dashboard';
        case 'teacher':
            return 'index.php?page=teacher_dashboard';
        case 'student':
            return 'index.php?page=student_dashboard';
        default:
            return 'index.php?page=home';
    }
}

/**
 * Check session timeout
 */
function check_session_timeout() {
    if (is_logged_in()) {
        $last_activity = $_SESSION['last_activity'] ?? 0;
        
        if (time() - $last_activity > SESSION_LIFETIME) {
            logout();
        }
        
        $_SESSION['last_activity'] = time();
    }
}

// Check session timeout on every request
check_session_timeout();
?>