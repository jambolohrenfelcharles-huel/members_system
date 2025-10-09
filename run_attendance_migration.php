<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Running Attendance Migration</h2>";
    
    // Check if event_id column already exists
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
    
    // Test the attendance query that was failing
    echo "<h3>Testing Attendance Query</h3>";
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE attendance_date = CURRENT_DATE");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Attendance query works: " . $result['total'] . " records for today</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Attendance query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test event-based attendance query
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE event_id IS NOT NULL");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Event-based attendance query works: " . $result['total'] . " records with event_id</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Event-based attendance query failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Migration Complete!</h3>";
    echo "<p>You can now access the attendance page without errors.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</p>";
}
?>
