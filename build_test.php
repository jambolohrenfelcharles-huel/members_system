<?php
// Simple test to verify PHP is working
echo "PHP is working! Version: " . PHP_VERSION . "\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n";

// Test database connection
try {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn) {
        echo "Database connection: SUCCESS\n";
    } else {
        echo "Database connection: FAILED\n";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

// Test file permissions
echo "File permissions: " . (is_writable('.') ? 'OK' : 'FAILED') . "\n";
echo "Uploads directory: " . (is_dir('uploads') ? 'EXISTS' : 'MISSING') . "\n";
?>
