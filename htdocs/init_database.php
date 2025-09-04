<?php
require_once __DIR__ . '/includes/database_initializer.php';

// Initialize database
$initializer = new DatabaseInitializer();
$result = $initializer->initialize();

if ($result['success']) {
    echo "Database initialized successfully!\n";
    echo "Default admin credentials:\n";
    echo "Email: admin@qrmenu.com\n";
    echo "Password: admin123\n";
} else {
    echo "Error: " . $result['message'] . "\n";
}
?>