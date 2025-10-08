<!DOCTYPE html>
<html>
<head>
    <title>SmartApp</title>
</head>
<body>
    <h1>SmartApp is Running!</h1>
    <p>PHP Version: <?php echo PHP_VERSION; ?></p>
    <p>Current Time: <?php echo date('Y-m-d H:i:s'); ?></p>
    
    <h2>Test Links:</h2>
    <ul>
        <li><a href="simple_test.php">Simple PHP Test</a></li>
        <li><a href="debug.php">Debug Information</a></li>
        <li><a href="test.php">Test Page</a></li>
        <li><a href="health.php">Health Check</a></li>
    </ul>
    
    <h2>Database Test:</h2>
    <?php
    try {
        require_once 'config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        if ($conn) {
            echo "<p style='color: green;'>✅ Database connection successful!</p>";
        } else {
            echo "<p style='color: red;'>❌ Database connection failed</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>
</body>
</html>
