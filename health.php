<?php
// Health check endpoint for Render
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'message' => 'SmartApp is running',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION
]);
?>
