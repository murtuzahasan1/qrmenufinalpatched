<?php
// Define the application root path for reliable file includes
define('APP_ROOT', $_SERVER['DOCUMENT_ROOT']);

// Database Configuration
define('DB_PATH', APP_ROOT . '/database.sqlite');
define('DB_TIMEOUT', 30);

// Application Configuration
define('APP_NAME', 'QR Menu System');
define('APP_URL', 'https://lunadine.rf.gd/');
define('APP_VERSION', '1.0.0');

// Security Configuration
define('HASH_ALGO', PASSWORD_DEFAULT);
define('SESSION_LIFETIME', 7200); // 2 hours
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15 minutes

// File Upload Configuration
define('UPLOAD_PATH', APP_ROOT . '/uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// QR Code Configuration
define('QR_SIZE', 300);
define('QR_MARGIN', 10);
define('QR_ERROR_CORRECTION', 'H');

// Module System Configuration
define('MODULES_PATH', APP_ROOT . '/modules');
define('ENABLED_MODULES_FILE', APP_ROOT . '/enabled_modules.json');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', APP_ROOT . '/logs/error.log');

// Timezone
date_default_timezone_set('Asia/Dhaka');

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
?>