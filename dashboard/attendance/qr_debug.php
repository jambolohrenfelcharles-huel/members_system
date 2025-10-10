<?php
/**
 * QR Scan Debug Endpoint
 * This helps diagnose QR scanning issues
 */

session_start();
require_once '../../config/database.php';

// Set proper headers
header('Content-Type: application/json');

echo json_encode([
    'status' => 'debug',
    'session' => [
        'user_id' => $_SESSION['user_id'] ?? 'not_set',
        'role' => $_SESSION['role'] ?? 'not_set'
    ],
    'request' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'post_data' => $_POST,
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not_set'
    ],
    'database' => [
        'type' => $_ENV['DB_TYPE'] ?? 'mysql',
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'name' => $_ENV['DB_NAME'] ?? 'members_system'
    ],
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
