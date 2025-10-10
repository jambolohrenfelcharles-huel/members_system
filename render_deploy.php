<?php
/**
 * Render Deployment Script for SmartApp
 * This script initializes the database and runs necessary migrations
 */

require_once 'config/database.php';

echo "<h1>SmartApp Render Deployment</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    echo "<h2>✅ Database Connection Successful</h2>";
    echo "<p>Connected to: " . ($_ENV['DB_TYPE'] ?? 'mysql') . " database</p>";
    
    // Check if we're using PostgreSQL
    $isPostgreSQL = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql';
    
    if ($isPostgreSQL) {
        echo "<h3>🐘 PostgreSQL Setup</h3>";
        
        // Read and execute PostgreSQL schema
        $schemaFile = 'db/members_system_postgresql.sql';
        if (file_exists($schemaFile)) {
            $schema = file_get_contents($schemaFile);
            
            // Split by semicolon and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $schema)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    try {
                        $db->exec($statement);
                    } catch (PDOException $e) {
                        // Ignore "already exists" errors
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            echo "<p style='color: orange;'>⚠️ Statement warning: " . substr($statement, 0, 50) . "... - " . $e->getMessage() . "</p>";
                        }
                    }
                }
            }
            
            echo "<p style='color: green;'>✅ PostgreSQL schema executed successfully</p>";
        }
        
        // Ensure events table exists with all columns
        echo "<h3>📅 Ensuring Events Table Exists</h3>";
        
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM events");
            echo "<p style='color: green;'>✅ Events table exists</p>";
            
            // Check table structure and fix any issues
            $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'events' ORDER BY ordinal_position");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<p><strong>Current events table columns:</strong> " . implode(', ', $columns) . "</p>";
            
            // Check if we have both name and title columns (schema mismatch)
            $hasName = in_array('name', $columns);
            $hasTitle = in_array('title', $columns);
            
            if ($hasName && !$hasTitle) {
                echo "<p style='color: orange;'>🔧 Renaming name column to title...</p>";
                $db->exec("ALTER TABLE events RENAME COLUMN name TO title");
                echo "<p style='color: green;'>✅ name column renamed to title successfully</p>";
            } elseif (!$hasName && !$hasTitle) {
                echo "<p style='color: orange;'>🔧 Adding title column to events table...</p>";
                $db->exec("ALTER TABLE events ADD COLUMN title VARCHAR(255) NOT NULL DEFAULT ''");
                echo "<p style='color: green;'>✅ title column added successfully</p>";
            } else {
                echo "<p style='color: green;'>✅ title column already exists</p>";
            }
            
            // Check if place column exists
            if (!in_array('place', $columns)) {
                echo "<p style='color: orange;'>🔧 Adding place column to events table...</p>";
                $db->exec("ALTER TABLE events ADD COLUMN place VARCHAR(255) NOT NULL DEFAULT ''");
                echo "<p style='color: green;'>✅ place column added successfully</p>";
            } else {
                echo "<p style='color: green;'>✅ place column already exists</p>";
            }
            
            // Check if event_date column exists
            if (!in_array('event_date', $columns)) {
                echo "<p style='color: orange;'>🔧 Adding event_date column to events table...</p>";
                $db->exec("ALTER TABLE events ADD COLUMN event_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
                echo "<p style='color: green;'>✅ event_date column added successfully</p>";
            } else {
                echo "<p style='color: green;'>✅ event_date column already exists</p>";
            }
            
            // Check if status column exists
            if (!in_array('status', $columns)) {
                echo "<p style='color: orange;'>🔧 Adding status column to events table...</p>";
                $db->exec("ALTER TABLE events ADD COLUMN status VARCHAR(20) DEFAULT 'upcoming' CHECK (status IN ('upcoming', 'ongoing', 'completed'))");
                echo "<p style='color: green;'>✅ status column added successfully</p>";
            } else {
                echo "<p style='color: green;'>✅ status column already exists</p>";
            }
            
            // Check if description column exists
            if (!in_array('description', $columns)) {
                echo "<p style='color: orange;'>🔧 Adding description column to events table...</p>";
                $db->exec("ALTER TABLE events ADD COLUMN description TEXT");
                echo "<p style='color: green;'>✅ description column added successfully</p>";
            } else {
                echo "<p style='color: green;'>✅ description column already exists</p>";
            }
            
            // Check if region column exists
            if (!in_array('region', $columns)) {
                echo "<p style='color: orange;'>🔧 Adding region column to events table...</p>";
                $db->exec("ALTER TABLE events ADD COLUMN region VARCHAR(100)");
                echo "<p style='color: green;'>✅ region column added successfully</p>";
            } else {
                echo "<p style='color: green;'>✅ region column already exists</p>";
            }
            
            // Check if organizing_club column exists
            if (!in_array('organizing_club', $columns)) {
                echo "<p style='color: orange;'>🔧 Adding organizing_club column to events table...</p>";
                $db->exec("ALTER TABLE events ADD COLUMN organizing_club VARCHAR(255)");
                echo "<p style='color: green;'>✅ organizing_club column added successfully</p>";
            } else {
                echo "<p style='color: green;'>✅ organizing_club column already exists</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: orange;'>🔧 Creating events table...</p>";
            $createEventsSQL = "
                CREATE TABLE IF NOT EXISTS events (
                    id SERIAL PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    place VARCHAR(255) NOT NULL,
                    status VARCHAR(20) DEFAULT 'upcoming' CHECK (status IN ('upcoming', 'ongoing', 'completed')),
                    event_date TIMESTAMP NOT NULL,
                    description TEXT,
                    region VARCHAR(100),
                    organizing_club VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ";
            $db->exec($createEventsSQL);
            echo "<p style='color: green;'>✅ Events table created successfully</p>";
        }
        
        // Ensure announcements table exists
        echo "<h3>📋 Ensuring Announcements Table Exists</h3>";
        
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM announcements");
            echo "<p style='color: green;'>✅ Announcements table exists</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>🔧 Creating announcements table...</p>";
            $createAnnouncementsSQL = "
                CREATE TABLE IF NOT EXISTS announcements (
                    id SERIAL PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ";
            $db->exec($createAnnouncementsSQL);
            echo "<p style='color: green;'>✅ Announcements table created successfully</p>";
        }
        
        // Ensure email queue tables exist
        echo "<h3>📧 Ensuring Email Queue Tables Exist</h3>";
        
        $emailQueueTables = [
            'email_queue' => "
                CREATE TABLE IF NOT EXISTS email_queue (
                    id SERIAL PRIMARY KEY,
                    type VARCHAR(50) NOT NULL,
                    subject VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    processed_at TIMESTAMP DEFAULT NULL
                )
            ",
            'email_queue_items' => "
                CREATE TABLE IF NOT EXISTS email_queue_items (
                    id SERIAL PRIMARY KEY,
                    queue_id INTEGER REFERENCES email_queue(id) ON DELETE CASCADE,
                    member_id VARCHAR(50),
                    member_name VARCHAR(255),
                    member_email VARCHAR(255) NOT NULL,
                    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'sent', 'failed')),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    processed_at TIMESTAMP DEFAULT NULL,
                    error_message TEXT DEFAULT NULL
                )
            "
        ];
        
        foreach ($emailQueueTables as $tableName => $createSQL) {
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM $tableName");
                echo "<p style='color: green;'>✅ $tableName table exists</p>";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>🔧 Creating $tableName table...</p>";
                $db->exec($createSQL);
                echo "<p style='color: green;'>✅ $tableName table created successfully</p>";
            }
        }
        
        // Create indexes for email queue performance
        echo "<h3>🔧 Creating Email Queue Indexes</h3>";
        
        $indexes = [
            'idx_email_queue_status' => 'CREATE INDEX IF NOT EXISTS idx_email_queue_status ON email_queue(status)',
            'idx_email_queue_items_status' => 'CREATE INDEX IF NOT EXISTS idx_email_queue_items_status ON email_queue_items(status)',
            'idx_email_queue_items_queue_id' => 'CREATE INDEX IF NOT EXISTS idx_email_queue_items_queue_id ON email_queue_items(queue_id)'
        ];
        
        foreach ($indexes as $indexName => $indexSQL) {
            try {
                $db->exec($indexSQL);
                echo "<p style='color: green;'>✅ $indexName index created</p>";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ $indexName index warning: " . $e->getMessage() . "</p>";
            }
        }
        
        // Run attendance migration
        echo "<h3>🔧 Running Attendance Migration</h3>";
        
        // Check if event_id column exists
        $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'attendance' AND column_name = 'event_id'");
        $stmt->execute();
        $columnExists = $stmt->fetch();
        
        if ($columnExists) {
            echo "<p style='color: green;'>✅ event_id column already exists in attendance table</p>";
        } else {
            echo "<p style='color: orange;'>🔧 Adding event_id column to attendance table...</p>";
            
            // Add event_id column
            $db->exec("ALTER TABLE attendance ADD COLUMN event_id INTEGER REFERENCES events(id) ON DELETE CASCADE");
            echo "<p style='color: green;'>✅ event_id column added successfully</p>";
            
            // Create index
            $db->exec("CREATE INDEX IF NOT EXISTS idx_attendance_event_id ON attendance(event_id)");
            echo "<p style='color: green;'>✅ Index created successfully</p>";
        }
        
        // Check and add missing columns
        $requiredColumns = ['full_name', 'club_position', 'status', 'event_name', 'semester', 'schoolyear', 'dateadded'];
        foreach ($requiredColumns as $column) {
            $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'attendance' AND column_name = ?");
            $stmt->execute([$column]);
            $columnExists = $stmt->fetch();
            
            if (!$columnExists) {
                echo "<p style='color: orange;'>🔧 Adding $column column to attendance table...</p>";
                
                switch ($column) {
                    case 'full_name':
                        $db->exec("ALTER TABLE attendance ADD COLUMN full_name VARCHAR(100) NOT NULL DEFAULT ''");
                        break;
                    case 'club_position':
                        $db->exec("ALTER TABLE attendance ADD COLUMN club_position VARCHAR(50) NOT NULL DEFAULT ''");
                        break;
                    case 'status':
                        $db->exec("ALTER TABLE attendance ADD COLUMN status VARCHAR(20) DEFAULT 'present' CHECK (status IN ('present', 'absent', 'late'))");
                        break;
                    case 'event_name':
                        $db->exec("ALTER TABLE attendance ADD COLUMN event_name VARCHAR(255)");
                        break;
                    case 'semester':
                        $db->exec("ALTER TABLE attendance ADD COLUMN semester INTEGER");
                        break;
                    case 'schoolyear':
                        $db->exec("ALTER TABLE attendance ADD COLUMN schoolyear VARCHAR(20)");
                        break;
                    case 'dateadded':
                        $db->exec("ALTER TABLE attendance ADD COLUMN dateadded TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                        break;
                }
                
                echo "<p style='color: green;'>✅ $column column added successfully</p>";
            } else {
                echo "<p style='color: green;'>✅ $column column already exists</p>";
            }
        }
        
        // Add user blocking functionality
        echo "<h3>🔧 Adding User Blocking Functionality</h3>";
        
        $userBlockingColumns = ['blocked', 'blocked_reason', 'blocked_at'];
        foreach ($userBlockingColumns as $column) {
            $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'users' AND column_name = ?");
            $stmt->execute([$column]);
            $columnExists = $stmt->fetch();
            
            if (!$columnExists) {
                echo "<p style='color: orange;'>🔧 Adding $column column to users table...</p>";
                
                switch ($column) {
                    case 'blocked':
                        $db->exec("ALTER TABLE users ADD COLUMN blocked BOOLEAN DEFAULT FALSE");
                        break;
                    case 'blocked_reason':
                        $db->exec("ALTER TABLE users ADD COLUMN blocked_reason TEXT DEFAULT NULL");
                        break;
                    case 'blocked_at':
                        $db->exec("ALTER TABLE users ADD COLUMN blocked_at TIMESTAMP DEFAULT NULL");
                        break;
                }
                
                echo "<p style='color: green;'>✅ $column column added successfully</p>";
            } else {
                echo "<p style='color: green;'>✅ $column column already exists</p>";
            }
        }
        
        // Create index for blocked users
        try {
            $db->exec("CREATE INDEX IF NOT EXISTS idx_users_blocked ON users(blocked)");
            echo "<p style='color: green;'>✅ Index for blocked users created</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Index creation skipped: " . $e->getMessage() . "</p>";
        }
        
        // Update existing users to have blocked = false
        try {
            $db->exec("UPDATE users SET blocked = FALSE WHERE blocked IS NULL");
            echo "<p style='color: green;'>✅ Existing users updated with default blocked status</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ User update skipped: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<h3>🐬 MySQL Setup</h3>";
        
        // Read and execute MySQL schema
        $schemaFile = 'db/members_system.sql';
        if (file_exists($schemaFile)) {
            $schema = file_get_contents($schemaFile);
            
            // Split by semicolon and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $schema)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    try {
                        $db->exec($statement);
                    } catch (PDOException $e) {
                        // Ignore "already exists" errors
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            echo "<p style='color: orange;'>⚠️ Statement warning: " . substr($statement, 0, 50) . "... - " . $e->getMessage() . "</p>";
                        }
                    }
                }
            }
            
            echo "<p style='color: green;'>✅ MySQL schema executed successfully</p>";
        }
    }
    
    // Test attendance functionality
    echo "<h3>🧪 Testing Attendance Functionality</h3>";
    
    try {
        if ($isPostgreSQL) {
            $stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE attendance_date = CURRENT_DATE");
        } else {
            $stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE attendance_date = CURRENT_DATE");
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Attendance query works: " . $result['total'] . " records for today</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Attendance query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test events functionality
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM events");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Events table accessible: " . $result['total'] . " events</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Events query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test users functionality
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Users table accessible: " . $result['total'] . " users</p>";
        
        // Check if admin user exists
        $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE username = 'admin'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['total'] > 0) {
            echo "<p style='color: green;'>✅ Admin user exists</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Admin user not found</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Users query failed: " . $e->getMessage() . "</p>";
    }
    
    // Run news_feed table migration
    echo "<h3>🔧 Running News Feed Migration</h3>";
    
    try {
        // Include the news_feed migration function
        require_once 'db/migration_fix_news_feed_table.php';
        
        // Execute the migration
        fixNewsFeedTable($db, $isPostgreSQL ? 'postgresql' : 'mysql');
        
        echo "<p style='color: green;'>✅ News feed table migration completed</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ News feed migration failed: " . $e->getMessage() . "</p>";
    }
    
    // Run attendance table migration
    echo "<h3>🔧 Running Attendance Migration</h3>";
    
    try {
        // Include the attendance migration function
        require_once 'db/migration_fix_attendance_table.php';
        
        // Execute the migration
        fixAttendanceTable($db, $isPostgreSQL ? 'postgresql' : 'mysql');
        
        echo "<p style='color: green;'>✅ Attendance table migration completed</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Attendance migration failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>🎉 Deployment Complete!</h3>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Your SmartApp is ready!</strong></p>";
    echo "<p>Default Admin Login:</p>";
    echo "<ul>";
    echo "<li>Username: <code>admin</code></li>";
    echo "<li>Password: <code>123</code></li>";
    echo "</ul>";
    echo "<p style='color: red;'><strong>Important:</strong> Change the admin password after first login!</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Deployment Failed</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
}

echo "</div>";
?>
