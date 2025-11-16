<?php
/**
 * Environmental Science LMS - Installation Script
 * 
 * This script sets up the database and initial configuration
 */

// Define initialization constant
define('LMS_INIT', true);

// Include configuration
require_once __DIR__ . '/includes/config.php';

// Database connection for installation
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    
    echo "✅ Database connection established\n";
    
    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    if ($schema) {
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $schema)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    echo "⚠️  Warning: " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "✅ Database schema installed successfully\n";
    }
    
    // Create uploads directory
    $uploadDir = __DIR__ . '/uploads';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "✅ Uploads directory created\n";
    }
    
    // Create subdirectories
    $subdirs = ['lessons', 'submissions', 'profiles', 'temp'];
    foreach ($subdirs as $subdir) {
        $path = $uploadDir . '/' . $subdir;
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }
    
    echo "✅ Upload directories created\n";
    
    // Set permissions
    chmod($uploadDir, 0755);
    echo "✅ Permissions set\n";
    
    echo "\n🎉 Installation completed successfully!\n\n";
    echo "Default login credentials:\n";
    echo "Admin: admin@lms.local / admin123\n";
    echo "Teacher: teacher@lms.local / teacher123\n";
    echo "Student: student@lms.local / student123\n\n";
    echo "Please change these passwords after first login.\n";
    echo "You can now access the LMS at: http://your-domain/public/\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Installation error: " . $e->getMessage() . "\n";
    exit(1);
}
?>