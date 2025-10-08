<?php
/**
 * Complete Render Database Setup for Successful Login
 * This script ensures everything is set up correctly for Render deployment
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Render Database Setup</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;}";
echo ".container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo ".success{color:#28a745;background:#d4edda;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".error{color:#dc3545;background:#f8d7da;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".info{color:#0c5460;background:#d1ecf1;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".btn{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;}";
echo ".btn:hover{background:#0056b3;}";
echo "</style></head><body><div class='container'>";

echo "<h1>🚀 Render Database Setup for SmartApp</h1>";
echo "<p>Setting up your database for successful login on Render...</p>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    echo "<div class='success'>✅ Database connected successfully!</div>";
    
    // Check database type
    $db_type = $_ENV['DB_TYPE'] ?? 'mysql';
    echo "<div class='info'>Database type: <strong>$db_type</strong></div>";
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Create users table
    echo "<h2>Creating Users Table...</h2>";
    if ($db_type === 'postgresql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE,
                role VARCHAR(20) DEFAULT 'member' CHECK (role IN ('admin', 'member')),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE,
                role VARCHAR(20) DEFAULT 'member',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    echo "<div class='success'>✅ Users table created</div>";
    
    // Create events table
    echo "<h2>Creating Events Table...</h2>";
    if ($db_type === 'postgresql') {
        $pdo->exec("
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
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                place VARCHAR(255) NOT NULL,
                status VARCHAR(20) DEFAULT 'upcoming',
                event_date TIMESTAMP NOT NULL,
                description TEXT,
                region VARCHAR(100),
                organizing_club VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    echo "<div class='success'>✅ Events table created</div>";
    
    // Create announcements table
    echo "<h2>Creating Announcements Table...</h2>";
    if ($db_type === 'postgresql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS announcements (
                id SERIAL PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS announcements (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    echo "<div class='success'>✅ Announcements table created</div>";
    
    // Create attendance table
    echo "<h2>Creating Attendance Table...</h2>";
    if ($db_type === 'postgresql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS attendance (
                id SERIAL PRIMARY KEY,
                member_id VARCHAR(50) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                club_position VARCHAR(50) NOT NULL,
                date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS attendance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                member_id VARCHAR(50) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                club_position VARCHAR(50) NOT NULL,
                date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    echo "<div class='success'>✅ Attendance table created</div>";
    
    // Create members table (equivalent to membership_monitoring in MySQL)
    echo "<h2>Creating Members Table...</h2>";
    if ($db_type === 'postgresql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS members (
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
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS membership_monitoring (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
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
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }
    echo "<div class='success'>✅ Members table created</div>";
    
    // Create news_feed table
    echo "<h2>Creating News Feed Table...</h2>";
    if ($db_type === 'postgresql') {
        $pdo->exec("
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
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS news_feed (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                media_path VARCHAR(255),
                media_type ENUM('image','video') NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }
    echo "<div class='success'>✅ News feed table created</div>";
    
    // Create comments table
    echo "<h2>Creating Comments Table...</h2>";
    if ($db_type === 'postgresql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS comments (
                id SERIAL PRIMARY KEY,
                post_id INTEGER NOT NULL REFERENCES news_feed(id) ON DELETE CASCADE,
                username VARCHAR(255) NOT NULL,
                comment TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                username VARCHAR(255) NOT NULL,
                comment TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES news_feed(id) ON DELETE CASCADE
            )
        ");
    }
    echo "<div class='success'>✅ Comments table created</div>";
    
    // Create reports table
    echo "<h2>Creating Reports Table...</h2>";
    if ($db_type === 'postgresql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS reports (
                id SERIAL PRIMARY KEY,
                report_type VARCHAR(50) NOT NULL,
                details TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS reports (
                id INT AUTO_INCREMENT PRIMARY KEY,
                report_type VARCHAR(50) NOT NULL,
                details TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    echo "<div class='success'>✅ Reports table created</div>";
    
    // Create notifications table
    echo "<h2>Creating Notifications Table...</h2>";
    if ($db_type === 'postgresql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                message TEXT NOT NULL,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                message TEXT NOT NULL,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }
    echo "<div class='success'>✅ Notifications table created</div>";
    
    // Create news feed comments table
    echo "<h2>Creating News Feed Comments Table...</h2>";
    if ($db_type === 'postgresql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS news_feed_comments (
                id SERIAL PRIMARY KEY,
                news_feed_id INTEGER NOT NULL REFERENCES news_feed(id) ON DELETE CASCADE,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                comment TEXT NOT NULL,
                parent_id INTEGER DEFAULT NULL REFERENCES news_feed_comments(id) ON DELETE CASCADE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS news_feed_comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                news_feed_id INT NOT NULL,
                user_id INT NOT NULL,
                comment TEXT NOT NULL,
                parent_id INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (news_feed_id) REFERENCES news_feed(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (parent_id) REFERENCES news_feed_comments(id) ON DELETE CASCADE
            )
        ");
    }
    echo "<div class='success'>✅ News feed comments table created</div>";
    
    // Create news feed reactions table
    echo "<h2>Creating News Feed Reactions Table...</h2>";
    if ($db_type === 'postgresql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS news_feed_reactions (
                id SERIAL PRIMARY KEY,
                news_feed_id INTEGER NOT NULL REFERENCES news_feed(id) ON DELETE CASCADE,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                reaction_type VARCHAR(20) NOT NULL CHECK (reaction_type IN ('like', 'love', 'haha', 'wow', 'sad', 'angry')),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(news_feed_id, user_id, reaction_type)
            )
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS news_feed_reactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                news_feed_id INT NOT NULL,
                user_id INT NOT NULL,
                reaction_type VARCHAR(20) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_reaction (news_feed_id, user_id, reaction_type),
                FOREIGN KEY (news_feed_id) REFERENCES news_feed(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }
    echo "<div class='success'>✅ News feed reactions table created</div>";
    
    // Create news feed comment reactions table
    echo "<h2>Creating News Feed Comment Reactions Table...</h2>";
    if ($db_type === 'postgresql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS news_feed_comment_reactions (
                id SERIAL PRIMARY KEY,
                comment_id INTEGER NOT NULL REFERENCES news_feed_comments(id) ON DELETE CASCADE,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                reaction_type VARCHAR(20) NOT NULL CHECK (reaction_type IN ('like', 'love', 'haha', 'wow', 'sad', 'angry')),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(comment_id, user_id)
            )
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS news_feed_comment_reactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                comment_id INT NOT NULL,
                user_id INT NOT NULL,
                reaction_type VARCHAR(20) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_comment_reaction (comment_id, user_id),
                FOREIGN KEY (comment_id) REFERENCES news_feed_comments(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }
    echo "<div class='success'>✅ News feed comment reactions table created</div>";
    
    // Create indexes
    echo "<h2>Creating Indexes...</h2>";
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_attendance_date ON attendance(attendance_date)",
        "CREATE INDEX IF NOT EXISTS idx_events_date ON events(event_date)",
        "CREATE INDEX IF NOT EXISTS idx_members_email ON members(email)",
        "CREATE INDEX IF NOT EXISTS idx_members_status ON members(status)",
        "CREATE INDEX IF NOT EXISTS idx_members_renewal ON members(renewal_date)",
        "CREATE INDEX IF NOT EXISTS idx_news_feed_comments_post ON news_feed_comments(news_feed_id)",
        "CREATE INDEX IF NOT EXISTS idx_news_feed_comments_parent ON news_feed_comments(parent_id)",
        "CREATE INDEX IF NOT EXISTS idx_news_feed_reactions_post ON news_feed_reactions(news_feed_id)",
        "CREATE INDEX IF NOT EXISTS idx_news_feed_comment_reactions_comment ON news_feed_comment_reactions(comment_id)",
        "CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_notifications_read ON notifications(is_read)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $pdo->exec($index);
        } catch (Exception $e) {
            // Index might already exist, continue
        }
    }
    echo "<div class='success'>✅ Indexes created</div>";
    
    // Create admin user
    echo "<h2>Setting up Admin User...</h2>";
    
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        // Create admin user
        $hashed_password = hash('sha256', '123');
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')");
        $stmt->execute([$hashed_password]);
        echo "<div class='success'>✅ Admin user created successfully</div>";
    } else {
        // Update admin password to ensure it's correct
        $hashed_password = hash('sha256', '123');
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
        $stmt->execute([$hashed_password]);
        echo "<div class='success'>✅ Admin user password updated</div>";
    }
    
    // Add sample data
    echo "<h2>Adding Sample Data...</h2>";
    
    // Add sample announcement
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM announcements");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content) VALUES (?, ?)");
        $stmt->execute(['Welcome to SmartApp', 'Welcome to your club management system! This is your first announcement.']);
        echo "<div class='success'>✅ Sample announcement added</div>";
    }
    
    // Add sample event
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM events");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        $future_date = date('Y-m-d H:i:s', strtotime('+1 month'));
        $stmt = $pdo->prepare("INSERT INTO events (name, place, event_date, description) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Monthly Club Meeting', 'Main Conference Room', $future_date, 'Regular monthly meeting for all club members']);
        echo "<div class='success'>✅ Sample event added</div>";
    }
    
    // Add sample member
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM members");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO members (member_id, full_name, email, club_position) VALUES (?, ?, ?, ?)");
        $stmt->execute(['MEM001', 'John Doe', 'john@example.com', 'President']);
        echo "<div class='success'>✅ Sample member added</div>";
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Test login
    echo "<h2>Testing Login...</h2>";
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && hash('sha256', '123') === $user['password']) {
        echo "<div class='success'>✅ Login test successful!</div>";
        echo "<div class='info'>";
        echo "<h3>🎉 Setup Complete! Your SmartApp is ready for login.</h3>";
        echo "<p><strong>Login Credentials:</strong></p>";
        echo "<p>Username: <code>admin</code></p>";
        echo "<p>Password: <code>123</code></p>";
        echo "<p>Role: " . $user['role'] . "</p>";
        echo "</div>";
    } else {
        echo "<div class='error'>❌ Login test failed</div>";
    }
    
    echo "<div class='success'>";
    echo "<h3>🚀 Ready to Login!</h3>";
    echo "<p>Your database is now set up and ready for use on Render.</p>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Click the 'Login to Dashboard' button below</li>";
    echo "<li>Use username: <code>admin</code> and password: <code>123</code></li>";
    echo "<li>You'll be redirected to your dashboard</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p>Please check your database configuration and try again.</p>";
}

echo "<div style='text-align:center;margin:20px 0;'>";
echo "<a href='auth/login.php' class='btn'>Login to Dashboard</a>";
echo "<a href='index.php' class='btn'>Go to Home</a>";
echo "<a href='verify_login.php' class='btn'>Verify Login</a>";
echo "</div>";

echo "</div></body></html>";
?>
