<!DOCTYPE html>
<html>
<head>
    <title>SmartApp Test Page</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>SmartApp Test Page</h1>
    
    <h2>Server Status</h2>
    <p class="success">✅ PHP is working! Version: <?php echo PHP_VERSION; ?></p>
    <p class="success">✅ Server time: <?php echo date('Y-m-d H:i:s'); ?></p>
    
    <h2>Database Test</h2>
    <?php
    try {
        require_once 'config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        if ($conn) {
            echo '<p class="success">✅ Database connection successful!</p>';
        } else {
            echo '<p class="error">❌ Database connection failed</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Database error: ' . $e->getMessage() . '</p>';
    }
    ?>
    
    <h2>File System Test</h2>
    <p class="success">✅ Current directory: <?php echo getcwd(); ?></p>
    <p class="success">✅ Files in directory: <?php echo count(scandir('.')); ?> items</p>
    
    <h2>Navigation</h2>
    <p><a href="auth/login.php">Go to Login Page</a></p>
    <p><a href="health.php">Health Check</a></p>
    <p><a href="index.php?health=1">JSON Health Check</a></p>
</body>
</html>
