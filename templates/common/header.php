<?php 
if(session_status() === PHP_SESSION_NONE) session_start(); 
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Environmental Science Learning Management System - Interactive learning platform for students and teachers">
    <meta name="keywords" content="environmental science, LMS, education, learning, sustainability">
    <meta name="author" content="Environmental Science LMS">
    
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Environmental Science LMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Stylesheets -->
    <link rel="stylesheet" href="assets/css/modern-lms.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    
    <!-- QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body class="bg-light">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="visually-hidden-focusable">Skip to main content</a>
    
    <?php if (is_logged_in()): ?>
        <!-- Include Sidebar for logged-in users -->
        <?php include __DIR__ . '/sidebar.php'; ?>
    <?php endif; ?>
    

    
    <!-- Main Content Area -->
    <main id="main-content" role="main" class="<?php echo is_logged_in() ? 'main-content' : 'container-fluid'; ?>">
        <?php
        // Update session last_activity for session duration tracking
        if(!empty($_SESSION['user'])) {
            $_SESSION['last_activity'] = time();
        }
        
        // Display flash messages if any
        if(isset($_SESSION['flash_message'])) {
            $flash = $_SESSION['flash_message'];
            echo '<div class="alert alert-' . htmlspecialchars($flash['type']) . ' alert-dismissible fade show" role="alert">';
            echo '<i class="bi bi-info-circle me-2"></i>';
            echo htmlspecialchars($flash['message']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            unset($_SESSION['flash_message']);
        }
        ?>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <!-- Load Sidebar JavaScript -->
    <?php if (is_logged_in()): ?>
        <script src="assets/js/sidebar.js"></script>
    <?php endif; ?>
    
