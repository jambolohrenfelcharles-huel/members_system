<?php
/**
 * Complete Database Setup for Render Deployment
 * This script ensures all tables are created and admin user exists
 */

require_once 'config/database.php';

echo "<h1>SmartApp Database Setup for Render</h1>";
echo "<p>Setting up database for successful login...</p>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Check database type
    $db_type = $_ENV['DB_TYPE'] ?? 'mysql';
    echo "<p>Database type: <strong>$db_type</strong></p>";
    
    // Create tables if they don't exist
    echo "<h2>Creating Tables...</h2>";
    
    // Users table
    if ($db_type === 'postgresql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
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
                role VARCHAR(20) DEFAULT 'member',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    echo "<p>✅ Users table ready</p>";
    
    // Events table
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
    echo "<p>✅ Events table ready</p>";
    
    // Announcements table
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
    echo "<p>✅ Announcements table ready</p>";
    
    // Attendance table
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
    echo "<p>✅ Attendance table ready</p>";
    
    // Members table
    if ($db_type === 'postgresql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS members (
                id SERIAL PRIMARY KEY,
                member_id VARCHAR(50) UNIQUE NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(255),
                phone VARCHAR(20),
                club_position VARCHAR(50) NOT NULL,
                status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS members (
                id INT AUTO_INCREMENT PRIMARY KEY,
                member_id VARCHAR(50) UNIQUE NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(255),
                phone VARCHAR(20),
                club_position VARCHAR(50) NOT NULL,
                status VARCHAR(20) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    echo "<p>✅ Members table ready</p>";
    
    // News feed table
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
    echo "<p>✅ News feed table ready</p>";
    
    // Comments table
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
    echo "<p>✅ Comments table ready</p>";
    
    // Create admin user
    echo "<h2>Setting up Admin User...</h2>";
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        // Create admin user
        $hashed_password = hash('sha256', '123');
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')");
        $stmt->execute([$hashed_password]);
        echo "<p style='color: green;'>✅ Admin user created successfully</p>";
    } else {
        // Update admin password to ensure it's correct
        $hashed_password = hash('sha256', '123');
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
        $stmt->execute([$hashed_password]);
        echo "<p style='color: green;'>✅ Admin user password updated</p>";
    }
    
    // Test login
    echo "<h2>Testing Login...</h2>";
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && hash('sha256', '123') === $user['password']) {
        echo "<p style='color: green;'>✅ Login test successful!</p>";
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>Login Credentials:</h3>";
        echo "<p><strong>Username:</strong> admin</p>";
        echo "<p><strong>Password:</strong> 123</p>";
        echo "<p><strong>Role:</strong> " . $user['role'] . "</p>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ Login test failed</p>";
    }
    
    // Add some sample data
    echo "<h2>Adding Sample Data...</h2>";
    
    // Add sample announcement
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM announcements");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content) VALUES (?, ?)");
        $stmt->execute(['Welcome to SmartApp', 'Welcome to your new club management system!']);
        echo "<p>✅ Sample announcement added</p>";
    }
    
    // Add sample event
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM events");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        $future_date = date('Y-m-d H:i:s', strtotime('+1 month'));
        $stmt = $pdo->prepare("INSERT INTO events (name, place, event_date, description) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Club Meeting', 'Main Hall', $future_date, 'Monthly club meeting']);
        echo "<p>✅ Sample event added</p>";
    }
    
    echo "<h2>Setup Complete!</h2>";
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; border: 1px solid #c3e6cb;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>🎉 Database Setup Successful!</h3>";
    echo "<p>Your SmartApp is now ready for login on Render.</p>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Visit the <a href='auth/login.php'>Login Page</a></li>";
    echo "<li>Use username: <code>admin</code> and password: <code>123</code></li>";
    echo "<li>Access the <a href='dashboard/index.php'>Dashboard</a> after login</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
}

echo "<hr>";
echo "<p><a href='auth/login.php'>Go to Login Page</a> | ";
echo "<a href='dashboard/index.php'>Go to Dashboard</a> | ";
echo "<a href='test_login_render.php'>Run Login Test</a></p>";
?>
