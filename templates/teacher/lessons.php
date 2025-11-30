<?php
$page_title = "My Lessons & Activities";
include __DIR__.'/../common/header.php';
require_role('teacher');

$me = user();

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';

// Build query based on filters
$query = '
    SELECT l.*,
           COUNT(DISTINCT s.student_id) as total_submissions,
           AVG(s.grade) as average_grade,
           COUNT(DISTINCT CASE WHEN s.status = "submitted" THEN s.id END) as pending_grades
    FROM lessons l
    LEFT JOIN submissions s ON l.id = s.lesson_id
    WHERE l.teacher_id = ?
';

$params = [$me['id']];

if ($status_filter !== 'all') {
    $query .= ' AND l.status = ?';
    $params[] = $status_filter;
}

if ($type_filter !== 'all') {
    if ($type_filter === 'lesson') {
        $query .= ' AND l.is_activity = 0';
    } elseif ($type_filter === 'activity') {
        $query .= ' AND l.is_activity = 1';
    }
}

$query .= ' GROUP BY l.id ORDER BY l.created_at DESC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$lessons = $stmt->fetchAll();

// Get status counts for badges
$statusCounts = [
    'all' => 0,
    'draft' => 0,
    'published' => 0,
    'archived' => 0
];

foreach ($lessons as $lesson) {
    $statusCounts['all']++;
    $statusCounts[$lesson['status']]++;
}
?>

<!-- Page Header -->
<div class="box" style="background: linear-gradient(135deg, var(--primary-green), var(--primary-dark)); margin-bottom: var(--space-6);">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--space-4);">
        <div>
            <h2 style="color: white; margin-bottom: var(--space-2);">
                📚 My Lessons & Activities
            </h2>
            <p style="color: rgba(255, 255, 255, 0.9); margin-bottom: 0;">
                Manage your educational content and track student progress
            </p>
        </div>
        <div style="text-align: right;">
            <a href="index.php?page=teacher_create" class="btn btn-light">
                ➕ Create New Content
            </a>
        </div>
    </div>
</div>

<!-- Status Filter Tabs -->
<div class="box" style="margin-bottom: var(--space-6);">
    <div style="display: flex; gap: var(--space-2); flex-wrap: wrap; border-bottom: 1px solid var(--gray-200); padding-bottom: var(--space-4); margin-bottom: var(--space-4);">
        <a href="index.php?page=teacher_lessons&status=all&type=<?php echo $type_filter; ?>"
           class="tab-link <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
            All (<?php echo $statusCounts['all']; ?>)
        </a>
        <a href="index.php?page=teacher_lessons&status=draft&type=<?php echo $type_filter; ?>"
           class="tab-link <?php echo $status_filter === 'draft' ? 'active' : ''; ?>">
            Draft (<?php echo $statusCounts['draft']; ?>)
        </a>
        <a href="index.php?page=teacher_lessons&status=published&type=<?php echo $type_filter; ?>"
           class="tab-link <?php echo $status_filter === 'published' ? 'active' : ''; ?>">
            Published (<?php echo $statusCounts['published']; ?>)
        </a>
        <a href="index.php?page=teacher_lessons&status=archived&type=<?php echo $type_filter; ?>"
           class="tab-link <?php echo $status_filter === 'archived' ? 'active' : ''; ?>">
            Archived (<?php echo $statusCounts['archived']; ?>)
        </a>
    </div>

    <!-- Type Filter -->
    <div style="display: flex; gap: var(--space-3); align-items: center;">
        <label style="font-weight: 500; color: var(--gray-700);">Filter by type:</label>
        <select id="typeFilter" onchange="changeTypeFilter(this.value)" style="width: auto; padding: var(--space-2) var(--space-3);">
            <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>All Content</option>
            <option value="lesson" <?php echo $type_filter === 'lesson' ? 'selected' : ''; ?>>Lessons Only</option>
            <option value="activity" <?php echo $type_filter === 'activity' ? 'selected' : ''; ?>>Activities Only</option>
        </select>
    </div>
</div>

<!-- Lessons Grid -->
<?php if (empty($lessons)): ?>
    <div class="box">
        <div style="text-align: center; padding: var(--space-12); color: var(--gray-500);">
            <div style="font-size: 4rem; margin-bottom: var(--space-4);">
                <?php
                if ($status_filter === 'draft') echo '📝';
                elseif ($status_filter === 'published') echo '📚';
                elseif ($status_filter === 'archived') echo '📦';
                else echo '📚';
                ?>
            </div>
            <h4 style="color: var(--gray-600);">
                <?php
                if ($status_filter === 'draft') echo 'No draft content found';
                elseif ($status_filter === 'published') echo 'No published content found';
                elseif ($status_filter === 'archived') echo 'No archived content found';
                else echo 'No content found';
                ?>
            </h4>
            <p style="margin-bottom: var(--space-6);">
                <?php
                if ($status_filter === 'all') echo 'Start creating engaging lessons and activities for your students!';
                else echo 'Try adjusting your filters or create new content.';
                ?>
            </p>
            <?php if ($status_filter === 'all'): ?>
                <a href="index.php?page=teacher_create" class="btn btn-primary">
                    ➕ Create Your First Content
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="grid" id="lessonsGrid">
        <?php foreach($lessons as $l):
            $lessonUrl = APP_URL.'/index.php?page=view_lesson&id='.$l['id'];
            $hasSubmissions = $l['total_submissions'] > 0;
            $avgGrade = $l['average_grade'] ? round($l['average_grade'], 1) : null;
        ?>
        <div class="card lesson-card" data-type="<?php echo $l['is_activity'] ? 'activity' : 'lesson'; ?>" data-status="<?php echo $l['status']; ?>">
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

            <!-- Status Badge -->
            <div style="margin-bottom: var(--space-3);">
                <span class="badge badge-<?php
                    if ($l['status'] === 'published') echo 'success';
                    elseif ($l['status'] === 'draft') echo 'warning';
                    elseif ($l['status'] === 'archived') echo 'secondary';
                    else echo 'secondary';
                ?>">
                    <?php echo ucfirst($l['status']); ?>
                </span>
            </div>

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
function changeTypeFilter(type) {
    const url = new URL(window.location);
    url.searchParams.set('type', type);
    window.location.href = url.toString();
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
    // Get the canvas from the already rendered QR code in the modal
    const qrDisplay = document.getElementById('qr-code-display');
    const canvas = qrDisplay ? qrDisplay.querySelector('canvas') : null;

    if (canvas) {
        canvas.toBlob(function(blob) {
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `qr_${title.replace(/[^a-zA-Z0-9]/g, '_')}.png`;
            link.click();
            URL.revokeObjectURL(link.href);
        });
    } else {
        console.error('QR code canvas not found');
        alert('Error: QR code not ready for download. Please try again.');
    }
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

