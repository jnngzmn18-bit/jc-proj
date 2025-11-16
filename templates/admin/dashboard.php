<?php 
$page_title = "Admin Dashboard";
include __DIR__ . '/../common/header.php'; 
require_role('admin');

$me = user();

// Get system statistics
try {
    // User statistics
    $userStats = $pdo->query("
        SELECT 
            role,
            COUNT(*) as count,
            COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as active_week,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_month
        FROM users 
        WHERE status = 'active'
        GROUP BY role
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Content statistics
    $contentStats = $pdo->query("
        SELECT 
            COUNT(*) as total_lessons,
            COUNT(CASE WHEN is_activity = 1 THEN 1 END) as total_activities,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_content_month
        FROM lessons
    ")->fetch();

    // Submission statistics
    $submissionStats = $pdo->query("
        SELECT 
            COUNT(*) as total_submissions,
            COUNT(CASE WHEN status = 'submitted' THEN 1 END) as pending_grades,
            COUNT(CASE WHEN submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as submissions_week
        FROM submissions
    ")->fetch();

    // Get recent users
    $recentUsers = $pdo->query("
        SELECT * FROM users 
        WHERE status = 'active'
        ORDER BY created_at DESC 
        LIMIT 10
    ")->fetchAll();

    // Get recent lessons
    $recentLessons = $pdo->query("
        SELECT l.*, u.name AS teacher_name 
        FROM lessons l 
        JOIN users u ON l.teacher_id = u.id 
        ORDER BY l.created_at DESC 
        LIMIT 10
    ")->fetchAll();

    // Get recent login history
    $recentLogins = $pdo->query("
        SELECT lh.*, u.name AS user_name, u.role
        FROM login_history lh 
        LEFT JOIN users u ON lh.user_id = u.id 
        ORDER BY lh.login_at DESC 
        LIMIT 15
    ")->fetchAll();

    // Get recent access logs
    $recentAccess = $pdo->query("
        SELECT a.*, u.name AS user_name, u.role
        FROM access_logs a 
        LEFT JOIN users u ON a.user_id = u.id 
        ORDER BY a.accessed_at DESC 
        LIMIT 15
    ")->fetchAll();

} catch (Exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $userStats = [];
    $contentStats = ['total_lessons' => 0, 'total_activities' => 0, 'new_content_month' => 0];
    $submissionStats = ['total_submissions' => 0, 'pending_grades' => 0, 'submissions_week' => 0];
    $recentUsers = [];
    $recentLessons = [];
    $recentLogins = [];
    $recentAccess = [];
}

// Process user stats for display
$totalUsers = array_sum(array_column($userStats, 'count'));
$activeUsers = array_sum(array_column($userStats, 'active_week'));
$newUsers = array_sum(array_column($userStats, 'new_month'));

$usersByRole = [];
foreach ($userStats as $stat) {
    $usersByRole[$stat['role']] = $stat;
}
?>

<!-- Welcome Section -->
<div class="box" style="background: linear-gradient(135deg, var(--primary-dark), var(--gray-800)); color: white; margin-bottom: var(--space-8);">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--space-4);">
        <div>
            <h2 style="color: white; margin-bottom: var(--space-2);">
                ⚙️ Admin Dashboard
            </h2>
            <p style="color: rgba(255,255,255,0.9); margin-bottom: 0;">
                System overview and management controls
            </p>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 2rem; margin-bottom: var(--space-2);">📊</div>
            <div style="font-size: 0.875rem; color: rgba(255,255,255,0.8);">
                <?php echo date('l, F j, Y'); ?>
            </div>
        </div>
    </div>
</div>

<!-- System Statistics -->
<div class="grid" style="margin-bottom: var(--space-8);">
    <!-- Total Users -->
    <div class="card" style="background: linear-gradient(135deg, var(--primary-blue), #1d4ed8); color: white; border: none;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="color: white; font-size: 2rem; margin-bottom: var(--space-1);"><?php echo $totalUsers; ?></h3>
                <p style="color: rgba(255,255,255,0.9); margin: 0; font-weight: 500;">Total Users</p>
                <p style="color: rgba(255,255,255,0.7); margin: 0; font-size: 0.75rem;">
                    <?php echo $activeUsers; ?> active this week
                </p>
            </div>
            <div style="font-size: 2.5rem; opacity: 0.8;">👥</div>
        </div>
    </div>

    <!-- Total Content -->
    <div class="card" style="background: linear-gradient(135deg, var(--success), #059669); color: white; border: none;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="color: white; font-size: 2rem; margin-bottom: var(--space-1);">
                    <?php echo $contentStats['total_lessons']; ?>
                </h3>
                <p style="color: rgba(255,255,255,0.9); margin: 0; font-weight: 500;">Total Lessons</p>
                <p style="color: rgba(255,255,255,0.7); margin: 0; font-size: 0.75rem;">
                    <?php echo $contentStats['total_activities']; ?> activities
                </p>
            </div>
            <div style="font-size: 2.5rem; opacity: 0.8;">📚</div>
        </div>
    </div>

    <!-- Pending Submissions -->
    <div class="card" style="background: linear-gradient(135deg, var(--warning), #d97706); color: white; border: none;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="color: white; font-size: 2rem; margin-bottom: var(--space-1);">
                    <?php echo $submissionStats['pending_grades']; ?>
                </h3>
                <p style="color: rgba(255,255,255,0.9); margin: 0; font-weight: 500;">Pending Grades</p>
                <p style="color: rgba(255,255,255,0.7); margin: 0; font-size: 0.75rem;">
                    <?php echo $submissionStats['submissions_week']; ?> this week
                </p>
            </div>
            <div style="font-size: 2.5rem; opacity: 0.8;">⏳</div>
        </div>
    </div>
</div>

<!-- User Management Section -->
<div class="box" style="margin-bottom: var(--space-8);">
    <h3 style="margin-bottom: var(--space-6);">👥 User Management</h3>
    
    <!-- User Role Statistics -->
    <div class="grid-2" style="margin-bottom: var(--space-6);">
        <?php foreach (['admin', 'teacher', 'student'] as $role): ?>
            <?php $roleData = $usersByRole[$role] ?? ['count' => 0, 'active_week' => 0, 'new_month' => 0]; ?>
            <div class="card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-3);">
                    <h4 style="margin: 0; text-transform: capitalize;">
                        <?php echo $role === 'admin' ? '⚙️' : ($role === 'teacher' ? '👨‍🏫' : '👨‍🎓'); ?>
                        <?php echo ucfirst($role); ?>s
                    </h4>
                    <span class="badge badge-primary"><?php echo $roleData['count']; ?></span>
                </div>
                <div style="font-size: 0.875rem; color: var(--gray-600);">
                    <div>Active this week: <strong><?php echo $roleData['active_week']; ?></strong></div>
                    <div>New this month: <strong><?php echo $roleData['new_month']; ?></strong></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent Users -->
    <h4>Recent Users</h4>
    <?php if (empty($recentUsers)): ?>
        <p style="color: var(--gray-500); text-align: center; padding: var(--space-8);">No users found.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="background: var(--gray-50); border-bottom: 2px solid var(--gray-200);">
                        <th style="padding: var(--space-3); text-align: left; font-weight: 600;">Name</th>
                        <th style="padding: var(--space-3); text-align: left; font-weight: 600;">Email</th>
                        <th style="padding: var(--space-3); text-align: center; font-weight: 600;">Role</th>
                        <th style="padding: var(--space-3); text-align: center; font-weight: 600;">Status</th>
                        <th style="padding: var(--space-3); text-align: center; font-weight: 600;">Joined</th>
                        <th style="padding: var(--space-3); text-align: center; font-weight: 600;">Last Login</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUsers as $user): ?>
                        <tr style="border-bottom: 1px solid var(--gray-200);">
                            <td style="padding: var(--space-3); font-weight: 500;">
                                <?php echo htmlspecialchars($user['name']); ?>
                            </td>
                            <td style="padding: var(--space-3); color: var(--gray-600);">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </td>
                            <td style="padding: var(--space-3); text-align: center;">
                                <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'error' : ($user['role'] === 'teacher' ? 'warning' : 'info'); ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td style="padding: var(--space-3); text-align: center;">
                                <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td style="padding: var(--space-3); text-align: center; color: var(--gray-600);">
                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                            </td>
                            <td style="padding: var(--space-3); text-align: center; color: var(--gray-600);">
                                <?php echo $user['last_login'] ? date('M j, g:i A', strtotime($user['last_login'])) : 'Never'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Content Overview -->
<div class="box" style="margin-bottom: var(--space-8);">
    <h3 style="margin-bottom: var(--space-6);">📚 Content Overview</h3>
    
    <?php if (empty($recentLessons)): ?>
        <p style="color: var(--gray-500); text-align: center; padding: var(--space-8);">No lessons found.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($recentLessons as $lesson): ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--space-3);">
                        <span class="badge badge-<?php echo $lesson['is_activity'] ? 'info' : 'primary'; ?>">
                            <?php echo $lesson['is_activity'] ? '📝 Activity' : '📚 Lesson'; ?>
                        </span>
                        <div style="font-size: 0.75rem; color: var(--gray-500);">
                            <?php echo date('M j, Y', strtotime($lesson['created_at'])); ?>
                        </div>
                    </div>
                    
                    <h4><?php echo htmlspecialchars($lesson['title']); ?></h4>
                    <p style="color: var(--gray-600); font-size: 0.875rem;">
                        By <?php echo htmlspecialchars($lesson['teacher_name']); ?>
                    </p>
                    <p><?php echo nl2br(htmlspecialchars(substr($lesson['description'], 0, 100))); ?>
                       <?php if (strlen($lesson['description']) > 100) echo '...'; ?>
                    </p>
                    
                    <div class="card-actions">
                        <a href="index.php?page=view_lesson&id=<?php echo $lesson['id']; ?>" class="btn btn-sm btn-secondary">
                            👁️ View
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- System Activity -->
<div class="grid-2">
    <!-- Login History -->
    <div class="box">
        <h3 style="margin-bottom: var(--space-6);">🔐 Recent Logins</h3>
        
        <?php if (empty($recentLogins)): ?>
            <p style="color: var(--gray-500); text-align: center; padding: var(--space-6);">No login history found.</p>
        <?php else: ?>
            <div style="max-height: 400px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                    <thead style="position: sticky; top: 0; background: white;">
                        <tr style="background: var(--gray-50); border-bottom: 1px solid var(--gray-200);">
                            <th style="padding: var(--space-2); text-align: left; font-weight: 600;">User</th>
                            <th style="padding: var(--space-2); text-align: center; font-weight: 600;">Login</th>
                            <th style="padding: var(--space-2); text-align: center; font-weight: 600;">Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLogins as $login): ?>
                            <tr style="border-bottom: 1px solid var(--gray-100);">
                                <td style="padding: var(--space-2);">
                                    <div style="font-weight: 500;">
                                        <?php echo htmlspecialchars($login['user_name'] ?? 'Unknown'); ?>
                                    </div>
                                    <?php if ($login['role']): ?>
                                        <div style="font-size: 0.75rem; color: var(--gray-500);">
                                            <?php echo ucfirst($login['role']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: var(--space-2); text-align: center; color: var(--gray-600);">
                                    <?php echo date('M j, g:i A', strtotime($login['login_at'])); ?>
                                </td>
                                <td style="padding: var(--space-2); text-align: center; color: var(--gray-600);">
                                    <?php 
                                    if ($login['duration_seconds']) {
                                        $minutes = floor($login['duration_seconds'] / 60);
                                        echo $minutes > 0 ? $minutes . 'm' : '<1m';
                                    } else {
                                        echo 'Active';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Access Logs -->
    <div class="box">
        <h3 style="margin-bottom: var(--space-6);">📋 Recent Access</h3>
        
        <?php if (empty($recentAccess)): ?>
            <p style="color: var(--gray-500); text-align: center; padding: var(--space-6);">No access logs found.</p>
        <?php else: ?>
            <div style="max-height: 400px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                    <thead style="position: sticky; top: 0; background: white;">
                        <tr style="background: var(--gray-50); border-bottom: 1px solid var(--gray-200);">
                            <th style="padding: var(--space-2); text-align: left; font-weight: 600;">User</th>
                            <th style="padding: var(--space-2); text-align: left; font-weight: 600;">Page</th>
                            <th style="padding: var(--space-2); text-align: center; font-weight: 600;">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAccess as $access): ?>
                            <tr style="border-bottom: 1px solid var(--gray-100);">
                                <td style="padding: var(--space-2);">
                                    <?php echo htmlspecialchars($access['user_name'] ?? 'Guest'); ?>
                                    <?php if ($access['role']): ?>
                                        <div style="font-size: 0.75rem; color: var(--gray-500);">
                                            <?php echo ucfirst($access['role']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: var(--space-2); color: var(--gray-600);">
                                    <?php echo htmlspecialchars($access['page']); ?>
                                </td>
                                <td style="padding: var(--space-2); text-align: center; color: var(--gray-600);">
                                    <?php echo date('M j, g:i A', strtotime($access['accessed_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../common/footer.php'; ?>