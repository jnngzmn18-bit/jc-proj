<?php 
$page_title = "Teacher Dashboard";
include __DIR__.'/../common/header.php'; 
require_role('teacher');

$me = user();

// Get teacher's lessons with statistics
$stmt = $pdo->prepare('
    SELECT l.*, 
           COUNT(DISTINCT s.student_id) as total_submissions,
           AVG(s.grade) as average_grade,
           COUNT(DISTINCT CASE WHEN s.status = "submitted" THEN s.id END) as pending_grades
    FROM lessons l 
    LEFT JOIN submissions s ON l.id = s.lesson_id 
    WHERE l.teacher_id = ? 
    GROUP BY l.id 
    ORDER BY l.created_at DESC
');
$stmt->execute([$me['id']]);
$lessons = $stmt->fetchAll();

// Get recent submissions for quick access
$recentStmt = $pdo->prepare('
    SELECT s.*, l.title as lesson_title, u.name as student_name, l.is_activity
    FROM submissions s
    JOIN lessons l ON s.lesson_id = l.id
    JOIN users u ON s.student_id = u.id
    WHERE l.teacher_id = ? AND s.status = "submitted"
    ORDER BY s.submitted_at DESC
    LIMIT 5
');
$recentStmt->execute([$me['id']]);
$recentSubmissions = $recentStmt->fetchAll();

// Calculate dashboard statistics
$totalLessons = count($lessons);
$totalActivities = count(array_filter($lessons, function($l) { return $l['is_activity']; }));
$totalSubmissions = array_sum(array_column($lessons, 'total_submissions'));
$pendingGrades = array_sum(array_column($lessons, 'pending_grades'));
?>

<!-- Welcome Section -->
<div class="box" style="background: linear-gradient(135deg, var(--primary-green), var(--primary-dark));">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--space-4);">
        <div>
            <h2 style="color: white; margin-bottom: var(--space-2);">
                Welcome back, <?php echo htmlspecialchars($me['name']); ?>!
            </h2>
            <p style="color: rgba(255, 255, 255, 0.9); margin-bottom: 0;">
                Ready to inspire the next generation of environmental stewards?
            </p>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 2rem; margin-bottom: var(--space-2);">🌱</div>
            <div style="font-size: 0.875rem; color: rgba(255,255,255,0.8);">
                <?php echo date('l, F j, Y'); ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="grid" style="margin-bottom: var(--space-8);">
    <div class="card" style="background: linear-gradient(135deg, var(--primary-light)); color: white; border: none;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="color: white; font-size: 2rem; margin-bottom: var(--space-1);"><?php echo $totalLessons; ?></h3>
                <p style="color: rgba(255, 255, 255, 0.9); margin: 0; font-weight: 500;">Total Lessons</p>
            </div>
            <div style="font-size: 2.5rem; opacity: 0.8;">📚</div>
        </div>
    </div>

    <div class="card" style="background: linear-gradient(135deg, var(--info), #0891b2); color: white; border: none;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="color: white; font-size: 2rem; margin-bottom: var(--space-1);"><?php echo $totalActivities; ?></h3>
                <p style="color: rgba(255,255,255,0.9); margin: 0; font-weight: 500;">Activities</p>
            </div>
            <div style="font-size: 2.5rem; opacity: 0.8;">📝</div>
        </div>
    </div>

    <div class="card" style="background: linear-gradient(135deg, var(--warning), #d97706); color: white; border: none;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="color: white; font-size: 2rem; margin-bottom: var(--space-1);"><?php echo $pendingGrades; ?></h3>
                <p style="color: rgba(255,255,255,0.9); margin: 0; font-weight: 500;">Pending Grades</p>
            </div>
            <div style="font-size: 2.5rem; opacity: 0.8;">⏳</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="box" style="margin-bottom: var(--space-8);">
    <h3 style="margin-bottom: var(--space-6);">🚀 Quick Actions</h3>
    <div style="display: flex; gap: var(--space-4); flex-wrap: wrap;">
        <a href="index.php?page=teacher_create" class="btn btn-primary">
            ➕ Create New Content
        </a>
        <a href="index.php?page=teacher_submissions" class="btn btn-secondary">
            📝 View All Submissions (<?php echo $pendingGrades; ?> pending)
        </a>
        <?php if ($totalLessons > 0): ?>
            <button onclick="generateAllQRCodes(this)" class="btn btn-secondary">
                📱 Generate QR Codes
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Submissions (if any) -->
<?php if (!empty($recentSubmissions)): ?>
<div class="box" style="margin-bottom: var(--space-8);">
    <h3 style="margin-bottom: var(--space-6);">📬 Recent Submissions</h3>
    <div style="display: grid; gap: var(--space-4);">
        <?php foreach ($recentSubmissions as $submission): ?>
            <div style="padding: var(--space-4); border: 1px solid var(--gray-200); border-radius: var(--radius-lg); background: var(--gray-50);">
                <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: var(--space-3);">
                    <div style="flex-grow: 1;">
                        <h4 style="margin-bottom: var(--space-2); color: var(--gray-900);">
                            <?php echo htmlspecialchars($submission['lesson_title']); ?>
                            <span class="badge badge-<?php echo $submission['is_activity'] ? 'info' : 'primary'; ?>">
                                <?php echo $submission['is_activity'] ? 'Activity' : 'Lesson'; ?>
                            </span>
                        </h4>
                        <p style="margin-bottom: var(--space-2); color: var(--gray-600);">
                            👤 <strong><?php echo htmlspecialchars($submission['student_name']); ?></strong>
                        </p>
                        <p style="margin: 0; font-size: 0.875rem; color: var(--gray-500);">
                            📅 Submitted <?php echo date('M j, Y \a\t g:i A', strtotime($submission['submitted_at'])); ?>
                        </p>
                    </div>
                    <div>
                        <a href="index.php?page=teacher_submissions&lesson_id=<?php echo $submission['lesson_id']; ?>" 
                           class="btn btn-sm btn-primary">
                            Grade Now
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- My Lessons & Activities -->
<div class="box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6); flex-wrap: wrap; gap: var(--space-4);">
        <h3 style="margin: 0;">📚 My Lessons & Activities</h3>
        <div style="display: flex; gap: var(--space-3); align-items: center;">
            <select id="filterType" onchange="filterLessons()" style="width: auto; padding: var(--space-2) var(--space-3);">
                <option value="all">All Content</option>
                <option value="lesson">Lessons Only</option>
                <option value="activity">Activities Only</option>
            </select>
        </div>
    </div>

    <?php if (empty($lessons)): ?>
        <div style="text-align: center; padding: var(--space-12); color: var(--gray-500);">
            <div style="font-size: 4rem; margin-bottom: var(--space-4);">📚</div>
            <h4 style="color: var(--gray-600);">No content created yet</h4>
            <p style="margin-bottom: var(--space-6);">Start creating engaging lessons and activities for your students!</p>
            <a href="index.php?page=teacher_create" class="btn btn-primary">
                ➕ Create Your First Content
            </a>
        </div>
    <?php else: ?>
        <div class="grid" id="lessonsGrid">
            <?php foreach($lessons as $l): 
                $lessonUrl = 'http://'.$_SERVER['HTTP_HOST'].'/public/index.php?page=view_lesson&id='.$l['id'];
                $hasSubmissions = $l['total_submissions'] > 0;
                $avgGrade = $l['average_grade'] ? round($l['average_grade'], 1) : null;
            ?>
            <div class="card lesson-card" data-type="<?php echo $l['is_activity'] ? 'activity' : 'lesson'; ?>">
                <!-- Content Type Badge -->
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--space-3);">
                    <span class="badge badge-<?php echo $l['is_activity'] ? 'info' : 'primary'; ?>">
                        <?php echo $l['is_activity'] ? '📝 Activity' : '📚 Lesson'; ?>
                    </span>
                    <div style="font-size: 0.75rem; color: var(--gray-500);">
                        📅 <?php echo date('M j, Y', strtotime($l['created_at'])); ?>
                    </div>
                </div>

                <h4><?php echo htmlspecialchars($l['title']); ?></h4>
                <p><?php echo nl2br(htmlspecialchars(substr($l['description'], 0, 150))); ?>
                   <?php if (strlen($l['description']) > 150) echo '...'; ?>
                </p>

                <!-- Statistics -->
                <?php if ($hasSubmissions): ?>
                    <div class="card-meta" style="margin-bottom: var(--space-4);">
                        <span>👥 <?php echo $l['total_submissions']; ?> submissions</span>
                        <?php if ($avgGrade): ?>
                            <span>📊 Avg: <?php echo $avgGrade; ?>%</span>
                        <?php endif; ?>
                        <?php if ($l['pending_grades'] > 0): ?>
                            <span class="badge badge-warning"><?php echo $l['pending_grades']; ?> pending</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="card-actions">
                    <a href="index.php?page=view_lesson&id=<?php echo $l['id']; ?>" class="btn btn-sm btn-secondary">
                        View
                    </a>
                    <a href="index.php?page=teacher_edit&id=<?php echo $l['id']; ?>" class="btn btn-sm btn-secondary">
                        Edit
                    </a>
                    <?php if ($hasSubmissions): ?>
                        <a href="index.php?page=teacher_submissions&lesson_id=<?php echo $l['id']; ?>" class="btn btn-sm btn-primary">
                            Submissions
                        </a>
                    <?php endif; ?>
                    <button onclick="showQR('<?php echo $lessonUrl; ?>', '<?php echo htmlspecialchars($l['title']); ?>')" 
                            class="btn btn-sm btn-secondary">
                        QR
                    </button>
                    <a href="index.php?page=teacher_delete&id=<?php echo $l['id']; ?>" 
                       onclick="return confirm('Are you sure you want to delete this <?php echo $l['is_activity'] ? 'activity' : 'lesson'; ?>? This action cannot be undone.')" 
                       class="btn btn-sm btn-error">
                        🗑️ Delete
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- QR Code Modal -->
<div id="qrModal" class="modal" style="display: none;">
    <div class="modal-content qr-modal-content">
        <div class="modal-header">
            <h3 style="margin: 0; display: flex; align-items: center; gap: var(--space-2);">
                <i class="fas fa-qrcode" style="color: var(--primary-green);"></i>
                QR Code Access
            </h3>
            <button onclick="closeQRModal()" class="btn btn-sm btn-ghost" style="padding: var(--space-2); border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="qrCodesContainer" class="qr-container">
                <!-- QR codes will be populated here -->
            </div>
        </div>
        <div class="modal-footer">
            <div class="qr-info">
                <i class="fas fa-info-circle" style="color: var(--info); margin-right: var(--space-2);"></i>
                <small style="color: var(--gray-600);">Scan this QR code with your mobile device to access the lesson</small>
            </div>
            <button onclick="closeQRModal()" class="btn btn-primary">Close</button>
        </div>
    </div>
</div>

<script>
function filterLessons() {
    const filter = document.getElementById('filterType').value;
    const cards = document.querySelectorAll('.lesson-card');

    cards.forEach(card => {
        const type = card.getAttribute('data-type');
        if (filter === 'all' || filter === type) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function generateAllQRCodes(btn) {
    if (confirm('This will generate QR codes for all your lessons. Continue?')) {
        // Show loading
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
        btn.disabled = true;

        // Get all lesson IDs from the page
        const lessonCards = document.querySelectorAll('.lesson-card');
        const lessonIds = Array.from(lessonCards).map(card => {
            const viewLink = card.querySelector('a[href*="view_lesson"]');
            if (viewLink) {
                const url = new URL(viewLink.href, window.location.origin);
                return url.searchParams.get('id');
            }
            return null;
        }).filter(id => id !== null);

        // Generate QR codes via AJAX
        fetch('index.php?page=generate_qr_codes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ lesson_ids: lessonIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show modal with QR codes
                showQRModal(data.qr_codes);
            } else {
                alert('Error generating QR codes: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error generating QR codes. Please try again.');
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
}

function showQR(url, title) {
    // Generate QR code client-side for single lesson
    const container = document.getElementById('qrCodesContainer');
    container.innerHTML = '';

    const qrItem = document.createElement('div');
    qrItem.className = 'qr-code-item';
    qrItem.innerHTML = `
        <div id="qr-code-display" style="text-align: center; margin-bottom: var(--space-3);"></div>
        <h4>${title}</h4>
        <div class="qr-actions">
            <button onclick="downloadQR('${url}', '${title}')" class="btn btn-sm btn-primary">Download QR Code</button>
        </div>
    `;
    container.appendChild(qrItem);

    // Generate QR code using QRCode.js
    new QRCode(document.getElementById('qr-code-display'), {
        text: url,
        width: 200,
        height: 200,
        colorDark: "#5b3e2b",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M
    });

    document.getElementById('qrModal').style.display = 'flex';
}

function downloadQR(url, title) {
    // Create a temporary canvas to generate downloadable QR code
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    canvas.width = 200;
    canvas.height = 200;

    // Generate QR code on canvas
    const qr = new QRCode(canvas, {
        text: url,
        width: 200,
        height: 200,
        colorDark: "#5b3e2b",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M
    });

    // Wait a bit for QR code to render, then download
    setTimeout(() => {
        canvas.toBlob(function(blob) {
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `qr_${title.replace(/[^a-zA-Z0-9]/g, '_')}.png`;
            link.click();
            URL.revokeObjectURL(link.href);
        });
    }, 100);
}

function showQRModal(qrCodes) {
    const container = document.getElementById('qrCodesContainer');
    container.innerHTML = '';
    container.classList.add('grid'); // Add grid class for bulk display

    qrCodes.forEach(qr => {
        const qrItem = document.createElement('div');
        qrItem.className = 'qr-code-item';
        qrItem.innerHTML = `
            <img src="${qr.qr_url}" alt="QR Code for ${qr.lesson_title}" />
            <h4>${qr.lesson_title}</h4>
            <div class="qr-actions">
                <a href="${qr.qr_url}" target="_blank" class="btn btn-sm btn-secondary">
                    <i class="fas fa-external-link-alt" style="margin-right: var(--space-1);"></i>View
                </a>
                <a href="${qr.qr_url}" download="qr_${qr.lesson_title.replace(/[^a-zA-Z0-9]/g, '_')}.png" class="btn btn-sm btn-primary">
                    <i class="fas fa-download" style="margin-right: var(--space-1);"></i>Download
                </a>
            </div>
        `;
        container.appendChild(qrItem);
    });

    document.getElementById('qrModal').style.display = 'flex';
}

function closeQRModal() {
    document.getElementById('qrModal').style.display = 'none';
    document.getElementById('qrCodesContainer').innerHTML = '';
}

// Close modal when clicking outside the modal content
document.getElementById('qrModal').addEventListener('click', function(event) {
    if (!event.target.closest('.modal-content')) {
        closeQRModal();
    }
});
</script>

<style>
/* Enhanced QR Modal Styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: auto;
    backdrop-filter: blur(2px);
}

.qr-modal-content {
    background: white;
    border-radius: var(--radius-lg);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.modal-header {
    padding: var(--space-5) var(--space-6);
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, var(--primary-light), #ffffff);
}

.modal-header h3 {
    color: var(--gray-900);
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

.btn-ghost {
    background: transparent;
    border: 1px solid var(--gray-300);
    color: var(--gray-600);
    transition: all 0.2s ease;
}

.btn-ghost:hover {
    background: var(--gray-100);
    border-color: var(--gray-400);
    color: var(--gray-800);
}

.modal-body {
    padding: var(--space-6);
    max-height: 60vh;
    overflow-y: auto;
}

.qr-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-4);
}

.qr-code-item {
    background: linear-gradient(135deg, var(--gray-50), #ffffff);
    padding: var(--space-5);
    border-radius: var(--radius-lg);
    text-align: center;
    border: 2px solid var(--gray-200);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 300px;
}

.qr-code-item #qr-code-display {
    margin-bottom: var(--space-4);
    display: inline-block;
    padding: var(--space-3);
    background: white;
    border-radius: var(--radius-md);
    border: 2px solid var(--primary-green);
    box-shadow: 0 4px 8px rgba(91, 62, 43, 0.2);
}

.qr-code-item h4 {
    margin-bottom: var(--space-3);
    color: var(--gray-900);
    font-size: 1.1rem;
    font-weight: 600;
}

.qr-actions {
    display: flex;
    gap: var(--space-3);
    justify-content: center;
    flex-wrap: wrap;
}

.qr-actions .btn {
    padding: var(--space-2) var(--space-4);
    border-radius: var(--radius-md);
    font-weight: 500;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.qr-actions .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.modal-footer {
    padding: var(--space-5) var(--space-6);
    border-top: 1px solid var(--gray-200);
    background: var(--gray-50);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--space-3);
}

.qr-info {
    display: flex;
    align-items: center;
    flex: 1;
    min-width: 200px;
}

.qr-info small {
    font-style: italic;
}

/* Bulk QR codes grid */
.qr-container.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--space-4);
    width: 100%;
}

.qr-container.grid .qr-code-item {
    max-width: none;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .qr-modal-content {
        width: 95%;
        margin: var(--space-4);
    }

    .modal-header,
    .modal-body,
    .modal-footer {
        padding: var(--space-4);
    }

    .qr-code-item {
        padding: var(--space-4);
    }

    .modal-footer {
        flex-direction: column;
        align-items: stretch;
    }

    .qr-info {
        text-align: center;
        justify-content: center;
    }
}
</style>

<?php include __DIR__.'/../common/footer.php'; ?>