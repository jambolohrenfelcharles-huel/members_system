<?php
/**
 * Create Admin Member Record Migration for Render
 * This script ensures the admin user has a member record for QR scanning
 */

function createAdminMemberRecord($db, $db_type) {
    try {
        echo "🔧 Checking admin member record...\n";
        
        // Get admin user
        $stmt = $db->prepare('SELECT id, username, email, role FROM users WHERE username = ?');
        $stmt->execute(['admin']);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            echo "⚠️ Admin user not found\n";
            return;
        }
        
        echo "✅ Admin user found: " . $admin['email'] . "\n";
        
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
            echo "✅ Admin member record already exists\n";
            return;
        }
        
        echo "📝 Creating admin member record...\n";
        
        // Generate member ID and QR code
        $member_id = 'M' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $qr_code = 'MEMBER_' . time() . '_' . rand(1000, 9999);
        
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
            echo "✅ Admin member record created successfully\n";
        } else {
            echo "❌ Failed to create admin member record\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error creating admin member record: " . $e->getMessage() . "\n";
        throw $e;
    }
}

// If this file is run directly, execute the fix
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        require_once 'config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            throw new Exception("Database connection failed");
        }
        
        $db_type = ($_ENV['DB_TYPE'] ?? 'mysql');
        echo "🔧 Creating admin member record for " . strtoupper($db_type) . "...\n";
        
        createAdminMemberRecord($db, $db_type);
        
        echo "✅ Admin member record creation completed successfully\n";
        
    } catch (Exception $e) {
        echo "❌ Admin member record creation failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>
