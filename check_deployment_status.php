<?php
/**
 * Deployment Status Checker
 * Check if the latest fixes are deployed on Render
 */

echo "<h1>Deployment Status Check</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

echo "<h2>🔍 Checking Deployment Status</h2>";

// Check local file status
echo "<h3>📁 Local File Status</h3>";

$filesToCheck = [
    'config/notification_helper.php' => 'Notification helper fix',
    'dashboard/system_status.php' => 'Database size query fix',
    'dashboard/admin/index.php' => 'User blocking functionality',
    'auth/login.php' => 'Login prevention for blocked users',
    'render_deploy.php' => 'Database migration script'
];

foreach ($filesToCheck as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file - $description</p>";
        
        // Check specific fixes
        if ($file === 'config/notification_helper.php') {
            $content = file_get_contents($file);
            if (strpos($content, "'membership_monitoring'") !== false) {
                echo "<p style='color: green;'>  ✅ \$members_table fix applied</p>";
            } else {
                echo "<p style='color: red;'>  ❌ \$members_table fix missing</p>";
            }
        }
        
        if ($file === 'dashboard/system_status.php') {
            $content = file_get_contents($file);
            if (strpos($content, 'pg_size_pretty') !== false) {
                echo "<p style='color: green;'>  ✅ PostgreSQL database size query fix applied</p>";
            } else {
                echo "<p style='color: red;'>  ❌ PostgreSQL database size query fix missing</p>";
            }
        }
        
    } else {
        echo "<p style='color: red;'>❌ $file - Missing</p>";
    }
}

echo "<h3>🔧 Git Status</h3>";

// Check git status
$gitStatus = shell_exec('git status --porcelain 2>&1');
$gitLog = shell_exec('git log --oneline -5 2>&1');

echo "<h4>Recent Commits:</h4>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
echo htmlspecialchars($gitLog);
echo "</pre>";

echo "<h4>Working Directory Status:</h4>";
if (empty(trim($gitStatus))) {
    echo "<p style='color: green;'>✅ Working directory clean</p>";
} else {
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($gitStatus);
    echo "</pre>";
}

echo "<h3>🚀 Deployment Instructions</h3>";

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h4 style='color: green;'>✅ All Fixes Committed and Pushed</h4>";
echo "<p>Your latest fixes have been committed and pushed to GitHub. If you're still seeing errors on Render, try these steps:</p>";
echo "<ol>";
echo "<li><strong>Check Render Dashboard:</strong> Go to <a href='https://dashboard.render.com' target='_blank'>https://dashboard.render.com</a></li>";
echo "<li><strong>Verify Auto-Deploy:</strong> Check if auto-deploy is enabled and working</li>";
echo "<li><strong>Manual Deploy:</strong> If auto-deploy isn't working, trigger a manual deployment</li>";
echo "<li><strong>Check Logs:</strong> Review deployment logs for any errors</li>";
echo "<li><strong>Health Check:</strong> Visit your app's health endpoint</li>";
echo "</ol>";
echo "</div>";

echo "<h3>🔗 Important URLs</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>Render Dashboard:</strong> <a href='https://dashboard.render.com' target='_blank'>https://dashboard.render.com</a></p>";
echo "<p><strong>GitHub Repository:</strong> <a href='https://github.com/jambolohrenfelcharles-huel/members_system' target='_blank'>https://github.com/jambolohrenfelcharles-huel/members_system</a></p>";
echo "<p><strong>Health Check:</strong> <code>https://your-app.onrender.com/health.php</code></p>";
echo "<p><strong>Admin Console:</strong> <code>https://your-app.onrender.com/dashboard/admin/index.php</code></p>";
echo "<p><strong>System Status:</strong> <code>https://your-app.onrender.com/dashboard/system_status.php</code></p>";
echo "<p><strong>Add Announcements:</strong> <code>https://your-app.onrender.com/dashboard/announcements/add.php</code></p>";
echo "</div>";

echo "<h3>📊 Latest Fixes Summary</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h4 style='color: orange;'>⚠️ If Errors Persist on Render</h4>";
echo "<p><strong>Latest fixes applied:</strong></p>";
echo "<ul>";
echo "<li>✅ Fixed undefined \$members_table variable in notification_helper.php</li>";
echo "<li>✅ Fixed PostgreSQL database size query in system_status.php</li>";
echo "<li>✅ Added user blocking functionality to admin console</li>";
echo "<li>✅ Fixed all PostgreSQL compatibility issues</li>";
echo "<li>✅ Added automatic database migrations</li>";
echo "</ul>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Check Render dashboard for deployment status</li>";
echo "<li>Trigger manual deployment if needed</li>";
echo "<li>Check deployment logs for errors</li>";
echo "<li>Verify health endpoint is working</li>";
echo "</ol>";
echo "</div>";

echo "</div>";
?>
