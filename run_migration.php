<?php
// Migration script to add status and renewal_date fields to $members_table table
// Run this script once to update your existing database

require_once 'config/database.php';

// Dynamic table name for PostgreSQL compatibility
$members_table = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql' ? 'members' : '$members_table';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Starting migration...\n";
    
    // Check if status column already exists
    $checkStatus = $db->query("information_schema.columns WHERE table_name = $members_table LIKE 'status'");
    if ($checkStatus->rowCount() == 0) {
        // Add status field with default 'active'
        $db->query("ALTER TABLE $members_table ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER image_path");
        echo "✓ Added status column\n";
    } else {
        echo "✓ Status column already exists\n";
    }
    
    // Check if renewal_date column already exists
    $checkRenewal = $db->query("information_schema.columns WHERE table_name = $members_table LIKE 'renewal_date'");
    if ($checkRenewal->rowCount() == 0) {
        // Add renewal_date field
        $db->query("ALTER TABLE $members_table ADD COLUMN renewal_date DATE AFTER status");
        echo "✓ Added renewal_date column\n";
    } else {
        echo "✓ Renewal_date column already exists\n";
    }
    
    // Update existing members to set renewal_date to 1 year from created_at
    $db->query("UPDATE $members_table SET renewal_date = DATE_ADD(created_at, INTERVAL 1 YEAR) WHERE renewal_date IS NULL");
    echo "✓ Updated existing members with renewal dates\n";
    
    // Update members who are past their renewal date to inactive status
    $result = $db->query("UPDATE $members_table SET status = 'inactive' WHERE renewal_date < CURRENT_DATE AND status = 'active'");
    $affectedRows = $result->rowCount();
    echo "✓ Updated $affectedRows members to inactive status (past renewal date)\n";
    
    echo "\nMigration completed successfully!\n";
    echo "You can now use the member status functionality in the dashboard.\n";
    
} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
}
?>
