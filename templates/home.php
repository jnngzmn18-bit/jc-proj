<?php
if(session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Environmental Science Learning Management System - Interactive learning platform for students and teachers">
    <meta name="keywords" content="environmental science, LMS, education, learning, sustainability">
    <meta name="author" content="Environmental Science LMS">

    <title>Environmental Science LMS - Learn Environmental Science Online</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Custom Styles -->
    <link rel="stylesheet" href="../assets/css/modern-lms.css">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">

    <style>
        :root {
            --primary-green: #22c55e;
            --primary-blue: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --space-1: 0.25rem;
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-5: 1.25rem;
            --space-6: 1.5rem;
            --space-8: 2rem;
            --space-12: 3rem;
            --radius-lg: 0.5rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--gray-800);
            overflow-x: hidden;
        }

        .hero-section {
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(59, 130, 246, 0.1)),
                        url('https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2074&q=80') center/cover no-repeat;
            background-attachment: fixed;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1;
        }

        .hero-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: var(--space-8);
            position: relative;
            z-index: 2;
        }

        .hero-heading {
            font-size: clamp(2.5rem, 5vw, 4.5rem);
            font-weight: 800;
            color: white;
            margin-bottom: var(--space-6);
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            line-height: 1.1;
        }

        .hero-subheading {
            font-size: clamp(1rem, 2vw, 1.25rem);
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: var(--space-8);
            max-width: 600px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .hero-button {
            background: linear-gradient(135deg, var(--primary-green), var(--success));
            color: white;
            border: none;
            padding: var(--space-4) var(--space-8);
            font-size: 1.125rem;
            font-weight: 600;
            border-radius: var(--radius-lg);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
        }

        .hero-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
            color: white;
            text-decoration: none;
        }

        .hero-button i {
            transition: transform 0.3s ease;
        }

        .hero-button:hover i {
            transform: translateX(3px);
        }

        .nav-buttons {
            position: absolute;
            top: var(--space-6);
            right: var(--space-6);
            display: flex;
            gap: var(--space-3);
            z-index: 3;
        }

        .nav-button {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: var(--space-3) var(--space-6);
            border-radius: var(--radius-lg);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-button:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .nav-button.primary {
            background: var(--primary-green);
            border-color: var(--primary-green);
        }

        .nav-button.primary:hover {
            background: var(--success);
            border-color: var(--success);
        }

        .features-section {
            padding: var(--space-12) 0;
            background: white;
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--space-6);
        }

        .features-heading {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: var(--space-12);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--space-8);
        }

        .feature-card {
            background: var(--gray-50);
            padding: var(--space-6);
            border-radius: var(--radius-lg);
            text-align: center;
            border: 1px solid var(--gray-200);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary-green);
            margin-bottom: var(--space-4);
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--space-3);
        }

        .feature-description {
            color: var(--gray-600);
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .nav-buttons {
                position: relative;
                top: auto;
                right: auto;
                justify-content: center;
                margin-bottom: var(--space-6);
            }

            .hero-heading {
                font-size: 2.5rem;
            }

            .hero-subheading {
                font-size: 1rem;
            }

            .features-section {
                padding: var(--space-8) 0;
            }

            .features-heading {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Buttons -->
    <div class="nav-buttons">
        <a href="?page=login" class="nav-button">Login</a>
        <a href="?page=register" class="nav-button primary">Sign Up</a>
    </div>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-heading">
                🌍 Learn Environmental Science
            </h1>
            <p class="hero-subheading">
                Join our interactive learning platform to explore environmental science, sustainability, and ecological concepts through engaging lessons and activities.
            </p>
            <a href="?page=login" class="hero-button">
                Learn Now
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="features-container">
            <h2 class="features-heading">Why Choose Our LMS?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3 class="feature-title">Environmental Focus</h3>
                    <p class="feature-description">
                        Specialized content covering climate change, biodiversity, sustainability, and environmental conservation.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Interactive Learning</h3>
                    <p class="feature-description">
                        Engage with hands-on activities, quizzes, and collaborative projects designed for effective learning.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Track Progress</h3>
                    <p class="feature-description">
                        Monitor your learning journey with detailed progress tracking, grades, and performance analytics.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="feature-title">Mobile Access</h3>
                    <p class="feature-description">
                        Access your lessons anywhere with our responsive design and QR code functionality for easy sharing.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3 class="feature-title">Expert Teachers</h3>
                    <p class="feature-description">
                        Learn from experienced environmental science educators dedicated to your success.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-award"></i>
                    </div>
                    <h3 class="feature-title">Certified Learning</h3>
                    <p class="feature-description">
                        Earn certificates and recognition for completing courses and demonstrating environmental knowledge.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>