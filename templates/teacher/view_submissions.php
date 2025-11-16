<?php
$page_title = "View Submissions";
include __DIR__ . '/../common/header.php';
require_role('teacher');

$me = user();
$lesson_id = $_GET['lesson_id'] ?? null;

// Get lesson if specific lesson requested
$lesson = null;
if ($lesson_id) {
    $stmt = $pdo->prepare('SELECT * FROM lessons WHERE id = ? AND teacher_id = ?');
    $stmt->execute([$lesson_id, $me['id']]);
    $lesson = $stmt->fetch();
    
    if (!$lesson) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Lesson not found or access denied.'
        ];
        header('Location: index.php?page=teacher_dashboard');
        exit;
    }
}

// Handle grading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'grade') {
    $submission_id = $_POST['submission_id'];
    $grade = $_POST['grade'];
    $feedback = $_POST['feedback'];
    
    try {
        $stmt = $pdo->prepare('
            UPDATE submissions 
            SET grade = ?, feedback = ?, status = "graded", graded_at = NOW() 
            WHERE id = ?
        ');
        $stmt->execute([$grade, $feedback, $submission_id]);
        
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => 'Grade submitted successfully!'
        ];
    } catch (Exception $e) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Error submitting grade.'
        ];
    }
    
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Get submissions
try {
    if ($lesson) {
        // Submissions for specific lesson
        $stmt = $pdo->prepare('
            SELECT s.*, u.name as student_name, u.email as student_email, l.title as lesson_title
            FROM submissions s
            JOIN users u ON s.student_id = u.id
            JOIN lessons l ON s.lesson_id = l.id
            WHERE s.lesson_id = ? AND l.teacher_id = ?
            ORDER BY s.submitted_at DESC, u.name ASC
        ');
        $stmt->execute([$lesson_id, $me['id']]);
    } else {
        // All submissions for teacher's lessons
        $stmt = $pdo->prepare('
            SELECT s.*, u.name as student_name, u.email as student_email, l.title as lesson_title
            FROM submissions s
            JOIN users u ON s.student_id = u.id
            JOIN lessons l ON s.lesson_id = l.id
            WHERE l.teacher_id = ?
            ORDER BY s.submitted_at DESC, l.title ASC, u.name ASC
        ');
        $stmt->execute([$me['id']]);
    }
    
    $submissions = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Submissions view error: " . $e->getMessage());
    $submissions = [];
}

// Filter submissions
$filter = $_GET['filter'] ?? 'all';
$filtered_submissions = array_filter($submissions, function($sub) use ($filter) {
    switch($filter) {
        case 'pending':
            return $sub['status'] === 'submitted';
        case 'graded':
            return $sub['status'] === 'graded';
        case 'draft':
            return $sub['status'] === 'draft';
        default:
            return true;
    }
});
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2" style="color: white;"><?php echo $lesson ? 'Submissions for: ' . htmlspecialchars($lesson['title']) : 'All Submissions'; ?></h2>
                    <p class="mb-0" style="color: rgba(255,255,255,0.8);">Review and grade student submissions</p>
                </div>
                <div class="text-end">
                    <div class="display-4 mb-1"><?php echo count($filtered_submissions); ?></div>
                    <div style="color: rgba(255,255,255,0.8);">submissions</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="box">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex gap-3 align-items-center flex-wrap">
                <label for="filter" class="form-label mb-0"><strong>Filter:</strong></label>
                <select id="filter" class="form-select" style="width: auto;" onchange="filterSubmissions()">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Submissions</option>
                    <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                    <option value="graded" <?php echo $filter === 'graded' ? 'selected' : ''; ?>>Graded</option>
                    <option value="draft" <?php echo $filter === 'draft' ? 'selected' : ''; ?>>Drafts</option>
                </select>
            </div>
            
            <div class="d-flex gap-3">
                <?php if ($lesson): ?>
                    <a href="index.php?page=view_lesson&id=<?php echo $lesson['id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-eye me-2"></i>View Lesson
                    </a>
                <?php endif; ?>
                <a href="index.php?page=teacher_dashboard" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Submissions List -->
    <?php if (empty($filtered_submissions)): ?>
        <div class="box text-center">
            <div style="font-size: 4rem; margin-bottom: var(--spacing-lg); color: var(--eco-gray);">📝</div>
            <h4 style="color: var(--eco-dark-gray);">No submissions found</h4>
            <p class="text-muted">
                <?php echo $filter === 'all' ? 'No students have submitted work yet.' : 'No submissions match the selected filter.'; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($filtered_submissions as $submission): ?>
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card h-100" style="border-left: 4px solid <?php 
                        echo $submission['status'] === 'graded' ? 'var(--eco-sage)' : 
                            ($submission['status'] === 'submitted' ? 'var(--eco-sun)' : 'var(--eco-sky)'); 
                    ?>;">
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge badge-<?php 
                                echo $submission['status'] === 'graded' ? 'success' : 
                                    ($submission['status'] === 'submitted' ? 'warning' : 'info'); 
                            ?>">
                                <?php echo ucfirst($submission['status']); ?>
                            </span>
                            <?php if ($submission['grade'] !== null): ?>
                                <div style="font-size: 1.25rem; font-weight: bold; color: var(--eco-green);">
                                    <?php echo $submission['grade']; ?>%
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Student Info -->
                        <h5 style="margin-bottom: var(--spacing-sm);">
                            👤 <?php echo htmlspecialchars($submission['student_name']); ?>
                        </h5>
                        
                        <?php if (!$lesson): ?>
                            <h6 style="color: var(--eco-light-green); margin-bottom: var(--spacing-sm);">
                                📚 <?php echo htmlspecialchars($submission['lesson_title']); ?>
                            </h6>
                        <?php endif; ?>

                        <p style="font-size: 0.875rem; color: var(--eco-gray); margin-bottom: var(--spacing-md);">
                            <?php echo htmlspecialchars($submission['student_email']); ?>
                        </p>

                        <!-- Submission Details -->
                        <div style="margin-bottom: var(--spacing-lg);">
                            <?php if ($submission['submitted_at']): ?>
                                <p style="font-size: 0.875rem; margin-bottom: var(--spacing-sm);">
                                    <strong>Submitted:</strong> <?php echo date('M j, Y \a\t g:i A', strtotime($submission['submitted_at'])); ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($submission['graded_at']): ?>
                                <p style="font-size: 0.875rem; margin-bottom: var(--spacing-sm);">
                                    <strong>Graded:</strong> <?php echo date('M j, Y \a\t g:i A', strtotime($submission['graded_at'])); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($submission['content']): ?>
                                <div style="margin-bottom: var(--spacing-sm);">
                                    <strong>Content Preview:</strong>
                                    <div style="padding: var(--spacing-sm); background: var(--eco-cream); border-radius: var(--border-radius); font-size: 0.875rem; max-height: 80px; overflow: hidden;">
                                        <?php echo nl2br(htmlspecialchars(substr($submission['content'], 0, 150))); ?>
                                        <?php if (strlen($submission['content']) > 150) echo '...'; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($submission['file_path']): ?>
                                <div style="margin-bottom: var(--spacing-sm);">
                                    <strong>File:</strong>
                                    <div style="display: flex; align-items: center; gap: var(--spacing-sm); margin-top: var(--spacing-xs);">
                                        <i class="fas fa-file" style="color: var(--eco-green);"></i>
                                        <span style="font-size: 0.875rem;"><?php echo basename($submission['file_path']); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Actions -->
                        <div class="card-actions mt-auto">
                            <button onclick="viewSubmission(<?php echo $submission['id']; ?>, <?php echo $submission['lesson_id']; ?>)" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye me-1"></i>View
                            </button>
                            
                            <?php if ($submission['status'] === 'submitted'): ?>
                                <button onclick="gradeSubmission(<?php echo $submission['id']; ?>)" class="btn btn-sm btn-success">
                                    <i class="fas fa-star me-1"></i>Grade
                                </button>
                            <?php elseif ($submission['status'] === 'graded'): ?>
                                <button onclick="editGrade(<?php echo $submission['id']; ?>)" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-edit me-1"></i>Edit Grade
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Grading Modal -->
<div class="modal fade" id="gradingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Grade Submission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="grade">
                    <input type="hidden" name="submission_id" id="gradeSubmissionId">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="grade" class="form-label">Grade (0-100) *</label>
                                <input type="number" class="form-control" id="grade" name="grade" min="0" max="100" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="feedback" class="form-label">Feedback</label>
                        <textarea class="form-control" id="feedback" name="feedback" rows="4" placeholder="Provide constructive feedback to help the student improve..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Submit Grade</button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
function filterSubmissions() {
    const filter = document.getElementById('filter').value;
    const url = new URL(window.location);
    url.searchParams.set('filter', filter);
    window.location.href = url.toString();
}

function gradeSubmission(submissionId) {
    document.getElementById('gradeSubmissionId').value = submissionId;
    new bootstrap.Modal(document.getElementById('gradingModal')).show();
}

function editGrade(submissionId) {
    // Similar to gradeSubmission but pre-fill with existing values
    gradeSubmission(submissionId);
}

function viewSubmission(submissionId, activityId) {
    // Redirect directly to submit_activity page with lesson_id (since activities are lessons) and view_submission parameters
    window.location.href = `index.php?page=submit_activity&id=${activityId}&view_submission=${submissionId}`;
}
</script>

<?php include __DIR__ . '/../common/footer.php'; ?>