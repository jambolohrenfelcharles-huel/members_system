<?php
echo "<h1>✅ SmartApp Local Test</h1>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Script Path:</strong> " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "<p><strong>Current Directory:</strong> " . getcwd() . "</p>";

echo "<h2>🔧 Quick Tests:</h2>";
echo "<ul>";
echo "<li><a href='index.php'>🏠 Main Index</a></li>";
echo "<li><a href='auth/login.php'>🔐 Login Page</a></li>";
echo "<li><a href='dashboard/index.php'>📊 Dashboard</a></li>";
echo "<li><a href='test_direct_email_solution.php'>📧 Email Test</a></li>";
echo "</ul>";

echo "<h2>📁 Directory Contents:</h2>";
echo "<pre>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo $file . "\n";
    }
}
echo "</pre>";
?>
