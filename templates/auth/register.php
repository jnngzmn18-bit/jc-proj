<?php 
$page_title = "Register";
include __DIR__.'/../common/header.php'; 

// Handle registration form submission
if ($_POST) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $admin_key = $_POST['admin_key'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email address is required.';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if ($role === 'admin' && $admin_key !== 'admin') {
        $errors[] = 'Invalid admin key provided.';
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email address already exists.';
        }
    }
    
    // Register user if no errors
    if (empty($errors)) {
        if (register_user($name, $email, $password, $role, $admin_key)) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Account created successfully! Please log in.'
            ];
            header('Location: index.php?page=login');
            exit;
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <!-- Header Section -->
        <div class="auth-header">
            <div class="auth-icon">🌱</div>
            <h1>Join Our Community</h1>
            <p>Create your Environmental Science LMS account</p>
        </div>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form method="post" class="auth-form">
            <div class="form-group">
                <label for="name" class="form-label">Full Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    required
                    placeholder="Enter your full name"
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                    autocomplete="name"
                    class="form-input"
                >
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    placeholder="Enter your email address"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    autocomplete="email"
                    class="form-input"
                >
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        placeholder="Create a password"
                        autocomplete="new-password"
                        minlength="6"
                        class="form-input"
                    >
                    <small class="form-hint">Minimum 6 characters</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        required
                        placeholder="Confirm your password"
                        autocomplete="new-password"
                        class="form-input"
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="role" class="form-label">Account Type</label>
                <select id="role" name="role" required onchange="toggleAdminKey()" class="form-input">
                    <option value="student" <?php echo ($_POST['role'] ?? 'student') === 'student' ? 'selected' : ''; ?>>
                        Student
                    </option>
                    <option value="teacher" <?php echo ($_POST['role'] ?? '') === 'teacher' ? 'selected' : ''; ?>>
                        Teacher
                    </option>
                    <option value="admin" <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>
                        Administrator
                    </option>
                </select>
            </div>

            <!-- Admin Key Field (Hidden by default) -->
            <div id="admin-key-group" class="form-group" style="display: none;">
                <label for="admin_key" class="form-label">Admin Key</label>
                <input
                    type="password"
                    id="admin_key"
                    name="admin_key"
                    placeholder="Enter admin key"
                    value="<?php echo htmlspecialchars($_POST['admin_key'] ?? ''); ?>"
                    class="form-input"
                >
                <small class="form-hint">Required for administrator accounts</small>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Create Account
            </button>
        </form>

        <!-- Divider -->
        <div class="auth-divider">
            <span>Already have an account?</span>
        </div>

        <!-- Login Link -->
        <div class="auth-footer">
            <a href="index.php?page=login" class="btn btn-primary btn-full">
                Sign In Instead
            </a>
        </div>

        <!-- Account Types Info -->
        <div class="auth-info">
            <h4>📋 Account Types</h4>
            <div class="info-list">
                <p><strong>Student:</strong> Access lessons, submit activities, view grades</p>
                <p><strong>Teacher:</strong> Create content, grade submissions, manage classes</p>
                <p><strong>Admin:</strong> System management and user oversight</p>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAdminKey() {
    const roleSelect = document.getElementById('role');
    const adminKeyGroup = document.getElementById('admin-key-group');
    const adminKeyInput = document.getElementById('admin_key');
    
    if (roleSelect.value === 'admin') {
        adminKeyGroup.style.display = 'block';
        adminKeyInput.required = true;
    } else {
        adminKeyGroup.style.display = 'none';
        adminKeyInput.required = false;
        adminKeyInput.value = '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleAdminKey();
});
</script>

<?php include __DIR__.'/../common/footer.php'; ?>