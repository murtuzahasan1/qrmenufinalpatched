<?php
// /api/bootstrap.php

// --- FATAL ERROR HANDLER ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        echo json_encode([
            'success' => false,
            'message' => 'A fatal server error occurred.',
            'error_details' => [
                'type' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
            ]
        ]);
        exit();
    }
});
// --- END ERROR HANDLER ---

// --- CORE FILE INCLUDES ---
require_once __DIR__ . '/../config/config.php';
require_once APP_ROOT . '/includes/utils.php';
require_once APP_ROOT . '/includes/database.php';
require_once APP_ROOT . '/includes/auth.php';

// --- SESSION CONFIGURATION ---
$session_path = APP_ROOT . '/cache/sessions';
if (!is_dir($session_path)) {
    mkdir($session_path, 0755, true);
}
ini_set('session.save_path', $session_path);
?>