<?php 
include __DIR__.'/../common/header.php'; 
require_role('teacher');
$me = user();
$id = $_GET['id'] ?? null;

$stmt = $pdo->prepare('SELECT * FROM lessons WHERE id = ? AND teacher_id = ?');
$stmt->execute([$id, $me['id']]);
$lesson = $stmt->fetch();

// Fetch the latest content from content table
$stmt = $pdo->prepare('SELECT content FROM content WHERE lesson_id = ? ORDER BY version DESC LIMIT 1');
$stmt->execute([$id]);
$content_row = $stmt->fetch();
$lesson_content = $content_row ? $content_row['content'] : '';

if(!$lesson) { 
    echo '<div class="alert alert-danger">Lesson not found or access denied</div>'; 
    include __DIR__.'/../common/footer.php'; 
    exit; 
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $content = $_POST['content'];
    $is_activity = isset($_POST['is_activity']) ? 1 : 0;
    $file_path = $lesson['file_path'];

    // Handle file upload
    if(!empty($_FILES['file']['name'])) {
        $uploadDir = __DIR__.'/../../public/uploads/';
        if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $fn = basename($_FILES['file']['name']);
        $target = $uploadDir.time().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $fn);
        if(move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            $file_path = 'uploads/'.basename($target);
        }
    }

    $stmt = $pdo->prepare('UPDATE lessons SET title=?,description=?,content=?,is_activity=?,file_path=? WHERE id=?');
    $stmt->execute([$title, $desc, $content, $is_activity, $file_path, $id]);

    // Get the next version number
    $stmt = $pdo->prepare('SELECT MAX(version) as max_version FROM content WHERE lesson_id = ?');
    $stmt->execute([$id]);
    $version_row = $stmt->fetch();
    $next_version = ($version_row && $version_row['max_version']) ? $version_row['max_version'] + 1 : 1;

    // Insert new version into content table
    $stmt = $pdo->prepare('INSERT INTO content (lesson_id, content_type, content, version) VALUES (?, ?, ?, ?)');
    $stmt->execute([$id, 'html', $content, $next_version]);

    // Regenerate QR code if needed
    if(!class_exists('QRcode')) {
        class QRcode {
            public static function png($text, $outfile = false, $level = 'L', $size = 4, $margin = 2) {
                $url = 'https://chart.googleapis.com/chart?cht=qr&chs='.(int)($size*40).'x'.(int)($size*40).'&chl='.urlencode($text).'&chld='.$level.'|'.$margin;
                $img = @file_get_contents($url);
                if($img && $outfile) {
                    file_put_contents($outfile, $img);
                    return true;
                }
                if($img) {
                    header('Content-Type: image/png');
                    echo $img;
                    return true;
                }
                return false;
            }
        }
    }

    $qrcodeDir = __DIR__.'/../../public/qrcodes/';
    if(!is_dir($qrcodeDir)) mkdir($qrcodeDir, 0755, true);
    $lessonUrl = 'http://'.$_SERVER['HTTP_HOST'].'/public/index.php?page=view_lesson&id='.$id;
    $outfile = $qrcodeDir.'lesson_'.$id.'.png';
    QRcode::png($lessonUrl, $outfile, 'L', 4, 2);

    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => 'Lesson updated successfully!'
    ];

    header('Location: index.php?page=teacher_dashboard');
    exit;
}
?>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="activity-form">
                <div class="d-flex align-items-center mb-4">
                    <div class="me-3">
                        <i class="fas fa-edit text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <div>
                        <h2 class="mb-1">Edit Content</h2>
                        <p class="text-muted mb-0">Update your lesson or activity</p>
                    </div>
                </div>

                <form method="post" enctype="multipart/form-data" id="editForm">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group mb-4">
                                <label for="title" class="form-label">
                                    <i class="fas fa-heading me-2"></i>Title *
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="title" 
                                       name="title" 
                                       value="<?= htmlspecialchars($lesson['title']) ?>" 
                                       required 
                                       placeholder="Enter a descriptive title">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group mb-4">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left me-2"></i>Description
                                </label>
                                <textarea class="form-control"
                                          id="description"
                                          name="description"
                                          rows="4"
                                          placeholder="Provide a detailed description of the content"><?= htmlspecialchars($lesson['description']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group mb-4">
                                <label for="content" class="form-label">
                                    <i class="fas fa-code me-2"></i>Content (HTML)
                                </label>
                                <textarea class="form-control"
                                          id="content"
                                          name="content"
                                          rows="8"
                                          placeholder="Enter the lesson content in HTML format"><?= htmlspecialchars($lesson_content) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="is_activity"
                                           name="is_activity"
                                           <?= $lesson['is_activity'] ? 'checked' : '' ?>>
                                    <label class="form-check-label <?= $lesson['is_activity'] ? 'text-success fw-bold' : '' ?>" for="is_activity">
                                        <i class="fas fa-tasks me-2"></i>This is an activity
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Activities allow student submissions and grading
                                </small>
                            </div>
                        </div>
                    </div>

                    <?php if($lesson['file_path']): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-info mb-4">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file-alt me-3"></i>
                                    <div>
                                        <strong>Current File:</strong>
                                        <a href="index.php?page=download&file=<?= urlencode($lesson['file_path']) ?>" 
                                           class="btn btn-sm btn-outline-primary ms-2">
                                            <i class="fas fa-download me-1"></i>Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group mb-4">
                                <label for="file" class="form-label">
                                    <i class="fas fa-paperclip me-2"></i>
                                    <?= $lesson['file_path'] ? 'Replace File' : 'Attach File' ?>
                                </label>
                                <div class="file-upload-zone">
                                    <input type="file" 
                                           class="form-control" 
                                           id="file" 
                                           name="file" 
                                           accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.gif">
                                    <div class="mt-2">
                                        <i class="fas fa-cloud-upload-alt text-muted" style="font-size: 2rem;"></i>
                                        <p class="text-muted mb-0">
                                            <strong>Choose a file</strong> or drag it here
                                        </p>
                                        <small class="text-muted">
                                            Supported: PDF, DOC, PPT, Images (Max: 10MB)
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="index.php?page=teacher_dashboard" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Content
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS and Font Awesome -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload enhancement
    const fileInput = document.getElementById('file');
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
    const form = document.getElementById('editForm');
    form.addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        
        if (!title) {
            e.preventDefault();
            alert('Please enter a title for your content.');
            document.getElementById('title').focus();
            return false;
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
        submitBtn.disabled = true;
        
        // Re-enable if form submission fails
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 5000);
    });
    
    // Activity checkbox enhancement
    const activityCheckbox = document.getElementById('is_activity');
    activityCheckbox.addEventListener('change', function() {
        const label = this.nextElementSibling;
        if (this.checked) {
            label.classList.add('text-success', 'fw-bold');
        } else {
            label.classList.remove('text-success', 'fw-bold');
        }
    });
});
</script>

<?php include __DIR__.'/../common/footer.php'; ?>