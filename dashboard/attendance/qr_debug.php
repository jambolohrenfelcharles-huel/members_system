<?php
/**
 * QR Scan Debug Endpoint
 * This endpoint provides debugging information for QR scanning issues
 */

session_start();
require_once '../../config/database.php';

// Set proper headers for AJAX response
header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $debug_info = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $_SESSION['user_id'] ?? 'not_set',
        'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive',
        'database_type' => $_ENV['DB_TYPE'] ?? 'mysql',
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    // Check if user exists
    if (isset($_SESSION['user_id'])) {
        $stmt = $db->prepare('SELECT id, username, email, role FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $debug_info['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ];
            
            // Check if member record exists
            $members_table = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql' ? 'members' : 'membership_monitoring';
            
            if ($_ENV['DB_TYPE'] === 'postgresql') {
                $stmt = $db->prepare("SELECT id, member_id, name, club_position FROM $members_table WHERE email = ? LIMIT 1");
            } else {
                $stmt = $db->prepare("SELECT id, name, club_position FROM $members_table WHERE email = ? LIMIT 1");
            }
            $stmt->execute([$user['email']]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($member) {
                $debug_info['member'] = $member;
            } else {
                $debug_info['member'] = 'not_found';
            }
        } else {
            $debug_info['user'] = 'not_found';
        }
    }
    
    // Check events table
    $stmt = $db->query("SELECT COUNT(*) as count FROM events");
    $events_count = $stmt->fetch(PDO::FETCH_ASSOC);
    $debug_info['events_count'] = $events_count['count'];
    
    // Check attendance table
    $stmt = $db->query("SELECT COUNT(*) as count FROM attendance");
    $attendance_count = $stmt->fetch(PDO::FETCH_ASSOC);
    $debug_info['attendance_count'] = $attendance_count['count'];
    
    // Check table structure
    $db_type = $_ENV['DB_TYPE'] ?? 'mysql';
    if ($db_type === 'postgresql') {
        $stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'attendance' ORDER BY ordinal_position");
    } else {
        $stmt = $db->query("SHOW COLUMNS FROM attendance");
    }
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $debug_info['attendance_columns'] = $columns;
    
    // Check members table structure
    $members_table = $db_type === 'postgresql' ? 'members' : 'membership_monitoring';
    if ($db_type === 'postgresql') {
        $stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = '$members_table' ORDER BY ordinal_position");
    } else {
        $stmt = $db->query("SHOW COLUMNS FROM $members_table");
    }
    $member_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $debug_info['members_columns'] = $member_columns;
    
    echo json_encode($debug_info, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
