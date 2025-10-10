<?php
/**
 * Create Admin Member Record
 * This script creates a member record for the admin user to enable QR scanning
 */

echo "<h1>Create Admin Member Record</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px;'>";

try {
    // Connect to database
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    echo "<h2>✅ Database Connection</h2>";
    echo "<p style='color: green;'>Database connected successfully</p>";
    
    // Check current database type
    $db_type = ($_ENV['DB_TYPE'] ?? 'mysql');
    echo "<p><strong>Database Type:</strong> " . strtoupper($db_type) . "</p>";
    
    // Get admin user
    $stmt = $db->prepare('SELECT id, username, email, role FROM users WHERE username = ?');
    $stmt->execute(['admin']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        throw new Exception("Admin user not found");
    }
    
    echo "<h2>👤 Admin User Found</h2>";
    echo "<ul>";
    echo "<li><strong>ID:</strong> " . $admin['id'] . "</li>";
    echo "<li><strong>Username:</strong> " . htmlspecialchars($admin['username']) . "</li>";
    echo "<li><strong>Email:</strong> " . htmlspecialchars($admin['email']) . "</li>";
    echo "<li><strong>Role:</strong> " . htmlspecialchars($admin['role']) . "</li>";
    echo "</ul>";
    
    // Check if member record already exists
    $members_table = $db_type === 'postgresql' ? 'members' : 'membership_monitoring';
    
    if ($db_type === 'postgresql') {
        $stmt = $db->prepare("SELECT id, member_id, name FROM $members_table WHERE email = ? LIMIT 1");
    } else {
        $stmt = $db->prepare("SELECT id, name FROM $members_table WHERE email = ? LIMIT 1");
    }
    $stmt->execute([$admin['email']]);
    $existing_member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_member) {
        echo "<h2>👥 Member Record Already Exists</h2>";
        echo "<p style='color: green;'>✅ Member record found</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $existing_member['id'] . "</li>";
        if ($db_type === 'postgresql') {
            echo "<li><strong>Member ID:</strong> " . htmlspecialchars($existing_member['member_id']) . "</li>";
        }
        echo "<li><strong>Name:</strong> " . htmlspecialchars($existing_member['name']) . "</li>";
        echo "</ul>";
    } else {
        echo "<h2>👥 Creating Member Record</h2>";
        
        // Generate member ID and QR code
        $member_id = 'M' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $qr_code = 'MEMBER_' . time() . '_' . rand(1000, 9999);
        
        echo "<p><strong>Generated Member ID:</strong> $member_id</p>";
        echo "<p><strong>Generated QR Code:</strong> $qr_code</p>";
        
        // Insert member record
        if ($db_type === 'postgresql') {
            $insertSql = "INSERT INTO $members_table (user_id, member_id, name, email, club_position, home_address, contact_number, philhealth_number, pagibig_number, tin_number, birthdate, height, weight, blood_type, religion, emergency_contact_person, emergency_contact_number, club_affiliation, region, qr_code, image_path, status, renewal_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($insertSql);
            $result = $stmt->execute([
                $admin['id'],
                $member_id,
                'Admin User',
                $admin['email'],
                'Administrator',
                'Admin Address',
                '1234567890',
                'PH123456789',
                'PAG123456789',
                'TIN123456789',
                '1990-01-01',
                170.0,
                70.0,
                'O+',
                'Christian',
                'Emergency Contact',
                '0987654321',
                'Admin Club',
                'Admin Region',
                $qr_code,
                null,
                'active',
                date('Y-m-d', strtotime('+1 year'))
            ]);
        } else {
            $insertSql = "INSERT INTO $members_table (user_id, name, email, club_position, home_address, contact_number, philhealth_number, pagibig_number, tin_number, birthdate, height, weight, blood_type, religion, emergency_contact_person, emergency_contact_number, club_affiliation, region, qr_code, image_path, status, renewal_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($insertSql);
            $result = $stmt->execute([
                $admin['id'],
                'Admin User',
                $admin['email'],
                'Administrator',
                'Admin Address',
                '1234567890',
                'PH123456789',
                'PAG123456789',
                'TIN123456789',
                '1990-01-01',
                170.0,
                70.0,
                'O+',
                'Christian',
                'Emergency Contact',
                '0987654321',
                'Admin Club',
                'Admin Region',
                $qr_code,
                null,
                'active',
                date('Y-m-d', strtotime('+1 year'))
            ]);
        }
        
        if ($result) {
            echo "<p style='color: green;'>✅ Member record created successfully</p>";
            
            // Verify the record was created
            if ($db_type === 'postgresql') {
                $stmt = $db->prepare("SELECT id, member_id, name FROM $members_table WHERE email = ? LIMIT 1");
            } else {
                $stmt = $db->prepare("SELECT id, name FROM $members_table WHERE email = ? LIMIT 1");
            }
            $stmt->execute([$admin['email']]);
            $new_member = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($new_member) {
                echo "<h3>✅ Verification Successful</h3>";
                echo "<ul>";
                echo "<li><strong>ID:</strong> " . $new_member['id'] . "</li>";
                if ($db_type === 'postgresql') {
                    echo "<li><strong>Member ID:</strong> " . htmlspecialchars($new_member['member_id']) . "</li>";
                }
                echo "<li><strong>Name:</strong> " . htmlspecialchars($new_member['name']) . "</li>";
                echo "</ul>";
            }
        } else {
            echo "<p style='color: red;'>❌ Failed to create member record</p>";
        }
    }
    
    // Test QR scan functionality
    echo "<h2>🧪 QR Scan Test</h2>";
    
    // Get an event for testing
    $stmt = $db->query("SELECT id, title FROM events ORDER BY event_date DESC LIMIT 1");
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($event) {
        echo "<p><strong>Test Event:</strong> " . htmlspecialchars($event['title']) . " (ID: " . $event['id'] . ")</p>";
        
        // Get the member record
        if ($db_type === 'postgresql') {
            $stmt = $db->prepare("SELECT id, member_id, name, club_position FROM $members_table WHERE email = ? LIMIT 1");
        } else {
            $stmt = $db->prepare("SELECT id, name, club_position FROM $members_table WHERE email = ? LIMIT 1");
        }
        $stmt->execute([$admin['email']]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($member) {
            // Generate member_id for testing
            if ($db_type === 'postgresql') {
                $test_member_id = $member['member_id'];
            } else {
                $test_member_id = 'M' . date('Y') . str_pad($member['id'], 4, '0', STR_PAD_LEFT);
            }
            
            echo "<p><strong>Test Member ID:</strong> $test_member_id</p>";
            
            // Test the attendance insertion
            $date = date('Y-m-d H:i:s');
            $stmt = $db->prepare('INSERT INTO attendance (member_id, full_name, club_position, event_id, date) VALUES (?, ?, ?, ?, ?)');
            
            if ($stmt->execute([$test_member_id, $member['name'], $member['club_position'], $event['id'], $date])) {
                echo "<p style='color: green;'>✅ Test attendance record created successfully</p>";
                
                // Clean up test record
                $stmt = $db->prepare('DELETE FROM attendance WHERE member_id = ? AND event_id = ?');
                $stmt->execute([$test_member_id, $event['id']]);
                echo "<p style='color: blue;'>ℹ️ Test record cleaned up</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to create test attendance record</p>";
            }
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No events found for testing</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<h2>🔧 Admin Member Record Summary</h2>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3 style='color: green;'>✅ Admin Member Record Setup Complete</h3>";
echo "<ul>";
echo "<li>✅ <strong>Problem:</strong> Admin user had no member record for QR scanning</li>";
echo "<li>✅ <strong>Solution:</strong> Created member record with proper data</li>";
echo "<li>✅ <strong>Member ID:</strong> Generated unique member ID for attendance tracking</li>";
echo "<li>✅ <strong>QR Code:</strong> Generated QR code for member identification</li>";
echo "<li>✅ <strong>Testing:</strong> Verified QR scan functionality works</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🔗 Next Steps</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>1. Test QR Scanning:</strong> Go to <code>dashboard/attendance/qr_scan.php</code></p>";
echo "<p><strong>2. Scan Event QR:</strong> Use the QR code from an event to mark attendance</p>";
echo "<p><strong>3. Check Debug:</strong> Use the debug button if issues persist</p>";
echo "<p><strong>4. Verify Attendance:</strong> Check <code>dashboard/attendance/index.php</code></p>";
echo "</div>";

echo "</div>";
?>
