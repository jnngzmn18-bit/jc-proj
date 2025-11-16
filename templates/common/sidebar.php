<?php
/**
 * Sidebar Navigation Component - Bootstrap Enhanced
 * Renders role-based navigation sidebar for the LMS
 */

// Ensure user is logged in to show sidebar
if (!is_logged_in()) {
    return;
}

$user = user();
$current_page = $_GET['page'] ?? 'dashboard';

// Define navigation items based on user role
$nav_items = [];

if ($user['role'] === 'admin') {
    $nav_items = [
        ['href' => 'index.php?page=dashboard', 'icon' => 'bi-speedometer2', 'text' => 'Dashboard'],
        ['href' => 'index.php?page=users', 'icon' => 'bi-people', 'text' => 'Users'],
        ['href' => 'index.php?page=lessons', 'icon' => 'bi-journal-bookmark', 'text' => 'All Lessons'],
        ['href' => 'index.php?page=reports', 'icon' => 'bi-bar-chart-line', 'text' => 'Reports'],
        ['href' => 'index.php?page=settings', 'icon' => 'bi-gear', 'text' => 'Settings'],
    ];
} elseif ($user['role'] === 'teacher') {
    $nav_items = [
        ['href' => 'index.php?page=dashboard', 'icon' => 'bi-speedometer2', 'text' => 'Dashboard'],
        ['href' => 'index.php?page=teacher_create', 'icon' => 'bi-plus-circle', 'text' => 'Create Content'],
        ['href' => 'index.php?page=teacher_lessons', 'icon' => 'bi-journal-bookmark', 'text' => 'My Lessons'],
        ['href' => 'index.php?page=teacher_submissions', 'icon' => 'bi-clipboard-check', 'text' => 'Submissions'],
        ['href' => 'javascript:void(0)', 'icon' => 'bi-bar-chart-line', 'text' => 'Grades', 'onclick' => 'showComingSoonAlert()'],
    ];
} else { // student
    $nav_items = [
        ['href' => 'index.php?page=dashboard', 'icon' => 'bi-speedometer2', 'text' => 'Dashboard'],
        ['href' => 'index.php?page=lessons', 'icon' => 'bi-journal-bookmark', 'text' => 'Lessons'],
        ['href' => 'index.php?page=student_grades', 'icon' => 'bi-bar-chart-line', 'text' => 'My Grades'],
        ['href' => 'index.php?page=student_activities', 'icon' => 'bi-clipboard-check', 'text' => 'Activities'],
    ];
}
?>

<!-- Sidebar Toggle Button -->
<button id="sidebar-toggle" class="btn btn-outline-success d-lg-none" aria-label="Toggle Sidebar" title="Toggle Navigation">
    <i class="fas fa-bars toggle-icon"></i>
</button>

<!-- Sidebar Overlay for Mobile -->
<div id="sidebar-overlay"></div>

<!-- Sidebar Navigation -->
<div class="sidebar">
    <!-- Logo Section -->
    <div class="logo mb-4">
        <i class="bi bi-leaf text-success"></i>
        <span class="fw-bold">EnviLearn</span>
    </div>

    <!-- Navigation Menu -->
    <nav class="nav flex-column w-100">
        <?php foreach ($nav_items as $item): ?>
            <a class="nav-link <?php echo (strpos($item['href'], "page=$current_page") !== false) ? 'active' : ''; ?>"
               href="<?php echo htmlspecialchars($item['href']); ?>"
               <?php if (isset($item['onclick'])): ?>onclick="<?php echo htmlspecialchars($item['onclick']); ?>"<?php endif; ?>>
                <i class="bi <?php echo htmlspecialchars($item['icon']); ?>"></i>
                <span><?php echo htmlspecialchars($item['text']); ?></span>
            </a>
        <?php endforeach; ?>
        
        <!-- Divider -->
        <hr class="my-3" style="border-color: rgba(91, 62, 43, 0.2);">

        <!-- Profile Link -->
        <a class="nav-link <?php echo ($current_page === 'profile') ? 'active' : ''; ?>" href="index.php?page=profile">
            <i class="bi bi-person"></i>
            <span>Profile</span>
        </a>

        <!-- Logout Link -->
        <a class="nav-link text-danger" href="index.php?page=logout" style="background: rgba(220, 53, 69, 0.1);">
            <i class="bi bi-box-arrow-right"></i> 
            <span>Logout</span>
        </a>
    </nav>

    <!-- Footer -->
    <div class="footer mt-auto">
        <small class="text-muted">© 2025 EnviLearn</small>
    </div>
</div>

<script>
function showComingSoonAlert() {
    alert('Coming soon! This feature is currently under development.');
}
</script>