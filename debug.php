<?php
// Debug file to check system status
echo "<h2>System Debug Information</h2>";

echo "<h3>File Structure Check:</h3>";
$files_to_check = [
    'config/database.php',
    'auth/login.php',
    'dashboard/index.php',
    'dashboard/includes/header.php',
    'dashboard/includes/sidebar.php',
    'dashboard/assets/css/dashboard.css',
    'dashboard/members/index.php',
    'dashboard/events/index.php',
    'dashboard/attendance/index.php',
    'dashboard/announcements/index.php',
    'dashboard/reports/index.php',
    'dashboard/admin/index.php'
];

foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    $status = $exists ? '<span style="color: green;">✓ EXISTS</span>' : '<span style="color: red;">✗ MISSING</span>';
    echo "$file: $status<br>";
}

echo "<h3>Database Connection Test:</h3>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo '<span style="color: green;">✓ Database connection successful</span><br>';
    
    // Test a simple query
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Users in database: " . $result['count'] . "<br>";
    
} catch (Exception $e) {
    echo '<span style="color: red;">✗ Database connection failed: ' . $e->getMessage() . '</span><br>';
}

echo "<h3>PHP Information:</h3>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Current Directory: " . __DIR__ . "<br>";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";

echo "<h3>Session Test:</h3>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo '<span style="color: green;">✓ User logged in: ' . $_SESSION['username'] . '</span><br>';
} else {
    echo '<span style="color: orange;">⚠ No active session</span><br>';
}

echo "<h3>Quick Links:</h3>";
echo '<a href="auth/login.php">Login Page</a><br>';
echo '<a href="dashboard/index.php">Dashboard</a><br>';
echo '<a href="dashboard/members/index.php">Members</a><br>';
echo '<a href="dashboard/events/index.php">Events</a><br>';
echo '<a href="dashboard/attendance/index.php">Attendance</a><br>';
echo '<a href="dashboard/announcements/index.php">Announcements</a><br>';
echo '<a href="dashboard/reports/index.php">Reports</a><br>';
?>
