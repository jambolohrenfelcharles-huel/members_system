<?php
// Simple login fix for Render - Quick Setup
require_once 'config/database.php';

echo "<h1>🔧 Quick Login Fix for Render</h1>";
echo "<p>Setting up database and admin user for successful login</p>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>❌ Database Connection Failed</h3>";
        echo "<p>Please check your environment variables:</p>";
        echo "<ul>";
        echo "<li>DB_TYPE=postgresql</li>";
        echo "<li>DB_HOST=[Your PostgreSQL Internal Database URL]</li>";
        echo "<li>DB_NAME=members_system</li>";
        echo "<li>DB_USERNAME=smartapp_user</li>";
        echo "<li>DB_PASSWORD=[Your PostgreSQL Password]</li>";
        echo "</ul>";
        echo "</div>";
        exit;
    }
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "✅ <strong>Database Connected!</strong><br>";
    echo "📊 <strong>Database Type:</strong> " . ($_ENV['DB_TYPE'] ?? 'mysql') . "<br>";
    echo "📊 <strong>Members Table:</strong> " . $database->getMembersTable() . "<br>";
    echo "</div>";
    
    // Create users table
    echo "<h3>Creating Users Table...</h3>";
    $db_type = $_ENV['DB_TYPE'] ?? 'mysql';
    
    if ($db_type === 'postgresql') {
        $create_users = "
            CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE,
                full_name VARCHAR(255),
                role VARCHAR(20) DEFAULT 'member' CHECK (role IN ('admin', 'member')),
                reset_token VARCHAR(100) DEFAULT NULL,
                reset_expires TIMESTAMP DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
    } else {
        $create_users = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE,
                full_name VARCHAR(255),
                role ENUM('admin', 'member') DEFAULT 'member',
                reset_token VARCHAR(100) DEFAULT NULL,
                reset_expires TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
    }
    
    $conn->exec($create_users);
    echo "✅ <strong>Users Table:</strong> Created successfully<br>";
    
    // Create members table
    echo "<h3>Creating Members Table...</h3>";
    $members_table = $database->getMembersTable();
    
    if ($db_type === 'postgresql') {
        $create_members = "
            CREATE TABLE IF NOT EXISTS " . $members_table . " (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                member_id VARCHAR(50) UNIQUE NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE,
                club_position VARCHAR(100),
                home_address TEXT NOT NULL,
                contact_number VARCHAR(20) NOT NULL,
                phone VARCHAR(20),
                philhealth_number VARCHAR(50),
                pagibig_number VARCHAR(50),
                tin_number VARCHAR(50),
                birthdate DATE NOT NULL,
                height DECIMAL(5,2),
                weight DECIMAL(5,2),
                blood_type VARCHAR(5),
                religion VARCHAR(50),
                emergency_contact_person VARCHAR(255) NOT NULL,
                emergency_contact_number VARCHAR(20) NOT NULL,
                club_affiliation VARCHAR(255),
                region VARCHAR(100),
                qr_code VARCHAR(255) UNIQUE NOT NULL,
                image_path VARCHAR(255),
                status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
                renewal_date DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
    } else {
        $create_members = "
            CREATE TABLE IF NOT EXISTS " . $members_table . " (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                member_id VARCHAR(50) UNIQUE NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE,
                club_position VARCHAR(100),
                home_address TEXT NOT NULL,
                contact_number VARCHAR(20) NOT NULL,
                phone VARCHAR(20),
                philhealth_number VARCHAR(50),
                pagibig_number VARCHAR(50),
                tin_number VARCHAR(50),
                birthdate DATE NOT NULL,
                height DECIMAL(5,2),
                weight DECIMAL(5,2),
                blood_type VARCHAR(5),
                religion VARCHAR(50),
                emergency_contact_person VARCHAR(255) NOT NULL,
                emergency_contact_number VARCHAR(20) NOT NULL,
                club_affiliation VARCHAR(255),
                region VARCHAR(100),
                qr_code VARCHAR(255) UNIQUE NOT NULL,
                image_path VARCHAR(255),
                status ENUM('active', 'inactive') DEFAULT 'active',
                renewal_date DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
    }
    
    $conn->exec($create_members);
    echo "✅ <strong>Members Table:</strong> Created successfully<br>";
    
    // Create events table
    echo "<h3>Creating Events Table...</h3>";
    if ($db_type === 'postgresql') {
        $create_events = "
            CREATE TABLE IF NOT EXISTS events (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                place VARCHAR(255) NOT NULL,
                status VARCHAR(20) DEFAULT 'upcoming' CHECK (status IN ('upcoming', 'ongoing', 'completed')),
                event_date TIMESTAMP NOT NULL,
                description TEXT,
                region VARCHAR(100),
                organizing_club VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
    } else {
        $create_events = "
            CREATE TABLE IF NOT EXISTS events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                place VARCHAR(255) NOT NULL,
                status ENUM('upcoming', 'ongoing', 'completed') DEFAULT 'upcoming',
                event_date TIMESTAMP NOT NULL,
                description TEXT,
                region VARCHAR(100),
                organizing_club VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
    }
    
    $conn->exec($create_events);
    echo "✅ <strong>Events Table:</strong> Created successfully<br>";
    
    // Create news feed table
    echo "<h3>Creating News Feed Table...</h3>";
    if ($db_type === 'postgresql') {
        $create_news_feed = "
            CREATE TABLE IF NOT EXISTS news_feed (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                media_path VARCHAR(500),
                media_type VARCHAR(20) NOT NULL CHECK (media_type IN ('image', 'video')),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
    } else {
        $create_news_feed = "
            CREATE TABLE IF NOT EXISTS news_feed (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                media_path VARCHAR(500),
                media_type ENUM('image', 'video') NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
    }
    
    $conn->exec($create_news_feed);
    echo "✅ <strong>News Feed Table:</strong> Created successfully<br>";
    
    // Create announcements table
    echo "<h3>Creating Announcements Table...</h3>";
    if ($db_type === 'postgresql') {
        $create_announcements = "
            CREATE TABLE IF NOT EXISTS announcements (
                id SERIAL PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
    } else {
        $create_announcements = "
            CREATE TABLE IF NOT EXISTS announcements (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
    }
    
    $conn->exec($create_announcements);
    echo "✅ <strong>Announcements Table:</strong> Created successfully<br>";
    
    // Create attendance table
    echo "<h3>Creating Attendance Table...</h3>";
    if ($db_type === 'postgresql') {
        $create_attendance = "
            CREATE TABLE IF NOT EXISTS attendance (
                id SERIAL PRIMARY KEY,
                member_id VARCHAR(50) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                club_position VARCHAR(50) NOT NULL,
                date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                attendance_date DATE GENERATED ALWAYS AS (date::date) STORED
            )
        ";
    } else {
        $create_attendance = "
            CREATE TABLE IF NOT EXISTS attendance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                member_id VARCHAR(50) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                club_position VARCHAR(50) NOT NULL,
                date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                attendance_date DATE DEFAULT (CURDATE())
            )
        ";
    }
    
    $conn->exec($create_attendance);
    echo "✅ <strong>Attendance Table:</strong> Created successfully<br>";
    
    // Create admin user
    echo "<h3>Creating Admin User...</h3>";
    
    // Check if admin exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin_exists = $stmt->fetchColumn();
    
    if ($admin_exists == 0) {
        $admin_password = hash('sha256', '123');
        $admin_email = 'admin@smartapp.com';
        $admin_full_name = 'System Administrator';
        
        $insert_admin = "
            INSERT INTO users (username, password, email, full_name, role) 
            VALUES ('admin', ?, ?, ?, 'admin')
        ";
        
        $stmt = $conn->prepare($insert_admin);
        $stmt->execute([$admin_password, $admin_email, $admin_full_name]);
        
        echo "✅ <strong>Admin User:</strong> Created successfully<br>";
    } else {
        // Update admin password to ensure it's correct
        $admin_password = hash('sha256', '123');
        $admin_email = 'admin@smartapp.com';
        $admin_full_name = 'System Administrator';
        
        $update_admin = "UPDATE users SET password = ?, email = ?, full_name = ? WHERE username = 'admin'";
        $stmt = $conn->prepare($update_admin);
        $stmt->execute([$admin_password, $admin_email, $admin_full_name]);
        
        echo "✅ <strong>Admin User:</strong> Updated successfully<br>";
    }
    
    echo "👤 <strong>Username:</strong> admin<br>";
    echo "🔑 <strong>Password:</strong> 123<br>";
    echo "📧 <strong>Email:</strong> admin@smartapp.com<br>";
    
    // Test login
    echo "<h3>Testing Login...</h3>";
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['password'] === hash('sha256', '123')) {
        echo "✅ <strong>Login Test:</strong> SUCCESS<br>";
        echo "🎉 <strong>Ready for Login!</strong><br>";
    } else {
        echo "❌ <strong>Login Test:</strong> FAILED<br>";
    }
    
    echo "<br><h2>🎉 Setup Complete!</h2>";
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<p><strong>✅ Your SmartApp is ready for login on Render!</strong></p>";
    echo "<p><strong>Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> admin</li>";
    echo "<li><strong>Password:</strong> 123</li>";
    echo "</ul>";
    echo "<p><strong>🚀 You can now login at:</strong> <a href='auth/login.php'>auth/login.php</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>❌ Setup Failed</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
    echo "</div>";
}
?>
