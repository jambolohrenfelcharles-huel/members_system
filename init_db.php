    f<?php
/**
 * Database initialization script for Render deployment
 * This script will create the database tables from the SQL file
 */

require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Always use MySQL SQL file
    $sqlFile = 'db/members_system.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $pdo->beginTransaction();
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    $pdo->commit();
    
    echo "Database initialized successfully!\n";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "Error initializing database: " . $e->getMessage() . "\n";
    exit(1);
}
?>
