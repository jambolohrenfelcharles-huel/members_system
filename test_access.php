<?php
echo "<h1>🔧 SmartApp Access Test</h1>";
echo "<p><strong>Status:</strong> ✅ PHP is working!</p>";
echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

echo "<h2>📋 Available URLs:</h2>";
echo "<ul>";
echo "<li><a href='index.php'>🏠 Main Index Page</a></li>";
echo "<li><a href='auth/login.php'>🔐 Login Page</a></li>";
echo "<li><a href='auth/signup.php'>👤 Signup Page</a></li>";
echo "<li><a href='dashboard/index.php'>📊 Dashboard</a></li>";
echo "<li><a href='test_direct_email_solution.php'>📧 Email Test</a></li>";
echo "<li><a href='test_local.php'>🧪 Local Test</a></li>";
echo "</ul>";

echo "<h2>🔍 File System Check:</h2>";
echo "<p><strong>Current Directory:</strong> " . getcwd() . "</p>";
echo "<p><strong>Index.php exists:</strong> " . (file_exists('index.php') ? '✅ Yes' : '❌ No') . "</p>";
echo "<p><strong>Auth directory exists:</strong> " . (is_dir('auth') ? '✅ Yes' : '❌ No') . "</p>";
echo "<p><strong>Dashboard directory exists:</strong> " . (is_dir('dashboard') ? '✅ Yes' : '❌ No') . "</p>";

echo "<h2>🌐 Server Information:</h2>";
echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Not set') . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "</p>";
echo "<p><strong>Request URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "</p>";
echo "<p><strong>Script Name:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'Not set') . "</p>";

echo "<h2>📁 Directory Listing:</h2>";
echo "<pre>";
$files = array_diff(scandir('.'), ['.', '..']);
foreach ($files as $file) {
    $type = is_dir($file) ? '[DIR]' : '[FILE]';
    echo sprintf("%-20s %s\n", $file, $type);
}
echo "</pre>";
?>
