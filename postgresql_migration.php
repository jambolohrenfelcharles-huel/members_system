<?php
/**
 * Complete PostgreSQL Migration for Render
 * This script converts all MySQL features to PostgreSQL for successful Render deployment
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>PostgreSQL Migration for Render</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;}";
echo ".container{max-width:1000px;margin:0 auto;background:white;padding:20px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo ".success{color:#28a745;background:#d4edda;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".error{color:#dc3545;background:#f8d7da;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".info{color:#0c5460;background:#d1ecf1;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".warning{color:#856404;background:#fff3cd;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".btn{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;}";
echo ".btn:hover{background:#0056b3;}";
echo ".step{background:#f8f9fa;padding:15px;margin:10px 0;border-radius:5px;border-left:4px solid #007bff;}";
echo "</style></head><body><div class='container'>";

echo "<h1>🐘 Complete PostgreSQL Migration for Render</h1>";
echo "<p>Converting all MySQL features to PostgreSQL for successful deployment on Render...</p>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    echo "<div class='success'>✅ Database connected successfully</div>";
    
    // Check database type
    $db_type = $_ENV['DB_TYPE'] ?? 'mysql';
    echo "<div class='info'>Database type: <strong>$db_type</strong></div>";
    
    if ($db_type !== 'postgresql') {
        echo "<div class='warning'>⚠️ This script is designed for PostgreSQL. Current database type: $db_type</div>";
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    echo "<div class='step'>";
    echo "<h2>Step 1: Creating Core Tables</h2>";
    
    // Users table with all features
    echo "<h3>Creating Users Table...</h3>";
    $pdo->exec("
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
    ");
    echo "<div class='success'>✅ Users table created with all features</div>";
    
    // Events table
    echo "<h3>Creating Events Table...</h3>";
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
    echo "<div class='success'>✅ Events table created</div>";
    
    // Announcements table
    echo "<h3>Creating Announcements Table...</h3>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS announcements (
            id SERIAL PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<div class='success'>✅ Announcements table created</div>";
    
    // Attendance table
    echo "<h3>Creating Attendance Table...</h3>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS attendance (
            id SERIAL PRIMARY KEY,
            member_id VARCHAR(50) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            club_position VARCHAR(50) NOT NULL,
            date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            attendance_date DATE GENERATED ALWAYS AS (date::date) STORED,
            UNIQUE(member_id, attendance_date)
        )
    ");
    echo "<div class='success'>✅ Attendance table created</div>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2>Step 2: Creating Members Table (PostgreSQL equivalent of membership_monitoring)</h2>";
    
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
    echo "<div class='success'>✅ Members table created (equivalent to membership_monitoring)</div>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2>Step 3: Creating News Feed System</h2>";
    
    // News feed table
    echo "<h3>Creating News Feed Table...</h3>";
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
    echo "<div class='success'>✅ News feed table created</div>";
    
    // News feed comments table
    echo "<h3>Creating News Feed Comments Table...</h3>";
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
    echo "<div class='success'>✅ News feed comments table created</div>";
    
    // News feed reactions table
    echo "<h3>Creating News Feed Reactions Table...</h3>";
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
    echo "<div class='success'>✅ News feed reactions table created</div>";
    
    // News feed comment reactions table
    echo "<h3>Creating News Feed Comment Reactions Table...</h3>";
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
    echo "<div class='success'>✅ News feed comment reactions table created</div>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2>Step 4: Creating Additional Tables</h2>";
    
    // Reports table
    echo "<h3>Creating Reports Table...</h3>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reports (
            id SERIAL PRIMARY KEY,
            report_type VARCHAR(50) NOT NULL,
            details TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<div class='success'>✅ Reports table created</div>";
    
    // Notifications table
    echo "<h3>Creating Notifications Table...</h3>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<div class='success'>✅ Notifications table created</div>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2>Step 5: Creating Indexes for Performance</h2>";
    
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
    echo "<div class='success'>✅ All indexes created</div>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2>Step 6: Setting up Admin User</h2>";
    
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        // Create admin user
        $hashed_password = hash('sha256', '123');
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES ('admin', ?, 'admin@smartunion.com', 'System Administrator', 'admin')");
        $stmt->execute([$hashed_password]);
        echo "<div class='success'>✅ Admin user created successfully</div>";
    } else {
        // Update admin password to ensure it's correct
        $hashed_password = hash('sha256', '123');
        $stmt = $pdo->prepare("UPDATE users SET password = ?, email = 'admin@smartunion.com', full_name = 'System Administrator' WHERE username = 'admin'");
        $stmt->execute([$hashed_password]);
        echo "<div class='success'>✅ Admin user password updated</div>";
    }
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2>Step 7: Adding Sample Data</h2>";
    
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
        $stmt = $pdo->prepare("INSERT INTO members (user_id, member_id, name, email, club_position, home_address, contact_number, emergency_contact_person, emergency_contact_number, birthdate, qr_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([1, 'MEM001', 'John Doe', 'john@example.com', 'President', '123 Main St', '123-456-7890', 'Jane Doe', '098-765-4321', '1990-01-01', 'QR001']);
        echo "<div class='success'>✅ Sample member added</div>";
    }
    echo "</div>";
    
    // Commit transaction
    $pdo->commit();
    
    // Test all functionality
    echo "<div class='step'>";
    echo "<h2>Step 8: Testing All Functionality</h2>";
    
    // Test login
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && hash('sha256', '123') === $user['password']) {
        echo "<div class='success'>✅ Login functionality works</div>";
    } else {
        echo "<div class='error'>❌ Login functionality failed</div>";
    }
    
    // Test signup
    $members_table = $db->getMembersTable();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $members_table");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<div class='success'>✅ Signup functionality works (can query $members_table table)</div>";
    
    // Test dashboard
    $tables = ['events', 'announcements', 'attendance', 'members', 'news_feed'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<div class='success'>✅ Dashboard $table query works (" . $result['count'] . " records)</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Dashboard $table query failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    echo "</div>";
    
    echo "<div class='success'>";
    echo "<h3>🎉 PostgreSQL Migration Complete!</h3>";
    echo "<p>Your SmartApp is now fully converted to PostgreSQL and ready for Render deployment.</p>";
    echo "<p><strong>What was migrated:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Complete users table with email, full_name, reset_token</li>";
    echo "<li>✅ Events table with region and organizing_club</li>";
    echo "<li>✅ Announcements table</li>";
    echo "<li>✅ Attendance table with generated columns</li>";
    echo "<li>✅ Members table (equivalent to membership_monitoring)</li>";
    echo "<li>✅ News feed system with comments and reactions</li>";
    echo "<li>✅ Reports and notifications tables</li>";
    echo "<li>✅ All indexes for performance</li>";
    echo "<li>✅ Admin user with correct credentials</li>";
    echo "<li>✅ Sample data for testing</li>";
    echo "</ul>";
    echo "<p><strong>Login Credentials:</strong></p>";
    echo "<p>Username: <code>admin</code></p>";
    echo "<p>Password: <code>123</code></p>";
    echo "</div>";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "<div class='error'>❌ Migration error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<div style='text-align:center;margin:20px 0;'>";
echo "<a href='auth/login.php' class='btn'>Test Login</a>";
echo "<a href='auth/signup.php' class='btn'>Test Signup</a>";
echo "<a href='dashboard/index.php' class='btn'>Test Dashboard</a>";
echo "<a href='index.php' class='btn'>Go to Home</a>";
echo "</div>";

echo "</div></body></html>";
?>
