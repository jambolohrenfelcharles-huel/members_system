<?php
require_once 'config/database.php';

echo "<h1>SmartApp Universal Database Setup</h1>";
echo "<p>Setting up database for both MySQL (Local) and PostgreSQL (Render)</p>";

// Get database type from environment
$db_type = $_ENV['DB_TYPE'] ?? 'mysql';
echo "<h2>Database Type: " . strtoupper($db_type) . "</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    echo "✅ <strong>Database Connection:</strong> SUCCESS<br>";
    echo "📊 <strong>Members Table:</strong> " . $database->getMembersTable() . "<br><br>";
    
    // Step 1: Create Users Table
    echo "<h3>Step 1: Creating Users Table</h3>";
    
    if ($db_type === 'postgresql') {
        $create_users = "
            CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(20) DEFAULT 'member',
                email VARCHAR(255) UNIQUE,
                full_name VARCHAR(255),
                reset_token VARCHAR(255),
                reset_expires TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
    } else {
        $create_users = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin', 'member') DEFAULT 'member',
                email VARCHAR(255) UNIQUE,
                full_name VARCHAR(255),
                reset_token VARCHAR(255),
                reset_expires TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
    }
    
    $conn->exec($create_users);
    echo "✅ <strong>Users Table:</strong> Created successfully<br>";
    
    // Step 2: Create Members Table
    echo "<h3>Step 2: Creating Members Table</h3>";
    
    $members_table = $database->getMembersTable();
    
    if ($db_type === 'postgresql') {
        $create_members = "
            CREATE TABLE IF NOT EXISTS " . $members_table . " (
                id SERIAL PRIMARY KEY,
                user_id INT REFERENCES users(id) ON DELETE CASCADE,
                member_id VARCHAR(50) UNIQUE NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255),
                club_position VARCHAR(100),
                home_address TEXT,
                contact_number VARCHAR(20),
                phone VARCHAR(20),
                philhealth_number VARCHAR(50),
                pagibig_number VARCHAR(50),
                tin_number VARCHAR(50),
                birthdate DATE,
                height DECIMAL(5,2),
                weight DECIMAL(5,2),
                blood_type VARCHAR(10),
                religion VARCHAR(100),
                emergency_contact_person VARCHAR(255),
                emergency_contact_number VARCHAR(20),
                club_affiliation VARCHAR(255),
                region VARCHAR(100),
                qr_code TEXT,
                image_path VARCHAR(500),
                status VARCHAR(20) DEFAULT 'active',
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
                email VARCHAR(255),
                club_position VARCHAR(100),
                home_address TEXT,
                contact_number VARCHAR(20),
                phone VARCHAR(20),
                philhealth_number VARCHAR(50),
                pagibig_number VARCHAR(50),
                tin_number VARCHAR(50),
                birthdate DATE,
                height DECIMAL(5,2),
                weight DECIMAL(5,2),
                blood_type VARCHAR(10),
                religion VARCHAR(100),
                emergency_contact_person VARCHAR(255),
                emergency_contact_number VARCHAR(20),
                club_affiliation VARCHAR(255),
                region VARCHAR(100),
                qr_code TEXT,
                image_path VARCHAR(500),
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
    
    // Step 3: Create Events Table
    echo "<h3>Step 3: Creating Events Table</h3>";
    
    if ($db_type === 'postgresql') {
        $create_events = "
            CREATE TABLE IF NOT EXISTS events (
                id SERIAL PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                event_date DATE NOT NULL,
                event_time TIME,
                location VARCHAR(255),
                organizer VARCHAR(255),
                status VARCHAR(20) DEFAULT 'upcoming',
                region VARCHAR(100),
                organizing_club VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
    } else {
        $create_events = "
            CREATE TABLE IF NOT EXISTS events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                event_date DATE NOT NULL,
                event_time TIME,
                location VARCHAR(255),
                organizer VARCHAR(255),
                status ENUM('upcoming', 'ongoing', 'completed') DEFAULT 'upcoming',
                region VARCHAR(100),
                organizing_club VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
    }
    
    $conn->exec($create_events);
    echo "✅ <strong>Events Table:</strong> Created successfully<br>";
    
    // Step 4: Create News Feed Table
    echo "<h3>Step 4: Creating News Feed Table</h3>";
    
    if ($db_type === 'postgresql') {
        $create_news_feed = "
            CREATE TABLE IF NOT EXISTS news_feed (
                id SERIAL PRIMARY KEY,
                user_id INT REFERENCES users(id) ON DELETE CASCADE,
                title VARCHAR(255),
                description TEXT,
                image_path VARCHAR(500),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
    } else {
        $create_news_feed = "
            CREATE TABLE IF NOT EXISTS news_feed (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                title VARCHAR(255),
                description TEXT,
                image_path VARCHAR(500),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
    }
    
    $conn->exec($create_news_feed);
    echo "✅ <strong>News Feed Table:</strong> Created successfully<br>";
    
    // Step 5: Create Additional Tables
    echo "<h3>Step 5: Creating Additional Tables</h3>";
    
    // Announcements Table
    if ($db_type === 'postgresql') {
        $create_announcements = "
            CREATE TABLE IF NOT EXISTS announcements (
                id SERIAL PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    
    // Attendance Table
    if ($db_type === 'postgresql') {
        $create_attendance = "
            CREATE TABLE IF NOT EXISTS attendance (
                id SERIAL PRIMARY KEY,
                member_id INT REFERENCES " . $members_table . "(id) ON DELETE CASCADE,
                event_id INT REFERENCES events(id) ON DELETE CASCADE,
                attendance_date DATE DEFAULT CURRENT_DATE,
                status VARCHAR(20) DEFAULT 'present',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
    } else {
        $create_attendance = "
            CREATE TABLE IF NOT EXISTS attendance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                member_id INT,
                event_id INT,
                attendance_date DATE DEFAULT (CURDATE()),
                status ENUM('present', 'absent', 'late') DEFAULT 'present',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (member_id) REFERENCES " . $members_table . "(id) ON DELETE CASCADE,
                FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
            )
        ";
    }
    
    $conn->exec($create_attendance);
    echo "✅ <strong>Attendance Table:</strong> Created successfully<br>";
    
    // Step 6: Create Indexes
    echo "<h3>Step 6: Creating Indexes</h3>";
    
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)",
        "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)",
        "CREATE INDEX IF NOT EXISTS idx_members_email ON " . $members_table . "(email)",
        "CREATE INDEX IF NOT EXISTS idx_members_status ON " . $members_table . "(status)",
        "CREATE INDEX IF NOT EXISTS idx_events_date ON events(event_date)",
        "CREATE INDEX IF NOT EXISTS idx_news_feed_user ON news_feed(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_attendance_member ON attendance(member_id)",
        "CREATE INDEX IF NOT EXISTS idx_attendance_event ON attendance(event_id)"
    ];
    
    foreach ($indexes as $index) {
        $conn->exec($index);
    }
    echo "✅ <strong>Indexes:</strong> Created successfully<br>";
    
    // Step 7: Create Admin User
    echo "<h3>Step 7: Creating Admin User</h3>";
    
    // Check if admin user exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin_exists = $stmt->fetchColumn();
    
    if ($admin_exists == 0) {
        $admin_password = hash('sha256', '123');
        $admin_email = 'admin@smartapp.com';
        $admin_full_name = 'System Administrator';
        
        if ($db_type === 'postgresql') {
            $insert_admin = "
                INSERT INTO users (username, password, role, email, full_name) 
                VALUES ('admin', ?, 'admin', ?, ?)
            ";
        } else {
            $insert_admin = "
                INSERT INTO users (username, password, role, email, full_name) 
                VALUES ('admin', ?, 'admin', ?, ?)
            ";
        }
        
        $stmt = $conn->prepare($insert_admin);
        $stmt->execute([$admin_password, $admin_email, $admin_full_name]);
        
        echo "✅ <strong>Admin User:</strong> Created successfully<br>";
        echo "👤 <strong>Username:</strong> admin<br>";
        echo "🔑 <strong>Password:</strong> 123<br>";
        echo "📧 <strong>Email:</strong> " . $admin_email . "<br>";
    } else {
        echo "✅ <strong>Admin User:</strong> Already exists<br>";
    }
    
    // Step 8: Test Database
    echo "<h3>Step 8: Testing Database</h3>";
    
    // Test users table
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    $user_count = $stmt->fetchColumn();
    echo "👥 <strong>Users Count:</strong> " . $user_count . "<br>";
    
    // Test members table
    $stmt = $conn->prepare("SELECT COUNT(*) FROM " . $members_table);
    $stmt->execute();
    $member_count = $stmt->fetchColumn();
    echo "👤 <strong>Members Count:</strong> " . $member_count . "<br>";
    
    // Test events table
    $stmt = $conn->prepare("SELECT COUNT(*) FROM events");
    $stmt->execute();
    $event_count = $stmt->fetchColumn();
    echo "📅 <strong>Events Count:</strong> " . $event_count . "<br>";
    
    // Test news feed table
    $stmt = $conn->prepare("SELECT COUNT(*) FROM news_feed");
    $stmt->execute();
    $news_count = $stmt->fetchColumn();
    echo "📰 <strong>News Feed Count:</strong> " . $news_count . "<br>";
    
    echo "<br><h2>✅ Database Setup Complete!</h2>";
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<p><strong>🎉 Your SmartApp database is ready for both MySQL and PostgreSQL!</strong></p>";
    echo "<ul>";
    echo "<li>🐬 <strong>MySQL:</strong> Ready for local development</li>";
    echo "<li>🐘 <strong>PostgreSQL:</strong> Ready for Render deployment</li>";
    echo "<li>👤 <strong>Admin User:</strong> admin / 123</li>";
    echo "<li>📊 <strong>All Tables:</strong> Created with proper relationships</li>";
    echo "<li>🔍 <strong>Indexes:</strong> Optimized for performance</li>";
    echo "</ul>";
    echo "<p><strong>🚀 You can now deploy to Render successfully!</strong></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>❌ Setup Failed</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
    echo "</div>";
}
?>
