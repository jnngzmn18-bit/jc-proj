<?php 
$page_title = "Login";
include __DIR__.'/../common/header.php'; 

// Handle login form submission
if ($_POST) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (login($email, $password)) {
        $user = user();
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => 'Welcome back, ' . htmlspecialchars($user['name']) . '!'
        ];
        
        // Redirect based on role
        switch($user['role']) {
            case 'admin':
                header('Location: index.php?page=admin_dashboard');
                break;
            case 'teacher':
                header('Location: index.php?page=teacher_dashboard');
                break;
            case 'student':
                header('Location: index.php?page=student_dashboard');
                break;
            default:
                header('Location: index.php?page=home');
        }
        exit;
    } else {
        $error = 'Invalid email or password. Please try again.';
    }
}
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <!-- Header Section -->
        <div class="auth-header">
            <div class="auth-icon">🌱</div>
            <h1>Welcome Back</h1>
            <p>Sign in to your Environmental Science LMS account</p>
        </div>

        <!-- Error Message -->
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="post" class="auth-form">
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

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    class="form-input"
                >
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Sign In
            </button>
        </form>

        <!-- Divider -->
        <div class="auth-divider">
            <span>New to the platform?</span>
        </div>

        <!-- Register Link -->
        <div class="auth-footer">
            <p>Don't have an account yet?</p>
            <a href="index.php?page=register" class="btn btn-primary btn-full">
                Create New Account
            </a>
        </div>
    </div>
</div>

<?php include __DIR__.'/../common/footer.php'; ?>