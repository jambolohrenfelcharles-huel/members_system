<?php
/**
 * Fix PostgreSQL member_id Type Error
 * This script fixes the member_id type mismatch in PostgreSQL
 */

function fixPostgreSQLMemberIdError($db, $db_type) {
    try {
        echo "🔧 Fixing PostgreSQL member_id type error...\n";
        
        if ($db_type !== 'postgresql') {
            echo "ℹ️ Not PostgreSQL, skipping fix\n";
            return;
        }
        
        // Check current attendance table structure
        $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'attendance' AND column_name = 'member_id'");
        $memberIdColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($memberIdColumn) {
            echo "📊 Current member_id column: " . $memberIdColumn['data_type'] . "\n";
            
            if ($memberIdColumn['data_type'] !== 'character varying') {
                echo "🔧 Converting member_id to VARCHAR(50)...\n";
                $db->exec("ALTER TABLE attendance ALTER COLUMN member_id TYPE VARCHAR(50)");
                echo "✅ member_id column converted to VARCHAR(50)\n";
            } else {
                echo "✅ member_id column is already VARCHAR\n";
            }
        } else {
            echo "❌ member_id column not found in attendance table\n";
        }
        
        // Check members table structure
        $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'members' AND column_name = 'member_id'");
        $membersMemberIdColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($membersMemberIdColumn) {
            echo "📊 Current members.member_id column: " . $membersMemberIdColumn['data_type'] . "\n";
            
            if ($membersMemberIdColumn['data_type'] !== 'character varying') {
                echo "🔧 Converting members.member_id to VARCHAR(50)...\n";
                $db->exec("ALTER TABLE members ALTER COLUMN member_id TYPE VARCHAR(50)");
                echo "✅ members.member_id column converted to VARCHAR(50)\n";
            } else {
                echo "✅ members.member_id column is already VARCHAR\n";
            }
        } else {
            echo "❌ member_id column not found in members table\n";
        }
        
        // Check for any constraints that might need updating
        $stmt = $db->query("SELECT constraint_name, constraint_type FROM information_schema.table_constraints WHERE table_name = 'attendance' AND constraint_type = 'FOREIGN KEY'");
        $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($foreignKeys as $fk) {
            echo "🔗 Found foreign key constraint: " . $fk['constraint_name'] . "\n";
        }
        
        // Test the problematic query
        echo "🧪 Testing member_id queries...\n";
        
        try {
            // Test with a sample member_id
            $testMemberId = 'M20250001';
            $testEventId = 1;
            
            $stmt = $db->prepare('SELECT id FROM attendance WHERE member_id = ? AND event_id = ?');
            $stmt->execute([$testMemberId, $testEventId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "✅ Test query executed successfully\n";
            
        } catch (Exception $e) {
            echo "❌ Test query failed: " . $e->getMessage() . "\n";
            
            // Try to fix the issue by recreating the table if necessary
            echo "🔧 Attempting to fix by updating column types...\n";
            
            try {
                // Ensure member_id is VARCHAR in attendance table
                $db->exec("ALTER TABLE attendance ALTER COLUMN member_id TYPE VARCHAR(50) USING member_id::VARCHAR(50)");
                echo "✅ Fixed attendance.member_id column\n";
                
                // Ensure member_id is VARCHAR in members table
                $db->exec("ALTER TABLE members ALTER COLUMN member_id TYPE VARCHAR(50) USING member_id::VARCHAR(50)");
                echo "✅ Fixed members.member_id column\n";
                
            } catch (Exception $fixError) {
                echo "❌ Failed to fix columns: " . $fixError->getMessage() . "\n";
            }
        }
        
        echo "✅ PostgreSQL member_id type fix completed\n";
        
    } catch (Exception $e) {
        echo "❌ Error fixing PostgreSQL member_id type: " . $e->getMessage() . "\n";
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
        echo "🔧 Fixing member_id type error for " . strtoupper($db_type) . "...\n";
        
        fixPostgreSQLMemberIdError($db, $db_type);
        
        echo "✅ Member ID type fix completed successfully\n";
        
    } catch (Exception $e) {
        echo "❌ Member ID type fix failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>
