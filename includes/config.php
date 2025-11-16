<?php
/**
 * Environmental Science LMS - Configuration File
 * 
 * This file contains all configuration settings for the LMS system
 * including database settings, security configurations, and system constants.
 */

// Prevent direct access
if (!defined('LMS_INIT')) {
    define('LMS_INIT', true);
}

// Detect environment (Railway vs Local)
if (getenv('RAILWAY_ENVIRONMENT')) {
    // Railway (Production)
    define('DB_HOST', getenv('DB_HOST') ?: 'hopper.proxy.rlwy.net');
    define('DB_NAME', getenv('DB_NAME') ?: 'railway');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: 'jnZBSnZBhtvApLHLBJplanxuIRvMLDdE');
    define('DB_PORT', getenv('DB_PORT') ?: 53461);
} else {
    // Local (Development)
    define('DB_HOST', '127.0.0.1');
    define('DB_NAME', 'lms_environmental_science');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_PORT', 3306);
}

define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'Environmental Science LMS');
define('APP_VERSION', '2.0');
define('APP_URL', 'http://' . $_SERVER['HTTP_HOST']);
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Security Configuration
define('SESSION_LIFETIME', 3600 * 8); // 8 hours
define('PASSWORD_MIN_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 10);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File Upload Configuration
define('ALLOWED_FILE_TYPES', [
    'pdf', 'doc', 'docx', 'txt', 'rtf',
    'jpg', 'jpeg', 'png', 'gif', 'webp',
    'mp4', 'avi', 'mov', 'wmv',
    'zip', 'rar', '7z'
]);

// Pagination
define('ITEMS_PER_PAGE', 10);

// Email Configuration (for future use)
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@lms.local');
define('FROM_NAME', APP_NAME);

// Timezone
date_default_timezone_set('America/New_York');

// Error Reporting
if (defined('DEVELOPMENT') && DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
?>