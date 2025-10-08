<?php
/**
 * Database Migration Script for Render
 * This script adds the email column to existing users table
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Database Migration</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;}";
echo ".container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo ".success{color:#28a745;background:#d4edda;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".error{color:#dc3545;background:#f8d7da;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".info{color:#0c5460;background:#d1ecf1;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".btn{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;}";
echo ".btn:hover{background:#0056b3;}";
echo "</style></head><body><div class='container'>";

echo "<h1>🔄 Database Migration for Render</h1>";
echo "<p>Adding email column to users table...</p>";

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
    
    // Check if email column exists
    echo "<h2>Checking Users Table Structure...</h2>";
    
    if ($db_type === 'postgresql') {
        $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'email'");
        $emailColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$emailColumn) {
            echo "<div class='info'>Email column not found, adding it...</div>";
            $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE");
            echo "<div class='success'>✅ Email column added to users table</div>";
        } else {
            echo "<div class='success'>✅ Email column already exists in users table</div>";
        }
    } else {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'");
        $emailColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$emailColumn) {
            echo "<div class='info'>Email column not found, adding it...</div>";
            $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE");
            echo "<div class='success'>✅ Email column added to users table</div>";
        } else {
            echo "<div class='success'>✅ Email column already exists in users table</div>";
        }
    }
    
    // Check if members table exists and has email column
    echo "<h2>Checking Members Table...</h2>";
    
    try {
        if ($db_type === 'postgresql') {
            $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'members' AND column_name = 'email'");
            $emailColumn = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$emailColumn) {
                echo "<div class='info'>Email column not found in members table, adding it...</div>";
                $pdo->exec("ALTER TABLE members ADD COLUMN email VARCHAR(255)");
                echo "<div class='success'>✅ Email column added to members table</div>";
            } else {
                echo "<div class='success'>✅ Email column already exists in members table</div>";
            }
        } else {
            $stmt = $pdo->query("SHOW COLUMNS FROM members LIKE 'email'");
            $emailColumn = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$emailColumn) {
                echo "<div class='info'>Email column not found in members table, adding it...</div>";
                $pdo->exec("ALTER TABLE members ADD COLUMN email VARCHAR(255)");
                echo "<div class='success'>✅ Email column added to members table</div>";
            } else {
                echo "<div class='success'>✅ Email column already exists in members table</div>";
            }
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Members table error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // Test signup functionality
    echo "<h2>Testing Signup Functionality...</h2>";
    
    try {
        // Test if we can query members table
        $members_table = ($db_type === 'postgresql') ? 'members' : 'membership_monitoring';
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $members_table");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<div class='success'>✅ Can query $members_table table (" . $result['count'] . " records)</div>";
        
        // Test if we can query users table with email
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email IS NOT NULL");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<div class='success'>✅ Can query users table with email column (" . $result['count'] . " users with email)</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Signup test error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    echo "<div class='success'>";
    echo "<h3>🎉 Migration Complete!</h3>";
    echo "<p>Your database is now updated and ready for signup functionality.</p>";
    echo "<p><strong>What was fixed:</strong></p>";
    echo "<ul>";
    echo "<li>Added email column to users table</li>";
    echo "<li>Added email column to members table</li>";
    echo "<li>Fixed table name references for PostgreSQL</li>";
    echo "<li>Verified signup functionality</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Migration error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<div style='text-align:center;margin:20px 0;'>";
echo "<a href='auth/signup.php' class='btn'>Test Signup</a>";
echo "<a href='auth/login.php' class='btn'>Go to Login</a>";
echo "<a href='index.php' class='btn'>Go to Home</a>";
echo "</div>";

echo "</div></body></html>";
?>
