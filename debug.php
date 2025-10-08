<?php
// Debug page to identify the internal server error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>SmartApp Debug Information</h1>";

echo "<h2>PHP Information</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Current Directory: " . getcwd() . "<br>";
echo "Server Time: " . date('Y-m-d H:i:s') . "<br>";

echo "<h2>File System Check</h2>";
$files_to_check = [
    'index.php',
    'config/database.php',
    'auth/login.php',
    '.htaccess'
];

foreach ($files_to_check as $file) {
    echo $file . ": " . (file_exists($file) ? "✅ EXISTS" : "❌ MISSING") . "<br>";
}

echo "<h2>Database Connection Test</h2>";
try {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn) {
        echo "✅ Database connection successful<br>";
        echo "Database type: " . ($_ENV['DB_TYPE'] ?? 'mysql') . "<br>";
    } else {
        echo "❌ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h2>Environment Variables</h2>";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NOT SET') . "<br>";
echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? 'NOT SET') . "<br>";
echo "DB_USERNAME: " . ($_ENV['DB_USERNAME'] ?? 'NOT SET') . "<br>";
echo "DB_TYPE: " . ($_ENV['DB_TYPE'] ?? 'NOT SET') . "<br>";

echo "<h2>PHP Extensions</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'pdo_pgsql', 'gd', 'zip'];
foreach ($required_extensions as $ext) {
    echo $ext . ": " . (extension_loaded($ext) ? "✅ LOADED" : "❌ NOT LOADED") . "<br>";
}

echo "<h2>Directory Permissions</h2>";
echo "Current directory writable: " . (is_writable('.') ? "✅ YES" : "❌ NO") . "<br>";
echo "Uploads directory exists: " . (is_dir('uploads') ? "✅ YES" : "❌ NO") . "<br>";

echo "<h2>Navigation</h2>";
echo "<a href='index.php'>Go to Main App</a><br>";
echo "<a href='test.php'>Go to Test Page</a><br>";
echo "<a href='health.php'>Health Check</a><br>";
?>