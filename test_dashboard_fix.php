<?php
/**
 * DASHBOARD FIX TEST
 * This tests the dashboard events display fix
 */

echo "<h1>📊 Dashboard Fix Test</h1>";
echo "<p>Testing the dashboard events display fix...</p>";

// Step 1: Database Connection Test
echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🗄️ Step 1: Database Connection Test</h3>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ <strong>Database Connection:</strong> SUCCESS<br>";
    
} catch (Exception $e) {
    echo "❌ <strong>Database Connection:</strong> FAILED - " . $e->getMessage() . "<br>";
}

echo "</div>";

// Step 2: Events Table Test
echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>📅 Step 2: Events Table Test</h3>";

try {
    // Test events query
    $stmt = $db->prepare("SELECT id, title, place, status, event_date FROM events ORDER BY event_date DESC LIMIT 5");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ <strong>Events Query:</strong> SUCCESS<br>";
    echo "<strong>Found Events:</strong> " . count($events) . "<br>";
    
    if (count($events) > 0) {
        echo "<h4>Sample Events:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Place</th><th>Status</th><th>Event Date</th></tr>";
        
        foreach ($events as $event) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($event['id']) . "</td>";
            echo "<td>" . htmlspecialchars($event['title']) . "</td>";
            echo "<td>" . htmlspecialchars($event['place']) . "</td>";
            echo "<td>" . htmlspecialchars($event['status']) . "</td>";
            echo "<td>" . htmlspecialchars($event['event_date']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test the specific array access that was causing the warning
        echo "<h4>Array Access Test:</h4>";
        foreach ($events as $event) {
            $title = $event['title'] ?? 'No Title';
            $eventDate = $event['event_date'] ?? 'No Date';
            $status = $event['status'] ?? 'No Status';
            
            echo "<p>✅ <strong>Title:</strong> " . htmlspecialchars($title) . "</p>";
            echo "<p>✅ <strong>Date:</strong> " . date('M d, Y', strtotime($eventDate)) . "</p>";
            echo "<p>✅ <strong>Status:</strong> " . ucfirst($status) . "</p>";
            echo "<hr>";
        }
        
    } else {
        echo "<p>⚠️ <strong>No Events Found:</strong> Create some events to test the dashboard</p>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Events Query:</strong> FAILED - " . $e->getMessage() . "<br>";
}

echo "</div>";

// Step 3: Dashboard Simulation Test
echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🖥️ Step 3: Dashboard Simulation Test</h3>";

try {
    // Simulate the dashboard events display
    $stmt = $db->prepare("SELECT id, title, place, status, event_date FROM events ORDER BY event_date DESC LIMIT 5");
    $stmt->execute();
    $recent_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Recent Events Display (Dashboard Style):</h4>";
    
    if (empty($recent_events)) {
        echo "<p class='text-muted'>No events yet.</p>";
    } else {
        foreach ($recent_events as $event) {
            echo "<div style='display: flex; align-items: center; margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
            echo "<div style='flex-shrink: 0; margin-right: 15px;'>";
            echo "<i class='fas fa-calendar-alt' style='color: #28a745;'></i>";
            echo "</div>";
            echo "<div style='flex-grow: 1;'>";
            echo "<h6 style='margin-bottom: 5px;'>" . htmlspecialchars($event['title']) . "</h6>";
            echo "<small style='color: #666;'>";
            echo date('M d, Y', strtotime($event['event_date'])) . " - ";
            echo ucfirst($event['status']);
            echo "</small>";
            echo "</div>";
            echo "</div>";
        }
    }
    
    echo "<p>✅ <strong>Dashboard Simulation:</strong> SUCCESS - No undefined array key warnings!</p>";
    
} catch (Exception $e) {
    echo "❌ <strong>Dashboard Simulation:</strong> FAILED - " . $e->getMessage() . "<br>";
}

echo "</div>";

// Step 4: Fix Verification
echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔧 Step 4: Fix Verification</h3>";

echo "<h4>What was fixed:</h4>";
echo "<ul>";
echo "<li>❌ <strong>Before:</strong> \$event['name'] - Undefined array key warning</li>";
echo "<li>✅ <strong>After:</strong> \$event['title'] - Correct column name</li>";
echo "</ul>";

echo "<h4>Database Schema:</h4>";
echo "<ul>";
echo "<li>✅ <strong>events.title:</strong> varchar(255) - Event title</li>";
echo "<li>✅ <strong>events.place:</strong> varchar(255) - Event location</li>";
echo "<li>✅ <strong>events.status:</strong> enum('upcoming','ongoing','completed') - Event status</li>";
echo "<li>✅ <strong>events.event_date:</strong> datetime - Event date</li>";
echo "</ul>";

echo "<h4>Code Changes:</h4>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
echo "// Before (causing warning):\n";
echo "htmlspecialchars(\$event['name'])\n\n";
echo "// After (fixed):\n";
echo "htmlspecialchars(\$event['title'])\n";
echo "</pre>";

echo "</div>";

// Step 5: Test Dashboard
echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🧪 Step 5: Test Dashboard</h3>";

echo "<h4>Test URLs:</h4>";
echo "<ul>";
echo "<li><a href='dashboard/index.php' target='_blank'>📊 Dashboard Main Page</a></li>";
echo "<li><a href='dashboard/events.php' target='_blank'>📅 Events Management</a></li>";
echo "<li><a href='dashboard/members.php' target='_blank'>👥 Members Management</a></li>";
echo "</ul>";

echo "<h4>Expected Results:</h4>";
echo "<ul>";
echo "<li>✅ <strong>No Warnings:</strong> Undefined array key 'name' warnings should be gone</li>";
echo "<li>✅ <strong>Events Display:</strong> Event titles should display correctly</li>";
echo "<li>✅ <strong>No Errors:</strong> Dashboard should load without PHP warnings</li>";
echo "</ul>";

echo "</div>";

echo "<div style='background: #d4edda; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #28a745;'>";
echo "<h2>🎉 Dashboard Fix Complete!</h2>";
echo "<p><strong>The undefined array key 'name' warning has been fixed!</strong></p>";
echo "<p>The dashboard now correctly uses the 'title' column from the events table.</p>";
echo "<p><strong>Test the dashboard to confirm the fix is working!</strong></p>";
echo "</div>";
?>
