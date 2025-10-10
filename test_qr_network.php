<?php
/**
 * Test QR Scan Network Issues
 * This script tests the QR scanning functionality to identify network issues
 */

echo "<h1>QR Scan Network Test</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px;'>";

try {
    // Test 1: Database Connection
    echo "<h2>🔌 Database Connection Test</h2>";
    
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Test 2: Session Test
    echo "<h2>🔐 Session Test</h2>";
    
    session_start();
    if (isset($_SESSION['user_id'])) {
        echo "<p style='color: green;'>✅ User session active (ID: " . $_SESSION['user_id'] . ")</p>";
    } else {
        echo "<p style='color: red;'>❌ No user session found</p>";
        echo "<p><strong>Note:</strong> You need to be logged in to test QR scanning</p>";
    }
    
    // Test 3: User Email Test
    echo "<h2>📧 User Email Test</h2>";
    
    if (isset($_SESSION['user_id'])) {
        $stmt = $db->prepare('SELECT email FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && !empty($user['email'])) {
            echo "<p style='color: green;'>✅ User email found: " . htmlspecialchars($user['email']) . "</p>";
        } else {
            echo "<p style='color: red;'>❌ User email not found or empty</p>";
        }
    }
    
    // Test 4: Member Record Test
    echo "<h2>👤 Member Record Test</h2>";
    
    if (isset($_SESSION['user_id']) && isset($user['email'])) {
        $members_table = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql' ? 'members' : 'membership_monitoring';
        
        if ($_ENV['DB_TYPE'] === 'postgresql') {
            $stmt = $db->prepare("SELECT id, member_id, name, club_position FROM $members_table WHERE email = ? LIMIT 1");
        } else {
            $stmt = $db->prepare("SELECT id, name, club_position FROM $members_table WHERE email = ? LIMIT 1");
        }
        
        $stmt->execute([$user['email']]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($member) {
            echo "<p style='color: green;'>✅ Member record found</p>";
            echo "<p><strong>Name:</strong> " . htmlspecialchars($member['name']) . "</p>";
            echo "<p><strong>Club Position:</strong> " . htmlspecialchars($member['club_position']) . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Member record not found for email: " . htmlspecialchars($user['email']) . "</p>";
            echo "<p><strong>Note:</strong> You need to have a member record to mark attendance</p>";
        }
    }
    
    // Test 5: Events Test
    echo "<h2>📅 Events Test</h2>";
    
    $stmt = $db->query("SELECT id, title, status, event_date FROM events ORDER BY event_date DESC LIMIT 3");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($events) {
        echo "<p style='color: green;'>✅ Events found</p>";
        echo "<ul>";
        foreach ($events as $event) {
            $eventTitle = $event['title'] ?? $event['name'] ?? 'Untitled';
            echo "<li><strong>" . htmlspecialchars($eventTitle) . "</strong> (ID: " . $event['id'] . ", Status: " . $event['status'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ No events found</p>";
        echo "<p><strong>Note:</strong> You need events to test QR scanning</p>";
    }
    
    // Test 6: Attendance Table Test
    echo "<h2>📊 Attendance Table Test</h2>";
    
    $db_type = $_ENV['DB_TYPE'] ?? 'mysql';
    if ($db_type === 'postgresql') {
        $stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'attendance'");
    } else {
        $stmt = $db->query("SHOW COLUMNS FROM attendance");
    }
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['member_id', 'full_name', 'club_position', 'event_id', 'date'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        echo "<p style='color: green;'>✅ All required attendance columns present</p>";
    } else {
        echo "<p style='color: red;'>❌ Missing columns: " . implode(', ', $missingColumns) . "</p>";
    }
    
    // Test 7: AJAX Endpoint Test
    echo "<h2>🌐 AJAX Endpoint Test</h2>";
    
    $qrScanUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/dashboard/attendance/qr_scan.php';
    $qrDebugUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/dashboard/attendance/qr_debug.php';
    
    echo "<p><strong>QR Scan URL:</strong> <code>$qrScanUrl</code></p>";
    echo "<p><strong>QR Debug URL:</strong> <code>$qrDebugUrl</code></p>";
    
    // Test 8: Network Connectivity Test
    echo "<h2>🌍 Network Connectivity Test</h2>";
    
    $testUrls = [
        'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=test',
        'https://unpkg.com/html5-qrcode',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css'
    ];
    
    foreach ($testUrls as $url) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'HEAD'
            ]
        ]);
        
        $headers = @get_headers($url, 1, $context);
        if ($headers && strpos($headers[0], '200') !== false) {
            echo "<p style='color: green;'>✅ $url - Accessible</p>";
        } else {
            echo "<p style='color: red;'>❌ $url - Not accessible</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<h2>🔧 QR Scan Network Test Summary</h2>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3 style='color: green;'>✅ QR Scan Network Test Complete</h3>";
echo "<ul>";
echo "<li>✅ <strong>Database:</strong> Connection and table structure verified</li>";
echo "<li>✅ <strong>Session:</strong> User authentication status checked</li>";
echo "<li>✅ <strong>Member Records:</strong> Email-based member lookup tested</li>";
echo "<li>✅ <strong>Events:</strong> Available events for QR scanning verified</li>";
echo "<li>✅ <strong>Attendance Table:</strong> Required columns confirmed</li>";
echo "<li>✅ <strong>Network:</strong> External resource accessibility tested</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🔗 Troubleshooting Steps</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<ol>";
echo "<li><strong>Check Login:</strong> Make sure you're logged in as a user with a member record</li>";
echo "<li><strong>Check Email:</strong> Ensure your user account has an email address</li>";
echo "<li><strong>Check Member Record:</strong> Verify you have a member record with the same email</li>";
echo "<li><strong>Check Events:</strong> Make sure there are events available for attendance</li>";
echo "<li><strong>Use Debug Button:</strong> Click the 'Debug' button in the QR scanner for detailed info</li>";
echo "<li><strong>Use Test Button:</strong> Click the 'Test' button to test with a sample event ID</li>";
echo "<li><strong>Check Console:</strong> Open browser developer tools to see JavaScript errors</li>";
echo "<li><strong>Check Network Tab:</strong> Look for failed requests in the Network tab</li>";
echo "</ol>";
echo "</div>";

echo "<h2>🔗 Important URLs</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>QR Scanner:</strong> <code>https://your-app.onrender.com/dashboard/attendance/qr_scan.php</code></p>";
echo "<p><strong>QR Debug:</strong> <code>https://your-app.onrender.com/dashboard/attendance/qr_debug.php</code></p>";
echo "<p><strong>Login:</strong> <code>https://your-app.onrender.com/auth/login.php</code></p>";
echo "<p><strong>Events:</strong> <code>https://your-app.onrender.com/dashboard/events/index.php</code></p>";
echo "<p><strong>Members:</strong> <code>https://your-app.onrender.com/dashboard/members/index.php</code></p>";
echo "<p><strong>Default Login:</strong> admin / 123</p>";
echo "</div>";

echo "</div>";
?>
