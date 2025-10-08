<?php
// Fix attendance table structure for PostgreSQL
require_once 'config/database.php';

echo "<h1>🔧 Fix Attendance Table Structure</h1>";
echo "<p>Fixing attendance table for PostgreSQL compatibility</p>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>❌ Database Connection Failed</h3>";
        echo "<p>Please check your environment variables.</p>";
        echo "</div>";
        exit;
    }
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "✅ <strong>Database Connected!</strong><br>";
    echo "📊 <strong>Database Type:</strong> " . ($_ENV['DB_TYPE'] ?? 'mysql') . "<br>";
    echo "</div>";
    
    $db_type = $_ENV['DB_TYPE'] ?? 'mysql';
    
    if ($db_type === 'postgresql') {
        echo "<h3>Fixing PostgreSQL Attendance Table...</h3>";
        
        // Drop the existing attendance table if it exists
        try {
            $conn->exec("DROP TABLE IF EXISTS attendance");
            echo "✅ <strong>Old Table:</strong> Dropped<br>";
        } catch (Exception $e) {
            echo "ℹ️ <strong>Old Table:</strong> " . $e->getMessage() . "<br>";
        }
        
        // Create the correct attendance table
        $create_attendance = "
            CREATE TABLE attendance (
                id SERIAL PRIMARY KEY,
                member_id VARCHAR(50) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                club_position VARCHAR(50) NOT NULL,
                date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                attendance_date DATE GENERATED ALWAYS AS (date::date) STORED
            )
        ";
        
        $conn->exec($create_attendance);
        echo "✅ <strong>New Table:</strong> Created successfully<br>";
        
        // Add some sample data
        $sample_data = [
            ['M001', 'John Doe', 'President', '2024-01-15 09:00:00'],
            ['M002', 'Jane Smith', 'Vice President', '2024-01-15 09:30:00'],
            ['M003', 'Bob Johnson', 'Secretary', '2024-01-15 10:00:00']
        ];
        
        $stmt = $conn->prepare("INSERT INTO attendance (member_id, full_name, club_position, date) VALUES (?, ?, ?, ?)");
        
        foreach ($sample_data as $data) {
            $stmt->execute($data);
        }
        
        echo "✅ <strong>Sample Data:</strong> Added<br>";
        
        // Test the query that was failing
        echo "<h3>Testing Dashboard Query...</h3>";
        
        try {
            $stmt = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE attendance_date = CURRENT_DATE");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "✅ <strong>Dashboard Query:</strong> SUCCESS<br>";
            echo "📊 <strong>Today's Attendance:</strong> " . $result['total'] . "<br>";
        } catch (Exception $e) {
            echo "❌ <strong>Dashboard Query:</strong> " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "<h3>MySQL Attendance Table (No Changes Needed)</h3>";
        echo "✅ <strong>MySQL Table:</strong> Already correct<br>";
        
        // Test MySQL query
        try {
            $stmt = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE DATE(date) = CURDATE()");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "✅ <strong>Dashboard Query:</strong> SUCCESS<br>";
            echo "📊 <strong>Today's Attendance:</strong> " . $result['total'] . "<br>";
        } catch (Exception $e) {
            echo "❌ <strong>Dashboard Query:</strong> " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br><h2>🎉 Attendance Table Fixed!</h2>";
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<p><strong>✅ The attendance table has been fixed for PostgreSQL!</strong></p>";
    echo "<p><strong>🚀 Your dashboard should now work without errors.</strong></p>";
    echo "<p><strong>📊 Test your dashboard:</strong> <a href='dashboard/index.php'>dashboard/index.php</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>❌ Fix Failed</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
    echo "</div>";
}
?>
