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

// Simple error handling
try {
    // Check if login file exists
    if (file_exists('auth/login.php')) {
        header('Location: auth/login.php');
        exit();
    } else {
        // Fallback if login doesn't exist
        echo "<h1>SmartApp</h1>";
        echo "<p>Welcome to SmartApp!</p>";
        echo "<p><a href='debug.php'>Debug Information</a></p>";
        echo "<p><a href='test.php'>Test Page</a></p>";
    }
} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='debug.php'>Debug Information</a></p>";
}
?>
