<?php
$page_title = "Delete Lesson";
include __DIR__ . '/../common/header.php';
require_role('teacher');

$me = user();
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid lesson ID provided.'
    ];
    header('Location: index.php?page=teacher_dashboard');
    exit;
}

// Get lesson details to confirm ownership and show info
$stmt = $pdo->prepare('SELECT * FROM lessons WHERE id = ? AND teacher_id = ?');
$stmt->execute([$id, $me['id']]);
$lesson = $stmt->fetch();

if (!$lesson) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Lesson not found or access denied.'
    ];
    header('Location: index.php?page=teacher_dashboard');
    exit;
}

// Check if lesson has submissions
$stmt = $pdo->prepare('SELECT COUNT(*) as submission_count FROM submissions WHERE lesson_id = ?');
$stmt->execute([$id]);
$submission_count = $stmt->fetch()['submission_count'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Start transaction
        $pdo->beginTransaction();

        // Delete related submissions first
        $stmt = $pdo->prepare('DELETE FROM submissions WHERE lesson_id = ?');
        $stmt->execute([$id]);

        // Delete related content
        $stmt = $pdo->prepare('DELETE FROM content WHERE lesson_id = ?');
        $stmt->execute([$id]);

        // Delete the lesson
        $stmt = $pdo->prepare('DELETE FROM lessons WHERE id = ? AND teacher_id = ?');
        $stmt->execute([$id, $me['id']]);

        $pdo->commit();

        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => 'Lesson deleted successfully.'
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Delete lesson error: " . $e->getMessage());
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Error deleting lesson. Please try again.'
        ];
    }

    header('Location: index.php?page=teacher_dashboard');
    exit;
}
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <!-- Warning Header -->
            <div class="card border-danger mb-4">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Delete <?php echo $lesson['is_activity'] ? 'Activity' : 'Lesson'; ?>
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-circle me-2"></i>Warning!</h5>
                        <p class="mb-0">This action cannot be undone. Are you sure you want to delete this <?php echo $lesson['is_activity'] ? 'activity' : 'lesson'; ?>?</p>
                    </div>

                    <!-- Lesson Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><?php echo htmlspecialchars($lesson['title']); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <strong>Type:</strong><br>
                                    <?php echo $lesson['is_activity'] ? '📝 Activity' : '📚 Lesson'; ?>
                                </div>
                                <div class="col-sm-6">
                                    <strong>Created:</strong><br>
                                    <?php echo date('M j, Y', strtotime($lesson['created_at'])); ?>
                                </div>
                            </div>

                            <?php if ($lesson['description']): ?>
                                <div class="mt-3">
                                    <strong>Description:</strong><br>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars(substr($lesson['description'], 0, 200)); ?>
                                    <?php if (strlen($lesson['description']) > 200) echo '...'; ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($submission_count > 0): ?>
                                <div class="mt-3">
                                    <div class="alert alert-warning">
                                        <strong>⚠️ Important:</strong> This <?php echo $lesson['is_activity'] ? 'activity' : 'lesson'; ?> has <?php echo $submission_count; ?> student submission(s) that will also be permanently deleted.
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-3 justify-content-end">
                        <a href="index.php?page=teacher_dashboard" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancel
                        </a>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="confirm_delete" value="1">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you absolutely sure you want to delete this <?php echo $lesson['is_activity'] ? 'activity' : 'lesson'; ?> and all associated data? This action cannot be undone.')">
                                <i class="fas fa-trash me-2"></i>Yes, Delete <?php echo $lesson['is_activity'] ? 'Activity' : 'Lesson'; ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../common/footer.php'; ?>