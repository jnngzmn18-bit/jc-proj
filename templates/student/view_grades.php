<?php include __DIR__.'/common/header.php'; require_role('student');

// Get student's grades and submissions
$me = user();
$stmt = $pdo->prepare('
    SELECT s.*, l.title AS lesson_title, l.description AS lesson_description
    FROM submissions s
    JOIN lessons l ON s.lesson_id = l.id
    WHERE s.student_id = ?
    ORDER BY s.submitted_at DESC
');
$stmt->execute([$me['id']]);
$submissions = $stmt->fetchAll();

?>
<div class="box">
<h2>My Grades</h2>
<?php if (empty($submissions)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    You haven't submitted any activities yet. Start by exploring available lessons and submitting your work!
</div>
<?php else: ?>
<div class="row">
    <?php foreach($submissions as $submission): ?>
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">
                    <?= htmlspecialchars($submission['lesson_title']) ?>
                </h5>
                <p class="card-text text-muted">
                    <?= htmlspecialchars(substr($submission['lesson_description'], 0, 100)) ?>...
                </p>
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted">Submitted</small>
                        <div class="fw-bold">
                            <?= date('M j, Y', strtotime($submission['submitted_at'])) ?>
                        </div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Grade</small>
                        <div class="fw-bold">
                            <?php if ($submission['grade'] !== null): ?>
                                <span class="badge bg-success">
                                    <?= $submission['grade'] ?>%
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning">
                                    Pending
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if ($submission['feedback']): ?>
                <div class="mt-3">
                    <small class="text-muted">Feedback</small>
                    <div class="bg-light p-2 rounded small">
                        <?= nl2br(htmlspecialchars($submission['feedback'])) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
</div>
<?php include __DIR__.'/common/footer.php'; ?>
