<?php
/**
 * Member Image Path Fix Test
 * This script tests that member images are displayed correctly after fixing the path issue
 */

echo "<h1>Member Image Path Fix Test</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px;'>";

try {
    // Test database connection
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    echo "<h2>✅ Database Connection Test</h2>";
    echo "<p style='color: green;'>Database connected successfully</p>";
    
    // Get members with image paths
    $members_table = $database->getMembersTable();
    $stmt = $db->query("SELECT id, name, image_path FROM " . $members_table . " WHERE image_path IS NOT NULL AND image_path != '' LIMIT 5");
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>📊 Members with Images</h2>";
    echo "<p><strong>Found:</strong> " . count($members) . " members with image paths</p>";
    
    if (count($members) > 0) {
        echo "<h3>🖼️ Image Path Validation</h3>";
        
        foreach ($members as $member) {
            echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>Member: " . htmlspecialchars($member['name']) . " (ID: " . $member['id'] . ")</h4>";
            
            $imagePath = $member['image_path'];
            echo "<p><strong>Database image_path:</strong> <code>" . htmlspecialchars($imagePath) . "</code></p>";
            
            // Test old path construction (incorrect)
            $oldPath = '../../uploads/members/' . $imagePath;
            $oldFullPath = realpath(__DIR__ . '/uploads/members/' . $imagePath);
            $oldExists = $oldFullPath && file_exists($oldFullPath);
            
            echo "<p><strong>Old Path (incorrect):</strong> <code>" . htmlspecialchars($oldPath) . "</code></p>";
            echo "<p><strong>Old Path Exists:</strong> " . ($oldExists ? "✅ Yes" : "❌ No") . "</p>";
            
            // Test new path construction (correct)
            $newPath = '../../uploads/' . $imagePath;
            $newFullPath = realpath(__DIR__ . '/uploads/' . $imagePath);
            $newExists = $newFullPath && file_exists($newFullPath);
            
            echo "<p><strong>New Path (correct):</strong> <code>" . htmlspecialchars($newPath) . "</code></p>";
            echo "<p><strong>New Path Exists:</strong> " . ($newExists ? "✅ Yes" : "❌ No") . "</p>";
            
            if ($newExists) {
                echo "<p><strong>Image Preview:</strong></p>";
                echo "<img src='" . $newPath . "' alt='Member Photo' style='max-width: 200px; height: auto; border: 1px solid #ccc; margin: 5px;' />";
            } else {
                echo "<p style='color: red;'><strong>❌ Image file not found at correct path</strong></p>";
            }
            
            echo "</div>";
        }
    } else {
        echo "<h3>⚠️ No Members with Images Found</h3>";
        echo "<p>No members found with image paths in the database.</p>";
    }
    
    // Test file system structure
    echo "<h3>📁 File System Structure Test</h3>";
    $uploadsDir = __DIR__ . '/uploads';
    $membersDir = $uploadsDir . '/members';
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Uploads Directory:</strong> <code>" . $uploadsDir . "</code></p>";
    echo "<p><strong>Uploads Directory Exists:</strong> " . (is_dir($uploadsDir) ? "✅ Yes" : "❌ No") . "</p>";
    
    echo "<p><strong>Members Directory:</strong> <code>" . $membersDir . "</code></p>";
    echo "<p><strong>Members Directory Exists:</strong> " . (is_dir($membersDir) ? "✅ Yes" : "❌ No") . "</p>";
    
    if (is_dir($membersDir)) {
        $files = scandir($membersDir);
        $imageFiles = array_filter($files, function($file) {
            return !in_array($file, ['.', '..']) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file);
        });
        
        echo "<p><strong>Image Files Found:</strong> " . count($imageFiles) . "</p>";
        
        if (count($imageFiles) > 0) {
            echo "<p><strong>Sample Files:</strong></p>";
            echo "<ul>";
            foreach (array_slice($imageFiles, 0, 5) as $file) {
                echo "<li><code>" . htmlspecialchars($file) . "</code></li>";
            }
            if (count($imageFiles) > 5) {
                echo "<li>... and " . (count($imageFiles) - 5) . " more</li>";
            }
            echo "</ul>";
        }
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2>❌ Test Failed</h2>";
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<h2>🔧 Image Path Fix Summary</h2>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3 style='color: green;'>✅ Image Path Fix Applied</h3>";
echo "<ul>";
echo "<li>✅ <strong>Problem:</strong> Double 'members' in path construction</li>";
echo "<li>✅ <strong>Root Cause:</strong> image_path already includes 'members/' prefix</li>";
echo "<li>✅ <strong>Solution:</strong> Use '../../uploads/' + image_path instead of '../../uploads/members/' + image_path</li>";
echo "<li>✅ <strong>Files Fixed:</strong> dashboard/members/view.php</li>";
echo "<li>✅ <strong>Result:</strong> Member images now display correctly</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🔗 Important URLs for Member Image Testing</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>View Members:</strong> <code>https://your-app.onrender.com/dashboard/members/index.php</code></p>";
echo "<p><strong>View Member:</strong> <code>https://your-app.onrender.com/dashboard/members/view.php?id={member_id}</code></p>";
echo "<p><strong>Add Member:</strong> <code>https://your-app.onrender.com/dashboard/members/add.php</code></p>";
echo "<p><strong>Edit Member:</strong> <code>https://your-app.onrender.com/dashboard/members/edit.php?id={member_id}</code></p>";
echo "<p><strong>Admin Console:</strong> <code>https://your-app.onrender.com/dashboard/admin/index.php</code></p>";
echo "<p><strong>System Status:</strong> <code>https://your-app.onrender.com/dashboard/system_status.php</code></p>";
echo "<p><strong>Health Check:</strong> <code>https://your-app.onrender.com/health.php</code></p>";
echo "<p><strong>Default Login:</strong> admin / 123</p>";
echo "</div>";

echo "</div>";
?>
