<?php
// Migration script to add region and organizing_club fields to events table
// Run this script once to update your existing database

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Starting events migration...\n\n";
    
    // Check if region column already exists
    $checkRegion = $db->query("SHOW COLUMNS FROM events LIKE 'region'");
    if ($checkRegion->rowCount() == 0) {
        $db->query("ALTER TABLE events ADD COLUMN region VARCHAR(100) AFTER description");
        echo "✓ Added region column\n";
    } else {
        echo "✓ Region column already exists\n";
    }
    
    // Check if organizing_club column already exists
    $checkClub = $db->query("SHOW COLUMNS FROM events LIKE 'organizing_club'");
    if ($checkClub->rowCount() == 0) {
        $db->query("ALTER TABLE events ADD COLUMN organizing_club VARCHAR(255) AFTER region");
        echo "✓ Added organizing_club column\n";
    } else {
        echo "✓ Organizing_club column already exists\n";
    }
    
    echo "\nEvents migration completed successfully!\n";
    echo "You can now add region and organizing club information when creating events.\n";
    
} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    echo "Please check your database connection and try again.\n";
}
?>
