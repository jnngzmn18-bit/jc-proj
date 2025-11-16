<?php
$page_title = "View Submission";
include __DIR__ . '/../common/header.php';
require_role('teacher');

$me = user();
$submission_id = $_GET['id'];

// Get submission details
try {
    $stmt = $pdo->prepare('
        SELECT s.*, u.name as student_name, u.email as student_email, l.title as lesson_title, a.title as activity_title
        FROM submissions s
        JOIN users u ON s.student_id = u.id
        JOIN lessons l ON s.lesson_id = l.id
        LEFT JOIN activities a ON s.activity_id = a.id
        WHERE s.id = ? AND l.teacher_id = ?
    ');
    $stmt->execute([$submission_id, $me['id']]);
    $submission = $stmt->fetch();

    if (!$submission) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Submission not found or access denied.'
        ];
        header('Location: index.php?page=teacher_submissions');
        exit;
    }

} catch (Exception $e) {
    error_log("View submission error: " . $e->getMessage());
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Error loading submission.'
    ];
    header('Location: index.php?page=teacher_submissions');
    exit;
}

// Handle grading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'grade') {
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
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="box" style="background: linear-gradient(135deg, var(--eco-green), var(--eco-light-green)); color: white; margin-bottom: var(--spacing-xl);">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h2 style="color: white; margin-bottom: var(--spacing-sm);">
                    📝 Submission Details
                </h2>
                <p style="color: rgba(255,255,255,0.9); margin: 0;">
                    Review and grade student submission
                </p>
            </div>
            <div class="d-flex gap-3">
                <a href="index.php?page=teacher_submissions" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i>Back to Submissions
                </a>
            </div>
        </div>
    </div>

    <!-- Submission Details -->
    <div class="row">
        <div class="col-lg-8">
            <!-- Student and Lesson Info -->
            <div class="box mb-4">
                <h4 class="mb-3">👤 Student Information</h4>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($submission['student_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($submission['student_email']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Lesson:</strong> <?php echo htmlspecialchars($submission['lesson_title']); ?></p>
                        <?php if ($submission['activity_title']): ?>
                            <p><strong>Activity:</strong> <?php echo htmlspecialchars($submission['activity_title']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Submission Content -->
            <div class="box mb-4">
                <h4 class="mb-3">📄 Submission Content</h4>

                <?php if ($submission['content']): ?>
                    <div class="mb-3">
                        <h6>Content:</h6>
                        <div style="padding: var(--spacing-md); background: var(--eco-cream); border-radius: var(--border-radius); white-space: pre-wrap;">
                            <?php echo nl2br(htmlspecialchars($submission['content'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($submission['file_path']): ?>
                    <div class="mb-3">
                        <h6>Attached File:</h6>
                        <div class="d-flex align-items-center gap-3">
                            <i class="fas fa-file" style="color: var(--eco-green); font-size: 1.5rem;"></i>
                            <div>
                                <p class="mb-1"><?php echo basename($submission['file_path']); ?></p>
                                <a href="index.php?page=download&file=<?php echo urlencode($submission['file_path']); ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-download me-1"></i>Download
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Status:</strong>
                            <span class="badge badge-<?php
                                echo $submission['status'] === 'graded' ? 'success' :
                                    ($submission['status'] === 'submitted' ? 'warning' : 'info');
                            ?>">
                                <?php echo ucfirst($submission['status']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Submitted:</strong> <?php echo date('M j, Y \a\t g:i A', strtotime($submission['submitted_at'])); ?></p>
                    </div>
                </div>

                <?php if ($submission['graded_at']): ?>
                    <p><strong>Graded:</strong> <?php echo date('M j, Y \a\t g:i A', strtotime($submission['graded_at'])); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Grading Section -->
            <div class="box">
                <h4 class="mb-3">🎯 Grade Submission</h4>

                <?php if ($submission['status'] === 'graded'): ?>
                    <div class="alert alert-success">
                        <h5>Already Graded</h5>
                        <p><strong>Grade:</strong> <?php echo $submission['grade']; ?>%</p>
                        <?php if ($submission['feedback']): ?>
                            <p><strong>Feedback:</strong> <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <button onclick="editGrade()" class="btn btn-secondary w-100">
                        <i class="fas fa-edit me-1"></i>Edit Grade
                    </button>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="action" value="grade">

                        <div class="form-group mb-3">
                            <label for="grade" class="form-label">Grade (0-100) *</label>
                            <input type="number" class="form-control" id="grade" name="grade" min="0" max="100" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="feedback" class="form-label">Feedback</label>
                            <textarea class="form-control" id="feedback" name="feedback" rows="4" placeholder="Provide constructive feedback to help the student improve..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-star me-1"></i>Submit Grade
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Grade Modal -->
<div class="modal fade" id="editGradeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Grade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="grade">

                    <div class="form-group mb-3">
                        <label for="editGrade" class="form-label">Grade (0-100) *</label>
                        <input type="number" class="form-control" id="editGrade" name="grade" min="0" max="100"
                               value="<?php echo $submission['grade']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="editFeedback" class="form-label">Feedback</label>
                        <textarea class="form-control" id="editFeedback" name="feedback" rows="4"
                                  placeholder="Provide constructive feedback to help the student improve..."><?php echo htmlspecialchars($submission['feedback']); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Grade</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editGrade() {
    document.getElementById('editGrade').value = '<?php echo $submission['grade']; ?>';
    document.getElementById('editFeedback').value = `<?php echo addslashes($submission['feedback']); ?>`;
    new bootstrap.Modal(document.getElementById('editGradeModal')).show();
}
</script>

<?php include __DIR__ . '/../common/footer.php'; ?>