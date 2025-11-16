<?php
$page_title = "View Lesson";
include __DIR__ . '/../templates/common/header.php';
require_login();

$lesson_id = $_GET['id'] ?? 0;
$me = user();

// Get lesson details
try {
    $stmt = $pdo->prepare('
        SELECT l.*, u.name AS teacher_name
        FROM lessons l
        JOIN users u ON l.teacher_id = u.id
        WHERE l.id = ? AND l.status = "published"
    ');
    $stmt->execute([$lesson_id]);
    $lesson = $stmt->fetch();

    // Fetch the latest content from content table
    $stmt = $pdo->prepare('SELECT content FROM content WHERE lesson_id = ? ORDER BY version DESC LIMIT 1');
    $stmt->execute([$lesson_id]);
    $content_row = $stmt->fetch();
    $lesson_content = $content_row ? $content_row['content'] : '';
    
    if (!$lesson) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Lesson not found or not available.'
        ];
        header('Location: index.php?page=' . ($me['role'] === 'teacher' ? 'teacher_dashboard' : 'student_dashboard'));
        exit;
    }
    
    // Get submission if student and activity
    $submission = null;
    if ($me['role'] === 'student' && $lesson['is_activity']) {
        $stmt = $pdo->prepare('SELECT * FROM submissions WHERE lesson_id = ? AND student_id = ?');
        $stmt->execute([$lesson_id, $me['id']]);
        $submission = $stmt->fetch();
    }
    
    // Get enrolled students if teacher
    $students = [];
    if ($me['role'] === 'teacher' && $lesson['teacher_id'] == $me['id']) {
        $stmt = $pdo->prepare('
            SELECT u.id, u.name, u.email, s.id as submission_id, s.status, s.grade, s.submitted_at, s.graded_at
            FROM users u
            LEFT JOIN submissions s ON u.id = s.student_id AND s.lesson_id = ?
            WHERE u.role = "student" AND u.status = "active"
            ORDER BY u.name ASC
        ');
        $stmt->execute([$lesson_id]);
        $students = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    error_log("Lesson view error: " . $e->getMessage());
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Error loading lesson.'
    ];
    header('Location: index.php?page=home');
    exit;
}

// Handle form submission for activity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answer']) && $me['role'] === 'student' && $lesson['is_activity']) {
    $answer = trim($_POST['answer']);
    if (empty($answer)) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Please provide an answer.'
        ];
    } else {
        try {
            if ($submission) {
                // Update existing submission
                $stmt = $pdo->prepare('UPDATE submissions SET content = ?, status = "submitted", submitted_at = NOW() WHERE id = ?');
                $stmt->execute([$answer, $submission['id']]);
            } else {
                // Create new submission
                $stmt = $pdo->prepare('INSERT INTO submissions (lesson_id, student_id, content, status, submitted_at) VALUES (?, ?, ?, "submitted", NOW())');
                $stmt->execute([$lesson_id, $me['id'], $answer]);
            }
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Your submission has been saved successfully.'
            ];
            header('Location: index.php?page=view_lesson&id=' . $lesson_id);
            exit;
        } catch (Exception $e) {
            error_log("Submission error: " . $e->getMessage());
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Error saving submission.'
            ];
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Lesson Header -->
            <div class="box" style="background: linear-gradient(135deg, var(--primary-green), var(--primary-dark)); color: white; margin-bottom: 2 rem;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <span class="badge" style="background: rgba(255,255,255,0.2); color: white; font-size: 0.875rem;">
                                <?php echo $lesson['is_activity'] ? '📝 Activity' : '📚 Lesson'; ?>
                            </span>
                            <?php if ($lesson['is_activity'] && $submission && $submission['grade'] !== null): ?>
                                <span class="badge" style="background: var(--success); color: white;">
                                    Grade: <?php echo $submission['grade']; ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                        <h1 style="color: white; margin-bottom: var(--spacing-sm); font-size: 2rem;">
                            <?php echo htmlspecialchars($lesson['title']); ?>
                        </h1>
                        <div class="d-flex align-items-center gap-4">
                            <span style="color: rgba(255,255,255,0.9);">
                                👨‍🏫 <?php echo htmlspecialchars($lesson['teacher_name']); ?>
                            </span>
                            <span style="color: rgba(255,255,255,0.8); font-size: 0.875rem;">
                                📅 <?php echo date('M j, Y', strtotime($lesson['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="text-end">
                        <div style="font-size: 3rem;">
                            <?php echo $lesson['is_activity'] ? '🎯' : '📖'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lesson Content -->
            <div class="box">
                <h3 style="margin-bottom: var(--spacing-lg);">📋 Content</h3>

                <?php if ($lesson['description']): ?>
                    <div class="box" style="margin-bottom: 2rem;">
                        <h4 style="color: var(--eco-light-green); margin-bottom: var(--spacing-md);">Description</h4>
                        <div style="padding: var(--spacing-lg); background: var(--eco-cream); border-radius: var(--border-radius); border-left: 4px solid var(--eco-sage);">
                            <?php echo nl2br(htmlspecialchars($lesson['description'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($lesson_content): ?>
                    <div class="box" style="margin-bottom: var(--spacing-lg);">
                        <h4 style="color: var(--eco-light-green); margin-bottom: var(--spacing-md);">Detailed Content</h4>
                        <div class="content-area" style="padding: var(--spacing-lg); background: var(--eco-white); border: 1px solid var(--border-color); border-radius: var(--border-radius);">
                            <?php echo $lesson_content; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($lesson['file_path']): ?>
                    <div class="box" style="margin-bottom: var(--spacing-lg);">
                        <h4 style="color: var(--eco-light-green); margin-bottom: var(--spacing-md);">📎 Attachments</h4>
                        <div style="padding: var(--spacing-md); background: var(--eco-mint); border-radius: var(--border-radius); display: flex; align-items: center; gap: var(--spacing-md);">
                            <i class="fas fa-file" style="font-size: 1.5rem; color: var(--eco-green);"></i>
                            <div class="flex-grow-1">
                                <strong><?php echo basename($lesson['file_path']); ?></strong>
                                <br>
                                <small class="text-muted">Click to download</small>
                            </div>
                            <a href="index.php?page=download&file=<?php echo urlencode($lesson['file_path']); ?>"
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-download me-2"></i>Download
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Student Actions -->
            <?php if ($me['role'] === 'student' && $lesson['is_activity']): ?>
                <div class="box">
                    <h3 style="margin-bottom: var(--spacing-lg);">🎯 Activity Submission</h3>

                    <?php if ($submission && $submission['status'] === 'submitted'): ?>
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>Your Submission Status</h5>
                            <p><strong>Status:</strong> <?php echo ucfirst($submission['status']); ?></p>
                            <?php if ($submission['submitted_at']): ?>
                                <p><strong>Submitted:</strong> <?php echo date('M j, Y \a\t g:i A', strtotime($submission['submitted_at'])); ?></p>
                            <?php endif; ?>
                            <?php if ($submission['grade'] !== null): ?>
                                <p><strong>Grade:</strong> <span style="color: var(--eco-green); font-weight: bold;"><?php echo $submission['grade']; ?>%</span></p>
                            <?php endif; ?>
                            <?php if ($submission['feedback']): ?>
                                <p><strong>Feedback:</strong></p>
                                <div style="padding: var(--spacing-md); background: var(--eco-cream); border-radius: var(--border-radius); margin-top: var(--spacing-sm);">
                                    <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex gap-3">
                            <a href="index.php?page=submit_activity&id=<?php echo $lesson['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit me-2"></i>View Submission
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="answer" class="form-label">Your Answer:</label>
                                <textarea class="form-control" id="answer" name="answer" rows="10" placeholder="Type your answer here..." required><?php echo $submission ? htmlspecialchars($submission['content']) : ''; ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Submit
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="box">
                <h4 style="margin-bottom: var(--spacing-lg);">🚀 Quick Actions</h4>
                <div class="d-flex flex-column gap-3">
                    <button onclick="showQR('<?php echo 'http://'.$_SERVER['HTTP_HOST'].'/public/index.php?page=view_lesson&id='.$lesson['id']; ?>', '<?php echo htmlspecialchars($lesson['title']); ?>')" 
                            class="btn btn-secondary">
                        <i class="fas fa-qrcode me-2"></i>Generate QR Code
                    </button>
                    
                    <a href="javascript:window.print()" class="btn btn-secondary">
                        <i class="fas fa-print me-2"></i>Print Lesson
                    </a>
                    
                    <a href="index.php?page=<?php echo $me['role']; ?>_dashboard" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>

            <!-- Teacher: Student Progress -->
            <?php if ($me['role'] === 'teacher' && $lesson['teacher_id'] == $me['id'] && $lesson['is_activity']): ?>
                <div class="box">
                    <h4 style="margin-bottom: var(--spacing-lg);">👥 Student Progress</h4>
                    
                    <?php if (empty($students)): ?>
                        <p class="text-muted">No students enrolled yet.</p>
                    <?php else: ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($students as $student): ?>
                                <div style="padding: var(--spacing-md); border: 1px solid var(--border-color); border-radius: var(--border-radius); margin-bottom: var(--spacing-sm); background: var(--eco-white);">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 style="margin-bottom: var(--spacing-xs);">
                                                <?php echo htmlspecialchars($student['name']); ?>
                                            </h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($student['email']); ?></small>
                                        </div>
                                        <div class="text-end">
                                            <?php if ($student['submission_id']): ?>
                                                <span class="badge badge-<?php 
                                                    echo $student['grade'] !== null ? 'success' : 
                                                        ($student['status'] === 'submitted' ? 'warning' : 'info'); 
                                                ?>">
                                                    <?php echo $student['grade'] !== null ? 'Graded' : 
                                                        ($student['status'] === 'submitted' ? 'Submitted' : 'Draft'); ?>
                                                </span>
                                                <?php if ($student['grade'] !== null): ?>
                                                    <div style="font-weight: bold; color: var(--eco-green); margin-top: var(--spacing-xs);">
                                                        <?php echo $student['grade']; ?>%
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge" style="background: var(--eco-gray); color: white;">Not Started</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($student['submitted_at']): ?>
                                        <div style="margin-top: var(--spacing-sm); font-size: 0.875rem; color: var(--eco-dark-gray);">
                                            📅 Submitted: <?php echo date('M j, Y \a\t g:i A', strtotime($student['submitted_at'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="margin-top: var(--spacing-lg);">
                            <a href="index.php?page=teacher_submissions&lesson_id=<?php echo $lesson['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-list me-2"></i>View All Submissions
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Lesson Info -->
            <div class="box">
                <h4 style="margin-bottom: var(--spacing-lg);">ℹ️ Lesson Information</h4>
                <div style="space-y: var(--spacing-md);">
                    <div style="margin-bottom: var(--spacing-md);">
                        <strong style="color: var(--eco-green);">Type:</strong><br>
                        <?php echo $lesson['is_activity'] ? 'Interactive Activity' : 'Reading Lesson'; ?>
                    </div>
                    
                    <div style="margin-bottom: var(--spacing-md);">
                        <strong style="color: var(--eco-green);">Created:</strong><br>
                        <?php echo date('F j, Y \a\t g:i A', strtotime($lesson['created_at'])); ?>
                    </div>
                    
                    <?php if ($lesson['updated_at'] !== $lesson['created_at']): ?>
                        <div style="margin-bottom: var(--spacing-md);">
                            <strong style="color: var(--eco-green);">Last Updated:</strong><br>
                            <?php echo date('F j, Y \a\t g:i A', strtotime($lesson['updated_at'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <strong style="color: var(--eco-green);">Status:</strong><br>
                        <span class="badge badge-success"><?php echo ucfirst($lesson['status']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Print styles */
@media print {
    .box:first-child {
        background: white !important;
        color: black !important;
    }
    
    .btn, .badge {
        display: none !important;
    }
    
    .col-lg-4 {
        display: none !important;
    }
    
    .col-lg-8 {
        width: 100% !important;
    }
}

/* Content area styling */
.content-area {
    line-height: 1.8;
}

.content-area h1, .content-area h2, .content-area h3 {
    color: var(--eco-green);
    margin-top: var(--spacing-xl);
    margin-bottom: var(--spacing-md);
}

.content-area ul, .content-area ol {
    margin-bottom: var(--spacing-md);
    padding-left: var(--spacing-xl);
}

.content-area li {
    margin-bottom: var(--spacing-sm);
}

.content-area p {
    margin-bottom: var(--spacing-md);
}
</style>

<?php include __DIR__ . '/../templates/common/footer.php'; ?>