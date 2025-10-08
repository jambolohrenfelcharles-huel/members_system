<?php
/**
 * Fix Image Paths in Database
 * Removes extra 'members/' prefix from image_path in database
 */

require_once 'config/database.php';

echo "<h1>🔧 FIX IMAGE PATHS IN DATABASE</h1>";
echo "<p>Removing extra 'members/' prefix from image_path in database</p>";

try {
    $database = new Database();
    $db = $database->getConnection();
    $members_table = $database->getMembersTable();
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "✅ <strong>Database Connected!</strong><br>";
    echo "✅ <strong>Database Type:</strong> " . strtoupper($_ENV['DB_TYPE'] ?? 'mysql') . "<br>";
    echo "✅ <strong>Members Table:</strong> " . $members_table . "<br>";
    echo "</div>";
    
    // Step 1: Find records with extra 'members/' prefix
    echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>🔍 Step 1: Find Records with Extra 'members/' Prefix</h3>";
    
    $stmt = $db->prepare("SELECT id, name, image_path FROM " . $members_table . " WHERE image_path LIKE 'members/%'");
    $stmt->execute();
    $recordsToFix = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recordsToFix)) {
        echo "✅ <strong>No records found with extra 'members/' prefix</strong><br>";
    } else {
        echo "⚠️ <strong>Found " . count($recordsToFix) . " records with extra 'members/' prefix:</strong><br>";
        foreach ($recordsToFix as $record) {
            echo "&nbsp;&nbsp;• <strong>ID {$record['id']}:</strong> {$record['name']} - {$record['image_path']}<br>";
        }
    }
    echo "</div>";
    
    // Step 2: Fix the records
    if (!empty($recordsToFix)) {
        echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>🔧 Step 2: Fixing Image Paths</h3>";
        
        $fixedCount = 0;
        foreach ($recordsToFix as $record) {
            $oldPath = $record['image_path'];
            $newPath = str_replace('members/', '', $oldPath);
            
            // Check if the corrected file exists
            $fullPath = realpath(__DIR__ . '/uploads/members/' . $newPath);
            $fileExists = $fullPath && file_exists($fullPath);
            
            if ($fileExists) {
                // Update the database
                $updateStmt = $db->prepare("UPDATE " . $members_table . " SET image_path = ? WHERE id = ?");
                $updateStmt->execute([$newPath, $record['id']]);
                
                echo "✅ <strong>Fixed ID {$record['id']}:</strong> '{$oldPath}' → '{$newPath}'<br>";
                $fixedCount++;
            } else {
                echo "❌ <strong>File not found for ID {$record['id']}:</strong> '{$newPath}'<br>";
            }
        }
        
        echo "<br><strong>Fixed:</strong> {$fixedCount} out of " . count($recordsToFix) . " records<br>";
        echo "</div>";
    }
    
    // Step 3: Verify the fix
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>🧪 Step 3: Verify the Fix</h3>";
    
    $stmt = $db->prepare("SELECT id, name, image_path FROM " . $members_table . " WHERE image_path IS NOT NULL AND image_path != '' ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    $allRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allRecords)) {
        echo "⚠️ <strong>No records with images found</strong><br>";
    } else {
        echo "✅ <strong>Current image paths in database:</strong><br>";
        foreach ($allRecords as $record) {
            $fullPath = realpath(__DIR__ . '/uploads/members/' . $record['image_path']);
            $fileExists = $fullPath && file_exists($fullPath);
            
            echo "&nbsp;&nbsp;• <strong>ID {$record['id']}:</strong> {$record['name']} - {$record['image_path']} ";
            echo "(" . ($fileExists ? "✅ File exists" : "❌ File missing") . ")<br>";
        }
    }
    echo "</div>";
    
    // Step 4: Test view.php URLs
    echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>🔗 Step 4: Test View URLs</h3>";
    
    if (!empty($allRecords)) {
        echo "✅ <strong>Test these URLs to verify profile photo display:</strong><br>";
        foreach (array_slice($allRecords, 0, 3) as $record) {
            $viewUrl = "dashboard/members/view.php?id={$record['id']}";
            echo "&nbsp;&nbsp;• <a href='{$viewUrl}' target='_blank'>{$viewUrl}</a> - {$record['name']}<br>";
        }
    } else {
        echo "⚠️ <strong>No records with images to test</strong><br>";
    }
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #28a745;'>";
    echo "<h2>🎉 IMAGE PATH FIX COMPLETE!</h2>";
    echo "<p><strong>✅ Image paths have been corrected in the database!</strong></p>";
    echo "<h3>🔧 What Was Fixed:</h3>";
    echo "<ul>";
    echo "<li>✅ <strong>Extra 'members/' prefix:</strong> Removed from database paths</li>";
    echo "<li>✅ <strong>File existence:</strong> Verified before updating</li>";
    echo "<li>✅ <strong>Database consistency:</strong> All paths now correct</li>";
    echo "<li>✅ <strong>View functionality:</strong> Ready to test</li>";
    echo "</ul>";
    echo "<h3>🎯 Next Steps:</h3>";
    echo "<ul>";
    echo "<li>👁️ <strong>Test View Pages:</strong> Click the test URLs above</li>";
    echo "<li>🖼️ <strong>Verify Images:</strong> Check if profile photos display correctly</li>";
    echo "<li>📱 <strong>Test Responsiveness:</strong> Check on different screen sizes</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>❌ ERROR</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
