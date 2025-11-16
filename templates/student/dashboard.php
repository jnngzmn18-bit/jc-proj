<?php 
$page_title = "Student Dashboard";
include __DIR__.'/../common/header.php'; 
require_role('student');

$me = user();

// Get all available lessons with submission status
$stmt = $pdo->prepare('
    SELECT l.*, u.name AS teacher_name,
           s.id as submission_id, s.grade, s.feedback, s.status as submission_status,
           s.submitted_at
    FROM lessons l 
    JOIN users u ON l.teacher_id = u.id 
    LEFT JOIN submissions s ON l.id = s.lesson_id AND s.student_id = ?
    WHERE l.status = "published"
    ORDER BY l.created_at DESC
');
$stmt->execute([$me['id']]);
$lessons = $stmt->fetchAll();

// Calculate student statistics
$totalLessons = count($lessons);
$completedSubmissions = count(array_filter($lessons, function($l) { return $l['submission_id']; }));
$gradedSubmissions = count(array_filter($lessons, function($l) { return $l['grade'] !== null; }));
$averageGrade = 0;

if ($gradedSubmissions > 0) {
    $grades = array_filter(array_column($lessons, 'grade'), function($grade) { return $grade !== null; });
    $averageGrade = round(array_sum($grades) / count($grades), 1);
}

// Get recent grades
$recentGradesStmt = $pdo->prepare('
    SELECT s.*, l.title as lesson_title, l.is_activity
    FROM submissions s
    JOIN lessons l ON s.lesson_id = l.id
    WHERE s.student_id = ? AND s.grade IS NOT NULL
    ORDER BY s.graded_at DESC
    LIMIT 5
');
$recentGradesStmt->execute([$me['id']]);
$recentGrades = $recentGradesStmt->fetchAll();
?>

<!-- Welcome Section -->
<div class="box" style="background: linear-gradient(135deg, var(--primary-green), var(--info)); color: white; margin-bottom: var(--space-8);">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--space-4);">
        <div>
            <h2 style="color: white; margin-bottom: var(--space-2);">
                👨‍🎓 Welcome back, <?php echo htmlspecialchars($me['name']); ?>!
            </h2>
            <p style="color: rgba(255,255,255,0.9); margin-bottom: 0;">
                Continue your environmental science learning journey
            </p>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 2rem; margin-bottom: var(--space-2);">🌍</div>
            <div style="font-size: 0.875rem; color: rgba(255,255,255,0.8);">
                <?php echo date('l, F j, Y'); ?>
            </div>
        </div>
    </div>
</div>

<!-- Progress Stats -->
<div class="grid" style="margin-bottom: var(--space-8);">
    <div class="card" style="background: linear-gradient(135deg, var(--primary-blue), #1d4ed8); color: white; border: none;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="color: white; font-size: 2rem; margin-bottom: var(--space-1);"><?php echo $totalLessons; ?></h3>
                <p style="color: rgba(255,255,255,0.9); margin: 0; font-weight: 500;">Available Lessons</p>
            </div>
            <div style="font-size: 2.5rem; opacity: 0.8;">📚</div>
        </div>
    </div>

    <div class="card" style="background: linear-gradient(135deg, var(--success), #059669); color: white; border: none;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="color: white; font-size: 2rem; margin-bottom: var(--space-1);"><?php echo $completedSubmissions; ?></h3>
                <p style="color: rgba(255,255,255,0.9); margin: 0; font-weight: 500;">Completed</p>
            </div>
            <div style="font-size: 2.5rem; opacity: 0.8;">✅</div>
        </div>
    </div>

    <div class="card" style="background: linear-gradient(135deg, var(--warning), #d97706); color: white; border: none;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="color: white; font-size: 2rem; margin-bottom: var(--space-1);">
                    <?php echo $averageGrade > 0 ? $averageGrade . '%' : 'N/A'; ?>
                </h3>
                <p style="color: rgba(255,255,255,0.9); margin: 0; font-weight: 500;">Average Grade</p>
            </div>
            <div style="font-size: 2.5rem; opacity: 0.8;">📊</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="box" style="margin-bottom: var(--space-8);">
    <h3 style="margin-bottom: var(--space-6);">🚀 Quick Actions</h3>
    <div style="display: flex; gap: var(--space-4); flex-wrap: wrap;">
        <a href="index.php?page=student_grades" class="btn btn-primary">
            📊 View My Grades
        </a>
        <a href="#lessons-section" class="btn btn-secondary" onclick="scrollToLessons()">
            📚 Browse Lessons
        </a>
        <?php if ($completedSubmissions < $totalLessons): ?>
            <button onclick="showIncompleteOnly()" class="btn btn-secondary">
                📝 Show Incomplete Only
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Grades (if any) -->
<?php if (!empty($recentGrades)): ?>
<div class="box" style="margin-bottom: var(--space-8);">
    <h3 style="margin-bottom: var(--space-6);">🎯 Recent Grades</h3>
    <div style="display: grid; gap: var(--space-4);">
        <?php foreach ($recentGrades as $grade): ?>
            <div style="padding: var(--space-4); border: 1px solid var(--gray-200); border-radius: var(--radius-lg); background: var(--gray-50);">
                <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: var(--space-3);">
                    <div style="flex-grow: 1;">
                        <h4 style="margin-bottom: var(--space-2); color: var(--gray-900);">
                            <?php echo htmlspecialchars($grade['lesson_title']); ?>
                            <span class="badge badge-<?php echo $grade['is_activity'] ? 'info' : 'primary'; ?>">
                                <?php echo $grade['is_activity'] ? 'Activity' : 'Lesson'; ?>
                            </span>
                        </h4>
                        <?php if ($grade['feedback']): ?>
                            <p style="margin-bottom: var(--space-2); color: var(--gray-600); font-style: italic;">
                                "<?php echo htmlspecialchars(substr($grade['feedback'], 0, 100)); ?>
                                <?php echo strlen($grade['feedback']) > 100 ? '...' : ''; ?>"
                            </p>
                        <?php endif; ?>
                        <p style="margin: 0; font-size: 0.875rem; color: var(--gray-500);">
                            📅 Graded <?php echo date('M j, Y', strtotime($grade['graded_at'])); ?>
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.5rem; font-weight: 600; color: <?php 
                            echo $grade['grade'] >= 90 ? 'var(--success)' : 
                                ($grade['grade'] >= 80 ? 'var(--info)' : 
                                ($grade['grade'] >= 70 ? 'var(--warning)' : 'var(--error)')); 
                        ?>;">
                            <?php echo $grade['grade']; ?>%
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Available Lessons & Activities -->
<div class="box" id="lessons-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6); flex-wrap: wrap; gap: var(--space-4);">
        <h3 style="margin: 0;">📚 Available Lessons & Activities</h3>
        <div style="display: flex; gap: var(--space-3); align-items: center;">
            <select id="filterType" onchange="filterContent()" style="width: auto; padding: var(--space-2) var(--space-3);">
                <option value="all">All Content</option>
                <option value="lesson">Lessons Only</option>
                <option value="activity">Activities Only</option>
                <option value="incomplete">Incomplete Only</option>
                <option value="graded">Graded Only</option>
            </select>
        </div>
    </div>

    <?php if (empty($lessons)): ?>
        <div style="text-align: center; padding: var(--space-12); color: var(--gray-500);">
            <div style="font-size: 4rem; margin-bottom: var(--space-4);">📚</div>
            <h4 style="color: var(--gray-600);">No lessons available yet</h4>
            <p>Check back soon for new environmental science content!</p>
        </div>
    <?php else: ?>
        <div class="grid" id="contentGrid">
            <?php foreach($lessons as $l): 
                $lessonUrl = 'http://'.$_SERVER['HTTP_HOST'].'/public/index.php?page=view_lesson&id='.$l['id'];
                $isCompleted = $l['submission_id'] !== null;
                $isGraded = $l['grade'] !== null;
                $statusClass = $isGraded ? 'success' : ($isCompleted ? 'warning' : 'info');
                $statusText = $isGraded ? 'Graded' : ($isCompleted ? 'Submitted' : 'Not Started');
            ?>
            <div class="card content-card" 
                 data-type="<?php echo $l['is_activity'] ? 'activity' : 'lesson'; ?>"
                 data-status="<?php echo $isGraded ? 'graded' : ($isCompleted ? 'completed' : 'incomplete'); ?>">
                
                <!-- Header with status -->
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--space-3);">
                    <span class="badge badge-<?php echo $l['is_activity'] ? 'info' : 'primary'; ?>">
                        <?php echo $l['is_activity'] ? '📝 Activity' : '📚 Lesson'; ?>
                    </span>
                    <span class="badge badge-<?php echo $statusClass; ?>">
                        <?php echo $statusText; ?>
                    </span>
                </div>

                <h4><?php echo htmlspecialchars($l['title']); ?></h4>
                <p><?php echo nl2br(htmlspecialchars(substr($l['description'], 0, 120))); ?>
                   <?php if (strlen($l['description']) > 120) echo '...'; ?>
                </p>

                <!-- Teacher and Grade Info -->
                <div class="card-meta" style="margin-bottom: var(--space-4);">
                    <span>👨‍🏫 <?php echo htmlspecialchars($l['teacher_name']); ?></span>
                    <?php if ($isGraded): ?>
                        <span style="color: <?php 
                            echo $l['grade'] >= 90 ? 'var(--success)' : 
                                ($l['grade'] >= 80 ? 'var(--info)' : 
                                ($l['grade'] >= 70 ? 'var(--warning)' : 'var(--error)')); 
                        ?>;">
                            📊 Grade: <?php echo $l['grade']; ?>%
                        </span>
                    <?php elseif ($isCompleted): ?>
                        <span style="color: var(--warning);">⏳ Awaiting Grade</span>
                    <?php endif; ?>
                </div>

                <div class="card-actions">
                    <a href="index.php?page=view_lesson&id=<?php echo $l['id']; ?>" class="btn btn-sm btn-primary">
                        View
                    </a>
                    
                    <?php if ($l['is_activity'] && !$isCompleted): ?>
                        <a href="index.php?page=submit_activity&id=<?php echo $l['id']; ?>" class="btn btn-sm btn-success">
                            Submit
                        </a>
                    <?php elseif ($l['is_activity'] && $isCompleted): ?>
                        <a href="index.php?page=submit_activity&id=<?php echo $l['id']; ?>" class="btn btn-sm btn-secondary">
                            View Submission
                        </a>
                    <?php endif; ?>
                    
                    <button onclick="showQR('<?php echo $lessonUrl; ?>', '<?php echo htmlspecialchars($l['title']); ?>')" 
                            class="btn btn-sm btn-secondary">
                        QR Code
                    </button>
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

<script>
function filterContent() {
    const filter = document.getElementById('filterType').value;
    const cards = document.querySelectorAll('.content-card');

    cards.forEach(card => {
        const type = card.getAttribute('data-type');
        const status = card.getAttribute('data-status');
        let show = false;

        switch(filter) {
            case 'all':
                show = true;
                break;
            case 'lesson':
                show = type === 'lesson';
                break;
            case 'activity':
                show = type === 'activity';
                break;
            case 'incomplete':
                show = status === 'incomplete';
                break;
            case 'graded':
                show = status === 'graded';
                break;
        }

        card.style.display = show ? 'flex' : 'none';
    });
}

function scrollToLessons() {
    document.getElementById('lessons-section').scrollIntoView({
        behavior: 'smooth'
    });
}

function showIncompleteOnly() {
    document.getElementById('filterType').value = 'incomplete';
    filterContent();
    scrollToLessons();
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

<?php include __DIR__.'/../common/footer.php'; ?>