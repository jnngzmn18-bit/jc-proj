<?php
include __DIR__.'/common/header.php';
require_login();
$me = user();
$activity_id = $_GET['id'] ?? null;
$view_submission = $_GET['view_submission'] ?? null;

if (!$activity_id) {
    echo '<div class="alert alert-danger">No activity specified</div>';
    include __DIR__.'/common/footer.php';
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM lessons WHERE id = ? AND is_activity = 1');
$stmt->execute([$activity_id]);
$activity = $stmt->fetch();

if (!$activity) {
    echo '<div class="alert alert-danger">Activity not found</div>';
    include __DIR__.'/common/footer.php';
    exit;
}

// For activities, the lesson is the activity itself
$lesson = $activity;

// Check if teacher is viewing a submission
$submission = null;
$view_mode = false;
if ($view_submission && $me['role'] === 'teacher') {
    $view_mode = true;
    $stmt = $pdo->prepare('SELECT * FROM submissions WHERE id = ?');
    $stmt->execute([$view_submission]);
    $submission = $stmt->fetch();
    if (!$submission) {
        echo '<div class="alert alert-danger">Submission not found</div>';
        include __DIR__.'/common/footer.php';
        exit;
    }
    // Override activity_id with the lesson_id from submission for consistency
    $activity_id = $submission['lesson_id'];
} elseif ($me['role'] === 'student') {
    // Check if student already submitted
    $stmt = $pdo->prepare('SELECT * FROM submissions WHERE lesson_id = ? AND student_id = ?');
    $stmt->execute([$activity_id, $me['id']]);
    $submission = $stmt->fetch();
}

// Check if already submitted
$stmt = $pdo->prepare('SELECT * FROM submissions WHERE lesson_id = ? AND student_id = ?');
$stmt->execute([$lesson_id, $me['id']]);
$existing_submission = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $me['role'] === 'student') {
    $content = $_POST['content'];
    $file_path = null;

    // Handle file upload
    if (!empty($_FILES['file']['name'])) {
        $uploadDir = __DIR__.'/../public/uploads/submissions/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $fn = basename($_FILES['file']['name']);
        $target = $uploadDir.time().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $fn);
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            $file_path = 'uploads/submissions/'.basename($target);
        }
    }

    if ($existing_submission) {
        // Update existing submission
        $stmt = $pdo->prepare('UPDATE submissions SET content = ?, file_path = ?, submitted_at = NOW() WHERE id = ?');
        $stmt->execute([$content, $file_path ?: $existing_submission['file_path'], $existing_submission['id']]);
        $message = 'Submission updated successfully!';
    } else {
        // Create new submission
        $stmt = $pdo->prepare('INSERT INTO submissions (activity_id, student_id, content, file_path, submitted_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$activity_id, $me['id'], $content, $file_path]);
        $message = 'Submission created successfully!';
    }

    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => $message
    ];

    header('Location: index.php?page=student_dashboard');
    exit;
}
?>



<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Activity Info Card -->
            <div class="card mb-4 border-0 shadow-lg" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 15px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start">
                        <div class="me-4">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 1.8rem;">
                                <i class="fas fa-tasks"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="card-title text-dark mb-3 fw-bold" style="font-size: 1.5rem;">
                                <?php if ($view_mode): ?>
                                    Viewing Submission for: <?= htmlspecialchars($activity['title']) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($activity['title']) ?>
                                <?php endif; ?>
                            </h3>
                            <?php if ($lesson['description']): ?>
                                <p class="card-text text-muted mb-4 lh-base" style="font-size: 1rem; line-height: 1.6;">
                                    <?= nl2br(htmlspecialchars($lesson['description'])) ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($lesson['file_path']): ?>
                                <div class="mt-3">
                                    <a href="index.php?page=download&file=<?= urlencode($lesson['file_path']) ?>"
                                       class="btn btn-primary btn-sm px-4 py-2 rounded-pill shadow-sm">
                                        <i class="fas fa-download me-2"></i>Download Activity Materials
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submission Form -->
            <div class="activity-form">
                <div class="d-flex align-items-center mb-4">
                    <div class="me-3">
                        <i class="fas fa-paper-plane text-success" style="font-size: 2rem;"></i>
                    </div>
                    <div>
                        <h2 class="mb-1">
                            <?= $existing_submission ? 'Update Submission' : 'Submit Your Work' ?>
                        </h2>
                        <p class="text-muted mb-0">
                            <?= $existing_submission ? 'Modify your existing submission' : 'Complete and submit your activity' ?>
                        </p>
                    </div>
                </div>

                <?php if ($existing_submission): ?>
                    <div class="alert alert-info mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle me-3"></i>
                            <div>
                                <strong>Previous Submission:</strong> 
                                <?= date('M j, Y \a\t g:i A', strtotime($existing_submission['submitted_at'])) ?>
                                <?php if ($existing_submission['grade']): ?>
                                    <span class="badge bg-success ms-2">Graded: <?= $existing_submission['grade'] ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning ms-2">Pending Review</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($view_mode && $submission): ?>
                    <!-- View Mode: Display Submission Content -->
                    <div class="row">
                        <div class="col-lg-8">
                            <!-- Student Response -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Student Response</h5>
                                </div>
                                <div class="card-body">
                                    <div class="bg-light p-3 rounded">
                                        <?= nl2br(htmlspecialchars($submission['content'])) ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Attached File -->
                            <?php if ($submission['file_path']): ?>
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Attached File</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file text-primary me-3"></i>
                                            <div class="flex-grow-1">
                                                <strong><?= basename($submission['file_path']) ?></strong>
                                            </div>
                                            <a href="index.php?page=download&file=<?= urlencode($submission['file_path']) ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-download me-1"></i>Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-lg-4">
                            <!-- Submission Details -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Submission Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <small class="text-muted">Submitted</small>
                                        <div class="fw-bold"><?= date('M j, Y \a\t g:i A', strtotime($submission['submitted_at'])) ?></div>
                                    </div>

                                    <div class="mb-3">
                                        <small class="text-muted">Status</small>
                                        <div>
                                            <span class="badge bg-<?= $submission['status'] === 'graded' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($submission['status']) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <?php if ($submission['grade']): ?>
                                        <div class="mb-3">
                                            <small class="text-muted">Grade</small>
                                            <div class="fw-bold text-success"><?= $submission['grade'] ?>%</div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($submission['feedback']): ?>
                                        <div class="mb-3">
                                            <small class="text-muted">Feedback</small>
                                            <div class="bg-light p-2 rounded small">
                                                <?= nl2br(htmlspecialchars($submission['feedback'])) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="index.php?page=teacher_submissions" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Submissions
                                        </a>
                                        <button onclick="gradeSubmission(<?= $submission['id'] ?>)" class="btn btn-success">
                                            <i class="fas fa-star me-2"></i>Grade Submission
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Edit/Submit Mode: Show Form -->
                    <form method="post" enctype="multipart/form-data" id="submissionForm">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group mb-4">
                                    <label for="content" class="form-label">
                                        <i class="fas fa-edit me-2"></i>Your Response *
                                    </label>
                                    <textarea class="form-control" 
                                              id="content" 
                                              name="content" 
                                              rows="8" 
                                              required 
                                              placeholder="Enter your detailed response to the activity..."><?= $existing_submission ? htmlspecialchars($existing_submission['content']) : '' ?></textarea>
                                    <div class="form-text">
                                        Provide a comprehensive response to the activity requirements.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group mb-4">
                                    <label for="file" class="form-label">
                                        <i class="fas fa-paperclip me-2"></i>
                                        <?= $existing_submission && $existing_submission['file_path'] ? 'Replace Attachment' : 'Attach File (Optional)' ?>
                                    </label>
                                    
                                    <?php if ($existing_submission && $existing_submission['file_path']): ?>
                                        <div class="alert alert-secondary mb-3">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-file-alt me-3"></i>
                                                <div>
                                                    <strong>Current File:</strong>
                                                    <a href="index.php?page=download&file=<?= urlencode($existing_submission['file_path']) ?>" 
                                                       class="btn btn-sm btn-outline-secondary ms-2">
                                                        <i class="fas fa-download me-1"></i>Download
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="file-upload-zone">
                                        <input type="file" 
                                               class="form-control" 
                                               id="file" 
                                               name="file" 
                                               accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.txt">
                                        <div class="mt-2">
                                            <i class="fas fa-cloud-upload-alt text-muted" style="font-size: 2rem;"></i>
                                            <p class="text-muted mb-0">
                                                <strong>Choose a file</strong> or drag it here
                                            </p>
                                            <small class="text-muted">
                                                Supported: PDF, DOC, PPT, Images, TXT (Max: 10MB)
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="index.php?page=student_dashboard" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-paper-plane me-2"></i>
                                <?= $existing_submission ? 'Update Submission' : 'Submit Work' ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS and Font Awesome -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

<script>
function gradeSubmission(submissionId) {
    // This function would open a grading modal or redirect to grading page
    // For now, we'll redirect to the grading page
    window.location.href = `index.php?page=grade_submission&id=${submissionId}`;
}

document.addEventListener('DOMContentLoaded', function() {
    // Only run file upload enhancement if form exists (not in view mode)
    const fileInput = document.getElementById('file');
    if (fileInput) {
        const fileZone = document.querySelector('.file-upload-zone');

        // File drag and drop
        fileZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            fileZone.classList.add('border-success');
        });

        fileZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            fileZone.classList.remove('border-success');
        });

        fileZone.addEventListener('drop', function(e) {
            e.preventDefault();
            fileZone.classList.remove('border-success');

            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                updateFileDisplay();
            }
        });

        fileInput.addEventListener('change', updateFileDisplay);

        function updateFileDisplay() {
            const file = fileInput.files[0];
            if (file) {
                const fileInfo = document.createElement('div');
                fileInfo.className = 'alert alert-info mt-2';
                fileInfo.innerHTML = `
                    <i class="fas fa-file me-2"></i>
                    <strong>${file.name}</strong> (${(file.size / 1024 / 1024).toFixed(2)} MB)
                `;

                // Remove existing file info
                const existingInfo = fileZone.querySelector('.alert');
                if (existingInfo) existingInfo.remove();

                fileZone.appendChild(fileInfo);
            }
        }

        // Form validation
        const form = document.getElementById('submissionForm');
        form.addEventListener('submit', function(e) {
            const content = document.getElementById('content').value.trim();

            if (!content) {
                e.preventDefault();
                alert('Please provide your response before submitting.');
                document.getElementById('content').focus();
                return false;
            }

            if (content.length < 50) {
                const proceed = confirm('Your response seems quite short. Are you sure you want to submit?');
                if (!proceed) {
                    e.preventDefault();
                    return false;
                }
            }

            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
            submitBtn.disabled = true;

            // Re-enable if form submission fails
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });

        // Character counter for textarea
        const textarea = document.getElementById('content');
        const counter = document.createElement('div');
        counter.className = 'form-text text-end';
        counter.style.fontSize = '0.8rem';
        textarea.parentNode.appendChild(counter);

        function updateCounter() {
            const length = textarea.value.length;
            counter.textContent = `${length} characters`;

            if (length < 50) {
                counter.className = 'form-text text-end text-warning';
            } else if (length > 1000) {
                counter.className = 'form-text text-end text-info';
            } else {
                counter.className = 'form-text text-end text-success';
            }
        }

        textarea.addEventListener('input', updateCounter);
        updateCounter(); // Initial count
    }
});
</script>

<?php include __DIR__.'/common/footer.php'; ?>