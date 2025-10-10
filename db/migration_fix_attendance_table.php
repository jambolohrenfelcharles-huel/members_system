<?php
/**
 * Attendance Table Migration for Render
 * This script ensures the attendance table has the correct structure for QR scanning
 */

function fixAttendanceTable($db, $db_type) {
    try {
        echo "🔧 Checking attendance table structure...\n";
        
        // Check if attendance table exists
        if ($db_type === 'postgresql') {
            $stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'attendance')");
            $tableExists = $stmt->fetchColumn();
        } else {
            $stmt = $db->query("SHOW TABLES LIKE 'attendance'");
            $tableExists = $stmt->rowCount() > 0;
        }
        
        if (!$tableExists) {
            echo "📝 Creating attendance table...\n";
            
            if ($db_type === 'postgresql') {
                $createTable = "
                    CREATE TABLE IF NOT EXISTS attendance (
                        id SERIAL PRIMARY KEY,
                        member_id VARCHAR(50) NOT NULL,
                        full_name VARCHAR(100) NOT NULL,
                        club_position VARCHAR(50) NOT NULL,
                        event_id INTEGER REFERENCES events(id) ON DELETE CASCADE,
                        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        attendance_date DATE GENERATED ALWAYS AS (date::date) STORED,
                        status VARCHAR(20) DEFAULT 'present' CHECK (status IN ('present', 'absent', 'late')),
                        event_name VARCHAR(255),
                        semester INTEGER,
                        schoolyear VARCHAR(20),
                        dateadded TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ";
            } else {
                $createTable = "
                    CREATE TABLE IF NOT EXISTS attendance (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        member_id VARCHAR(50) NOT NULL,
                        full_name VARCHAR(100) NOT NULL,
                        club_position VARCHAR(50) NOT NULL,
                        event_id INT,
                        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        attendance_date DATE GENERATED ALWAYS AS (DATE(date)) STORED,
                        status ENUM('present', 'absent', 'late') DEFAULT 'present',
                        event_name VARCHAR(255),
                        semester INT,
                        schoolyear VARCHAR(20),
                        dateadded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
                    )
                ";
            }
            
            $db->exec($createTable);
            echo "✅ attendance table created\n";
            return;
        }
        
        // Check current columns
        if ($db_type === 'postgresql') {
            $stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'attendance'");
        } else {
            $stmt = $db->query("SHOW COLUMNS FROM attendance");
        }
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = [
            'member_id' => 'VARCHAR(50) NOT NULL',
            'full_name' => 'VARCHAR(100) NOT NULL', 
            'club_position' => 'VARCHAR(50) NOT NULL',
            'event_id' => 'INTEGER',
            'date' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'status' => 'VARCHAR(20) DEFAULT \'present\'',
            'event_name' => 'VARCHAR(255)',
            'semester' => 'INTEGER',
            'schoolyear' => 'VARCHAR(20)',
            'dateadded' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
        ];
        
        // Add missing columns
        foreach ($requiredColumns as $columnName => $columnDef) {
            if (!in_array($columnName, $columns)) {
                echo "📝 Adding $columnName column...\n";
                
                if ($db_type === 'postgresql') {
                    if ($columnName === 'status') {
                        $db->exec("ALTER TABLE attendance ADD COLUMN IF NOT EXISTS $columnName VARCHAR(20) DEFAULT 'present' CHECK ($columnName IN ('present', 'absent', 'late'))");
                    } else {
                        $db->exec("ALTER TABLE attendance ADD COLUMN IF NOT EXISTS $columnName $columnDef");
                    }
                } else {
                    if ($columnName === 'status') {
                        $db->exec("ALTER TABLE attendance ADD COLUMN $columnName ENUM('present', 'absent', 'late') DEFAULT 'present'");
                    } else {
                        $db->exec("ALTER TABLE attendance ADD COLUMN $columnName $columnDef");
                    }
                }
                
                echo "✅ $columnName column added\n";
            }
        }
        
        // Add foreign key constraint for event_id if it doesn't exist
        if ($db_type === 'postgresql') {
            try {
                $db->exec("ALTER TABLE attendance ADD CONSTRAINT fk_attendance_event_id FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE");
                echo "✅ Foreign key constraint added\n";
            } catch (Exception $e) {
                // Constraint might already exist
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "⚠️ Foreign key constraint warning: " . $e->getMessage() . "\n";
                }
            }
        }
        
        // Create indexes for better performance
        $indexes = [
            'idx_attendance_member_id' => 'CREATE INDEX IF NOT EXISTS idx_attendance_member_id ON attendance(member_id)',
            'idx_attendance_event_id' => 'CREATE INDEX IF NOT EXISTS idx_attendance_event_id ON attendance(event_id)',
            'idx_attendance_date' => 'CREATE INDEX IF NOT EXISTS idx_attendance_date ON attendance(date)',
            'idx_attendance_status' => 'CREATE INDEX IF NOT EXISTS idx_attendance_status ON attendance(status)'
        ];
        
        foreach ($indexes as $indexName => $indexSql) {
            try {
                $db->exec($indexSql);
                echo "✅ Index $indexName created\n";
            } catch (Exception $e) {
                // Index might already exist
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "⚠️ Index $indexName warning: " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "✅ attendance table structure verified\n";
        
    } catch (Exception $e) {
        echo "❌ Error fixing attendance table: " . $e->getMessage() . "\n";
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
        echo "🔧 Fixing attendance table for " . strtoupper($db_type) . "...\n";
        
        fixAttendanceTable($db, $db_type);
        
        echo "✅ Attendance table fix completed successfully\n";
        
    } catch (Exception $e) {
        echo "❌ Attendance table fix failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>
