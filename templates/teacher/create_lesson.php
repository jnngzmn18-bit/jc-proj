<?php 
include __DIR__.'/../common/header.php'; 
require_role('teacher');
$me = user();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $content = $_POST['content'];
    $is_activity = isset($_POST['is_activity']) ? 1 : 0;
    $file_path = null;

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

    // Insert into lessons table
    $stmt = $pdo->prepare('INSERT INTO lessons (teacher_id, title, description, content, is_activity, file_path, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$me['id'], $title, $desc, $content, $is_activity, $file_path, 'published']);
    $lesson_id = $pdo->lastInsertId();

    // Insert initial version into content table
    $stmt = $pdo->prepare('INSERT INTO content (lesson_id, content_type, content, version) VALUES (?, ?, ?, ?)');
    $stmt->execute([$lesson_id, 'html', $content, 1]);

    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => 'Lesson/Activity created successfully!'
    ];

    header('Location: index.php?page=teacher_dashboard');
    exit;
}
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="activity-form">
                <div class="d-flex align-items-center mb-4">
                    <div class="me-3">
                        <i class="fas fa-plus-circle text-success" style="font-size: 2rem;"></i>
                    </div>
                    <div>
                        <h2 class="mb-1">Create New Content</h2>
                        <p class="text-muted mb-0">Add a new lesson or activity for your students</p>
                    </div>
                </div>

                <form method="post" enctype="multipart/form-data" id="createForm">
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
                                          placeholder="Provide a detailed description of the content"></textarea>
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
                                          placeholder="Enter the lesson content in HTML format"></textarea>
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
                                           name="is_activity">
                                    <label class="form-check-label" for="is_activity">
                                        <i class="fas fa-tasks me-2"></i>This is an activity
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Activities allow student submissions and grading
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group mb-4">
                                <label for="file" class="form-label">
                                    <i class="fas fa-paperclip me-2"></i>Attach File
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
                            <i class="fas fa-save me-2"></i>Create Content
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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
    const form = document.getElementById('createForm');
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
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
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