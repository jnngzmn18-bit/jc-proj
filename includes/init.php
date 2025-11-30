<?php
/**
 * Environmental Science LMS - Initialization File
 *
 * This file handles the core system initialization including
 * session management, database connection, and authentication setup.
 *
 * @author Environmental Science LMS Team
 * @version 2.0
 */

// Prevent direct access
if (!defined('LMS_INIT')) {
    die('Direct access not permitted');
}

// Start session management
session_start();

// Include core system files
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/**
 * Enhanced error handling and logging
 */
function handleError($errno, $errstr, $errfile, $errline) {
    error_log("LMS Error: [$errno] $errstr in $errfile on line $errline");
    return false;
}
set_error_handler('handleError');

/**
 * Set flash message for user feedback
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}
