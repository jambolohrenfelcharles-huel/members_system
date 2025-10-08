<?php
// SmartApp Main Entry Point
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if we're in a health check
if (isset($_GET['health'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'message' => 'SmartApp is running']);
    exit();
}

// Redirect to login page
try {
    header('Location: auth/login.php');
    exit();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
