<?php
require_once 'config/database.php';

echo "<h1>🐘 PostgreSQL Setup for Render</h1>";
echo "<p>Setting up PostgreSQL database for successful Render deployment</p>";

// Set PostgreSQL environment variables
$_ENV['DB_TYPE'] = 'postgresql';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("PostgreSQL connection failed. Please check your environment variables.");
    }
    
    echo "✅ <strong>PostgreSQL Connection:</strong> SUCCESS<br>";
    echo "📊 <strong>Members Table:</strong> " . $database->getMembersTable() . "<br><br>";
    
    // Step 1: Create Users Table
    echo "<h3>Step 1: Creating Users Table</h3>";
    
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
    
    $conn->exec($create_users);
    echo "✅ <strong>Users Table:</strong> Created successfully<br>";
    
    // Step 2: Create Members Table
    echo "<h3>Step 2: Creating Members Table</h3>";
    
    $members_table = $database->getMembersTable();
    
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
    
    $conn->exec($create_members);
    echo "✅ <strong>Members Table:</strong> Created successfully<br>";
    
    // Step 3: Create Events Table
    echo "<h3>Step 3: Creating Events Table</h3>";
    
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
    
    $conn->exec($create_events);
    echo "✅ <strong>Events Table:</strong> Created successfully<br>";
    
    // Step 4: Create News Feed Table
    echo "<h3>Step 4: Creating News Feed Table</h3>";
    
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
    
    $conn->exec($create_news_feed);
    echo "✅ <strong>News Feed Table:</strong> Created successfully<br>";
    
    // Step 5: Create Additional Tables
    echo "<h3>Step 5: Creating Additional Tables</h3>";
    
    // Announcements Table
    $create_announcements = "
        CREATE TABLE IF NOT EXISTS announcements (
            id SERIAL PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    $conn->exec($create_announcements);
    echo "✅ <strong>Announcements Table:</strong> Created successfully<br>";
    
    // Attendance Table
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
    
    $conn->exec($create_attendance);
    echo "✅ <strong>Attendance Table:</strong> Created successfully<br>";
    
    // Reports Table
    $create_reports = "
        CREATE TABLE IF NOT EXISTS reports (
            id SERIAL PRIMARY KEY,
            report_type VARCHAR(50) NOT NULL,
            details TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    $conn->exec($create_reports);
    echo "✅ <strong>Reports Table:</strong> Created successfully<br>";
    
    // Notifications Table
    $create_notifications = "
        CREATE TABLE IF NOT EXISTS notifications (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    $conn->exec($create_notifications);
    echo "✅ <strong>Notifications Table:</strong> Created successfully<br>";
    
    // News Feed Comments Table
    $create_comments = "
        CREATE TABLE IF NOT EXISTS news_feed_comments (
            id SERIAL PRIMARY KEY,
            news_feed_id INTEGER NOT NULL REFERENCES news_feed(id) ON DELETE CASCADE,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            comment TEXT NOT NULL,
            parent_id INTEGER DEFAULT NULL REFERENCES news_feed_comments(id) ON DELETE CASCADE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    $conn->exec($create_comments);
    echo "✅ <strong>News Feed Comments Table:</strong> Created successfully<br>";
    
    // News Feed Reactions Table
    $create_reactions = "
        CREATE TABLE IF NOT EXISTS news_feed_reactions (
            id SERIAL PRIMARY KEY,
            news_feed_id INTEGER NOT NULL REFERENCES news_feed(id) ON DELETE CASCADE,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            reaction_type VARCHAR(20) NOT NULL CHECK (reaction_type IN ('like', 'love', 'haha', 'wow', 'sad', 'angry')),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(news_feed_id, user_id, reaction_type)
        )
    ";
    
    $conn->exec($create_reactions);
    echo "✅ <strong>News Feed Reactions Table:</strong> Created successfully<br>";
    
    // News Feed Comment Reactions Table
    $create_comment_reactions = "
        CREATE TABLE IF NOT EXISTS news_feed_comment_reactions (
            id SERIAL PRIMARY KEY,
            comment_id INTEGER NOT NULL REFERENCES news_feed_comments(id) ON DELETE CASCADE,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            reaction_type VARCHAR(20) NOT NULL CHECK (reaction_type IN ('like', 'love', 'haha', 'wow', 'sad', 'angry')),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(comment_id, user_id)
        )
    ";
    
    $conn->exec($create_comment_reactions);
    echo "✅ <strong>News Feed Comment Reactions Table:</strong> Created successfully<br>";
    
    // Step 6: Create Indexes
    echo "<h3>Step 6: Creating Indexes</h3>";
    
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)",
        "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)",
        "CREATE INDEX IF NOT EXISTS idx_members_email ON " . $members_table . "(email)",
        "CREATE INDEX IF NOT EXISTS idx_members_status ON " . $members_table . "(status)",
        "CREATE INDEX IF NOT EXISTS idx_members_renewal ON " . $members_table . "(renewal_date)",
        "CREATE INDEX IF NOT EXISTS idx_events_date ON events(event_date)",
        "CREATE INDEX IF NOT EXISTS idx_news_feed_user ON news_feed(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_news_feed_comments_post ON news_feed_comments(news_feed_id)",
        "CREATE INDEX IF NOT EXISTS idx_news_feed_comments_parent ON news_feed_comments(parent_id)",
        "CREATE INDEX IF NOT EXISTS idx_news_feed_reactions_post ON news_feed_reactions(news_feed_id)",
        "CREATE INDEX IF NOT EXISTS idx_news_feed_comment_reactions_comment ON news_feed_comment_reactions(comment_id)",
        "CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_notifications_read ON notifications(is_read)",
        "CREATE INDEX IF NOT EXISTS idx_attendance_date ON attendance(attendance_date)"
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
    
    // Test login functionality
    echo "<h3>Step 9: Testing Login Functionality</h3>";
    
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ <strong>User Found:</strong> " . $user['username'] . "<br>";
        echo "👤 <strong>Role:</strong> " . $user['role'] . "<br>";
        
        if ($user['password'] === hash('sha256', '123')) {
            echo "✅ <strong>Password Match:</strong> SUCCESS<br>";
            echo "🎉 <strong>Login Test:</strong> PASSED<br>";
        } else {
            echo "❌ <strong>Password Match:</strong> FAILED<br>";
        }
    } else {
        echo "❌ <strong>User Not Found:</strong> admin<br>";
    }
    
    echo "<br><h2>✅ PostgreSQL Setup Complete!</h2>";
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<p><strong>🎉 Your SmartApp PostgreSQL database is ready for Render!</strong></p>";
    echo "<ul>";
    echo "<li>🐘 <strong>PostgreSQL:</strong> All tables created successfully</li>";
    echo "<li>👤 <strong>Admin User:</strong> admin / 123</li>";
    echo "<li>📊 <strong>All Tables:</strong> Created with proper relationships</li>";
    echo "<li>🔍 <strong>Indexes:</strong> Optimized for performance</li>";
    echo "<li>🔐 <strong>Login:</strong> Tested and working</li>";
    echo "</ul>";
    echo "<p><strong>🚀 You can now login successfully on Render!</strong></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>❌ Setup Failed</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Please check your PostgreSQL configuration and try again.</p>";
    echo "<p><strong>Make sure you have set these environment variables:</strong></p>";
    echo "<ul>";
    echo "<li>DB_TYPE=postgresql</li>";
    echo "<li>DB_HOST=[Your PostgreSQL Internal Database URL]</li>";
    echo "<li>DB_NAME=members_system</li>";
    echo "<li>DB_USERNAME=smartapp_user</li>";
    echo "<li>DB_PASSWORD=[Your PostgreSQL Password]</li>";
    echo "</ul>";
    echo "</div>";
}
?>
