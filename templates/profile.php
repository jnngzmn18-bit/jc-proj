<?php
$page_title = "My Profile";
include __DIR__.'/common/header.php';
require_login();

$user = user();
$user_id = $user['id'];

// Get additional user statistics based on role
$stats = [];
$recent_activity = [];

if ($user['role'] === 'teacher') {
    // Teacher statistics
    $stmt = $pdo->prepare('
        SELECT
            COUNT(DISTINCT l.id) as total_lessons,
            COUNT(DISTINCT CASE WHEN l.is_activity = 1 THEN l.id END) as total_activities,
            COUNT(DISTINCT s.id) as total_submissions,
            COUNT(DISTINCT CASE WHEN s.status = "submitted" THEN s.id END) as pending_grades,
            AVG(s.grade) as avg_grade
        FROM lessons l
        LEFT JOIN submissions s ON l.id = s.lesson_id
        WHERE l.teacher_id = ?
    ');
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();

    // Recent activity
    $stmt = $pdo->prepare('
        SELECT l.title, l.created_at, "lesson_created" as type
        FROM lessons l
        WHERE l.teacher_id = ?
        UNION ALL
        SELECT l.title, s.graded_at, "submission_graded" as type
        FROM submissions s
        JOIN lessons l ON s.lesson_id = l.id
        WHERE l.teacher_id = ? AND s.graded_at IS NOT NULL
        ORDER BY created_at DESC
        LIMIT 5
    ');
    $stmt->execute([$user_id, $user_id]);
    $recent_activity = $stmt->fetchAll();

} elseif ($user['role'] === 'student') {
    // Student statistics
    $stmt = $pdo->prepare('
        SELECT
            COUNT(DISTINCT s.id) as total_submissions,
            COUNT(DISTINCT CASE WHEN s.grade IS NOT NULL THEN s.id END) as graded_submissions,
            AVG(s.grade) as avg_grade,
            COUNT(DISTINCT l.id) as total_lessons_viewed
        FROM submissions s
        LEFT JOIN lessons l ON s.lesson_id = l.id
        WHERE s.student_id = ?
    ');
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();

    // Recent activity
    $stmt = $pdo->prepare('
        SELECT l.title, s.submitted_at as activity_date, "submission_made" as type
        FROM submissions s
        JOIN lessons l ON s.lesson_id = l.id
        WHERE s.student_id = ?
        UNION ALL
        SELECT l.title, s.graded_at, "grade_received" as type
        FROM submissions s
        JOIN lessons l ON s.lesson_id = l.id
        WHERE s.student_id = ? AND s.graded_at IS NOT NULL
        ORDER BY activity_date DESC
        LIMIT 5
    ');
    $stmt->execute([$user_id, $user_id]);
    $recent_activity = $stmt->fetchAll();

} elseif ($user['role'] === 'admin') {
    // Admin statistics
    $stmt = $pdo->prepare('
        SELECT
            (SELECT COUNT(*) FROM users WHERE role = "teacher") as total_teachers,
            (SELECT COUNT(*) FROM users WHERE role = "student") as total_students,
            (SELECT COUNT(*) FROM lessons) as total_lessons,
            (SELECT COUNT(*) FROM submissions) as total_submissions
    ');
    $stmt->execute();
    $stats = $stmt->fetch();

    // Recent activity (system-wide)
    $stmt = $pdo->prepare('
        SELECT u.name, l.title, l.created_at, "lesson_created" as type
        FROM lessons l
        JOIN users u ON l.teacher_id = u.id
        ORDER BY l.created_at DESC
        LIMIT 5
    ');
    $stmt->execute();
    $recent_activity = $stmt->fetchAll();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];
    $success = false;

    // Validate name
    if (empty($name)) {
        $errors[] = 'Name is required.';
    } elseif (strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters long.';
    }

    // Check current password if changing password
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = 'Current password is required to change password.';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new_password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match.';
        }
    }

    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET name = ?, password = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([$name, $hashed_password, $user_id]);
            } else {
                // Update name only
                $stmt = $pdo->prepare('UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([$name, $user_id]);
            }

            // Update session data
            $_SESSION['user']['name'] = $name;

            setFlashMessage('success', 'Profile updated successfully!');
            $success = true;

            // Refresh user data
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$user_id]);
            $updated_user = $stmt->fetch();
            $_SESSION['user'] = [
                'id' => $updated_user['id'],
                'name' => $updated_user['name'],
                'email' => $updated_user['email'],
                'role' => $updated_user['role'],
                'created_at' => $updated_user['created_at']
            ];

        } catch (Exception $e) {
            $errors[] = 'An error occurred while updating your profile.';
            error_log("Profile update error: " . $e->getMessage());
        }
    }

    if (!empty($errors)) {
        setFlashMessage('error', implode(' ', $errors));
    } elseif ($success) {
        // Redirect to refresh the page
        header('Location: index.php?page=profile');
        exit;
    }
}
?>

<!-- Profile Header -->
<div class="box" style="background: linear-gradient(135deg, var(--primary-green), var(--primary-dark));">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--space-4);">
        <div>
            <h2 style="color: white; margin-bottom: var(--space-2);">
                👤 My Profile
            </h2>
            <p style="color: rgba(255, 255, 255, 0.9); margin-bottom: 0;">
                Manage your account information and view your activity
            </p>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 3rem; margin-bottom: var(--space-2);">🌱</div>
            <div style="font-size: 0.875rem; color: rgba(255,255,255,0.8);">
                <?php echo ucfirst($user['role']); ?> Account
            </div>
        </div>
    </div>
</div>

<div class="grid">
    <!-- Profile Information -->
    <div class="card">
        <h3 style="margin-bottom: var(--space-6); display: flex; align-items: center; gap: var(--space-2);">
            <i class="bi bi-person-circle" style="color: var(--primary-green);"></i>
            Profile Information
        </h3>

        <form method="POST" style="max-width: 500px;">
            <div style="margin-bottom: var(--space-4);">
                <label for="name" class="form-label fw-semibold">Full Name</label>
                <input type="text" class="form-control" id="name" name="name"
                       value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>

            <div style="margin-bottom: var(--space-4);">
                <label for="email" class="form-label fw-semibold">Email Address</label>
                <input type="email" class="form-control" id="email" name="email"
                       value="<?php echo htmlspecialchars($user['email']); ?>" readonly
                       style="background-color: var(--gray-100);">
                <div class="form-text">Email cannot be changed. Contact administrator if needed.</div>
            </div>

            <div style="margin-bottom: var(--space-4);">
                <label for="role" class="form-label fw-semibold">Role</label>
                <input type="text" class="form-control" id="role" name="role"
                       value="<?php echo ucfirst($user['role']); ?>" readonly
                       style="background-color: var(--gray-100);">
            </div>

            <div style="margin-bottom: var(--space-4);">
                <label class="form-label fw-semibold">Member Since</label>
                <input type="text" class="form-control"
                       value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" readonly
                       style="background-color: var(--gray-100);">
            </div>

            <div style="margin-bottom: var(--space-4);">
                <label class="form-label fw-semibold">Last Login</label>
                <input type="text" class="form-control"
                       value="<?php echo $user['last_login'] ? date('F j, Y \a\t g:i A', strtotime($user['last_login'])) : 'Never'; ?>" readonly
                       style="background-color: var(--gray-100);">
            </div>

            <hr style="margin: var(--space-6) 0;">

            <h5 style="margin-bottom: var(--space-4);">Change Password (Optional)</h5>

            <div style="margin-bottom: var(--space-4);">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password" class="form-control" id="current_password" name="current_password">
                <div class="form-text">Required only if changing password</div>
            </div>

            <div style="margin-bottom: var(--space-4);">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password">
                <div class="form-text">Leave blank to keep current password</div>
            </div>

            <div style="margin-bottom: var(--space-6);">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
            </div>

            <button type="submit" name="update_profile" class="btn btn-primary">
                <i class="bi bi-check-circle me-2"></i>Update Profile
            </button>
        </form>
    </div>

    <!-- Statistics Card -->
    <div class="card">
        <h3 style="margin-bottom: var(--space-6); display: flex; align-items: center; gap: var(--space-2);">
            <i class="bi bi-bar-chart-line" style="color: var(--primary-green);"></i>
            <?php echo ucfirst($user['role']); ?> Statistics
        </h3>

        <div style="display: grid; gap: var(--space-4);">
            <?php if ($user['role'] === 'teacher'): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-3); background: var(--gray-50); border-radius: var(--radius-md);">
                    <div>
                        <div style="font-weight: 600; color: var(--gray-900);"><?php echo $stats['total_lessons'] ?? 0; ?></div>
                        <div style="font-size: 0.875rem; color: var(--gray-600);">Total Lessons</div>
                    </div>
                    <div style="font-size: 1.5rem;">📚</div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-3); background: var(--gray-50); border-radius: var(--radius-md);">
                    <div>
                        <div style="font-weight: 600; color: var(--gray-900);"><?php echo $stats['total_activities'] ?? 0; ?></div>
                        <div style="font-size: 0.875rem; color: var(--gray-600);">Activities</div>
                    </div>
                    <div style="font-size: 1.5rem;">📝</div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-3); background: var(--gray-50); border-radius: var(--radius-md);">
                    <div>
                        <div style="font-weight: 600; color: var(--gray-900);"><?php echo $stats['total_submissions'] ?? 0; ?></div>
                        <div style="font-size: 0.875rem; color: var(--gray-600);">Total Submissions</div>
                    </div>
                    <div style="font-size: 1.5rem;">📬</div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-3); background: var(--gray-50); border-radius: var(--radius-md);">
                    <div>
                        <div style="font-weight: 600; color: var(--gray-900);"><?php echo $stats['pending_grades'] ?? 0; ?></div>
                        <div style="font-size: 0.875rem; color: var(--gray-600);">Pending Grades</div>
                    </div>
                    <div style="font-size: 1.5rem;">⏳</div>
                </div>

                <?php if (($stats['avg_grade'] ?? 0) > 0): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-3); background: linear-gradient(135deg, var(--success), #059669); color: white; border-radius: var(--radius-md);">
                    <div>
                        <div style="font-weight: 600;"><?php echo number_format($stats['avg_grade'], 1); ?>%</div>
                        <div style="font-size: 0.875rem; opacity: 0.9;">Average Grade</div>
                    </div>
                    <div style="font-size: 1.5rem;">📊</div>
                </div>
                <?php endif; ?>

            <?php elseif ($user['role'] === 'student'): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-3); background: var(--gray-50); border-radius: var(--radius-md);">
                    <div>
                        <div style="font-weight: 600; color: var(--gray-900);"><?php echo $stats['total_submissions'] ?? 0; ?></div>
                        <div style="font-size: 0.875rem; color: var(--gray-600);">Total Submissions</div>
                    </div>
                    <div style="font-size: 1.5rem;">📤</div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-3); background: var(--gray-50); border-radius: var(--radius-md);">
                    <div>
                        <div style="font-weight: 600; color: var(--gray-900);"><?php echo $stats['graded_submissions'] ?? 0; ?></div>
                        <div style="font-size: 0.875rem; color: var(--gray-600);">Graded Submissions</div>
                    </div>
                    <div style="font-size: 1.5rem;">✅</div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-3); background: var(--gray-50); border-radius: var(--radius-md);">
                    <div>
                        <div style="font-weight: 600; color: var(--gray-900);"><?php echo $stats['total_lessons_viewed'] ?? 0; ?></div>
                        <div style="font-size: 0.875rem; color: var(--gray-600);">Lessons Viewed</div>
                    </div>
                    <div style="font-size: 1.5rem;">👁️</div>
                </div>

                <?php if (($stats['avg_grade'] ?? 0) > 0): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-3); background: linear-gradient(135deg, var(--success), #059669); color: white; border-radius: var(--radius-md);">
                    <div>
                        <div style="font-weight: 600;"><?php echo number_format($stats['avg_grade'], 1); ?>%</div>
                        <div style="font-size: 0.875rem; opacity: 0.9;">Average Grade</div>
                    </div>
                    <div style="font-size: 1.5rem;">📊</div>
                </div>
                <?php endif; ?>

            <?php elseif ($user['role'] === 'admin'): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-3); background: var(--gray-50); border-radius: var(--radius-md);">
                    <div>
                        <div style="font-weight: 600; color: var(--gray-900);"><?php echo $stats['total_teachers'] ?? 0; ?></div>
                        <div style="font-size: 0.875rem; color: var(--gray-600);">Teachers</div>
                    </div>
                    <div style="font-size: 1.5rem;">👨‍🏫</div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-3); background: var(--gray-50); border-radius: var(--radius-md);">
                    <div>
                        <div style="font-weight: 600; color: var(--gray-900);"><?php echo $stats['total_students'] ?? 0; ?></div>
                        <div style="font-size: 0.875rem; color: var(--gray-600);">Students</div>
                    </div>
                    <div style="font-size: 1.5rem;">👨‍🎓</div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-3); background: var(--gray-50); border-radius: var(--radius-md);">
                    <div>
                        <div style="font-weight: 600; color: var(--gray-900);"><?php echo $stats['total_lessons'] ?? 0; ?></div>
                        <div style="font-size: 0.875rem; color: var(--gray-600);">Total Lessons</div>
                    </div>
                    <div style="font-size: 1.5rem;">📚</div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-3); background: var(--gray-50); border-radius: var(--radius-md);">
                    <div>
                        <div style="font-weight: 600; color: var(--gray-900);"><?php echo $stats['total_submissions'] ?? 0; ?></div>
                        <div style="font-size: 0.875rem; color: var(--gray-600);">Total Submissions</div>
                    </div>
                    <div style="font-size: 1.5rem;">📬</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<?php if (!empty($recent_activity)): ?>
<div class="box" style="margin-top: var(--space-8);">
    <h3 style="margin-bottom: var(--space-6); display: flex; align-items: center; gap: var(--space-2);">
        <i class="bi bi-clock-history" style="color: var(--primary-green);"></i>
        Recent Activity
    </h3>

    <div style="display: grid; gap: var(--space-3);">
        <?php foreach ($recent_activity as $activity): ?>
            <div style="padding: var(--space-4); border: 1px solid var(--gray-200); border-radius: var(--radius-lg); background: var(--gray-50);">
                <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: var(--space-3);">
                    <div style="flex-grow: 1;">
                        <h5 style="margin-bottom: var(--space-2); color: var(--gray-900); font-size: 1rem;">
                            <?php
                            $activity_type = $activity['type'];
                            $icon = '';
                            $action_text = '';

                            if ($activity_type === 'lesson_created') {
                                $icon = '📚';
                                $action_text = 'Created lesson:';
                            } elseif ($activity_type === 'submission_graded') {
                                $icon = '✅';
                                $action_text = 'Graded submission for:';
                            } elseif ($activity_type === 'submission_made') {
                                $icon = '📤';
                                $action_text = 'Submitted work for:';
                            } elseif ($activity_type === 'grade_received') {
                                $icon = '📊';
                                $action_text = 'Received grade for:';
                            }

                            echo $icon . ' ' . htmlspecialchars($action_text) . ' ' . htmlspecialchars($activity['title']);
                            ?>
                        </h5>
                        <?php if (isset($activity['name'])): ?>
                            <p style="margin-bottom: var(--space-2); color: var(--gray-600); font-size: 0.875rem;">
                                👤 <?php echo htmlspecialchars($activity['name']); ?>
                            </p>
                        <?php endif; ?>
                        <p style="margin: 0; font-size: 0.875rem; color: var(--gray-500);">
                            📅 <?php echo date('M j, Y \a\t g:i A', strtotime($activity['created_at'] ?? $activity['activity_date'])); ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__.'/common/footer.php'; ?>