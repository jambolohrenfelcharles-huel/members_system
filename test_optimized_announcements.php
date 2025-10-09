<?php
/**
 * Test Optimized Announcement System
 * Test the async announcement system for Render performance
 */

echo "<h1>Optimized Announcement System Test</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px;'>";

try {
    // Test database connection
    echo "<h2>🔧 Testing Database Connection</h2>";
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        $isPostgreSQL = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql';
        echo "<p style='color: green;'>✅ Database connection successful</p>";
        echo "<p>Database type: " . ($isPostgreSQL ? 'PostgreSQL' : 'MySQL') . "</p>";
        
        // Test email queue tables
        echo "<h3>📧 Testing Email Queue Tables</h3>";
        
        $tables = ['email_queue', 'email_queue_items'];
        foreach ($tables as $table) {
            try {
                $stmt = $db->query("SELECT COUNT(*) as total FROM $table");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<p style='color: green;'>✅ $table table: " . $result['total'] . " records</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ $table table issue: " . $e->getMessage() . "</p>";
            }
        }
        
        // Test async notification helper
        echo "<h3>⚡ Testing Async Notification Helper</h3>";
        
        try {
            require_once 'config/async_notification_helper.php';
            $asyncHelper = new AsyncNotificationHelper($db);
            echo "<p style='color: green;'>✅ AsyncNotificationHelper instantiated successfully</p>";
            
            // Test queue status
            $queueStatus = $asyncHelper->getQueueStatus();
            if ($queueStatus) {
                echo "<p><strong>Email Queue Status:</strong></p>";
                echo "<ul>";
                foreach ($queueStatus as $status => $count) {
                    echo "<li><strong>$status:</strong> $count items</li>";
                }
                echo "</ul>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ AsyncNotificationHelper issue: " . $e->getMessage() . "</p>";
        }
        
        // Test announcement insertion with async processing
        echo "<h3>📝 Testing Optimized Announcement Insertion</h3>";
        
        try {
            $testTitle = "Performance Test Announcement - " . date('Y-m-d H:i:s');
            $testContent = "This is a test announcement to verify the optimized async email processing system works correctly.";
            
            // Measure execution time
            $startTime = microtime(true);
            
            // Insert announcement
            $stmt = $db->prepare("INSERT INTO announcements (title, content) VALUES (?, ?)");
            $result = $stmt->execute([$testTitle, $testContent]);
            
            if ($result) {
                $announcementId = $db->lastInsertId();
                echo "<p style='color: green;'>✅ Test announcement inserted successfully (ID: $announcementId)</p>";
                
                // Test async email queueing
                $asyncHelper = new AsyncNotificationHelper($db);
                $queueResult = $asyncHelper->queueAnnouncementNotification($announcementId, $testTitle, $testContent);
                
                $endTime = microtime(true);
                $executionTime = round(($endTime - $startTime) * 1000, 2);
                
                if ($queueResult['success']) {
                    echo "<p style='color: green;'>✅ Email queueing successful (Queue ID: " . $queueResult['queue_id'] . ")</p>";
                    echo "<p style='color: green;'>✅ Total execution time: <strong>{$executionTime}ms</strong></p>";
                    echo "<p style='color: green;'>✅ Members queued: " . $queueResult['total_members'] . "</p>";
                    
                    if ($executionTime < 1000) {
                        echo "<p style='color: green;'>🚀 <strong>Performance: EXCELLENT</strong> - Under 1 second!</p>";
                    } elseif ($executionTime < 2000) {
                        echo "<p style='color: orange;'>⚡ <strong>Performance: GOOD</strong> - Under 2 seconds</p>";
                    } else {
                        echo "<p style='color: red;'>⚠️ <strong>Performance: NEEDS IMPROVEMENT</strong> - Over 2 seconds</p>";
                    }
                } else {
                    echo "<p style='color: red;'>❌ Email queueing failed: " . $queueResult['error'] . "</p>";
                }
                
                // Clean up test announcement
                $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
                $stmt->execute([$announcementId]);
                echo "<p style='color: green;'>✅ Test announcement cleaned up</p>";
                
            } else {
                echo "<p style='color: red;'>❌ Failed to insert test announcement</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Announcement insertion test failed: " . $e->getMessage() . "</p>";
        }
        
        // Test email queue processing
        echo "<h3>🔄 Testing Email Queue Processing</h3>";
        
        try {
            $asyncHelper = new AsyncNotificationHelper($db);
            $processResult = $asyncHelper->processEmailQueue(5); // Process 5 emails
            
            if ($processResult['success']) {
                echo "<p style='color: green;'>✅ Email queue processing successful</p>";
                echo "<p>Processed: " . $processResult['processed'] . " items</p>";
                echo "<p>Sent: " . $processResult['sent'] . " emails</p>";
                echo "<p>Failed: " . $processResult['failed'] . " emails</p>";
            } else {
                echo "<p style='color: red;'>❌ Email queue processing failed: " . $processResult['error'] . "</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Email queue processing test failed: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
    }
    
    echo "<h2>🎯 Performance Optimization Summary</h2>";
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: green;'>✅ Optimizations Applied</h3>";
    echo "<ul>";
    echo "<li><strong>Async Email Processing:</strong> Emails are queued instead of sent synchronously</li>";
    echo "<li><strong>Database Indexes:</strong> Added indexes for email queue performance</li>";
    echo "<li><strong>Background Processing:</strong> Email queue processed in background</li>";
    echo "<li><strong>Render Scaling:</strong> Upgraded to starter plan with auto-scaling</li>";
    echo "<li><strong>Cron Jobs:</strong> Automated email queue processing</li>";
    echo "<li><strong>Performance Monitoring:</strong> Execution time tracking</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🚀 Performance Benefits</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<ul>";
    echo "<li><strong>Faster Form Submission:</strong> Announcements post instantly without waiting for emails</li>";
    echo "<li><strong>Better User Experience:</strong> No more lag or timeouts</li>";
    echo "<li><strong>Scalable Email Processing:</strong> Can handle large member lists</li>";
    echo "<li><strong>Reliable Delivery:</strong> Failed emails can be retried</li>";
    echo "<li><strong>Resource Efficient:</strong> Better use of Render resources</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🔗 How to Use Optimized System</h3>";
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4 style='color: orange;'>⚠️ Important Notes</h4>";
    echo "<ol>";
    echo "<li><strong>Instant Posting:</strong> Announcements are posted immediately</li>";
    echo "<li><strong>Background Emails:</strong> Emails are sent in the background</li>";
    echo "<li><strong>Queue Status:</strong> Check email queue status in admin panel</li>";
    echo "<li><strong>Processing Time:</strong> Emails typically sent within 1-2 minutes</li>";
    echo "<li><strong>Error Handling:</strong> Failed emails are logged and can be retried</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>🔗 Important URLs for Render</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Add Announcements:</strong> <code>https://your-app.onrender.com/dashboard/announcements/add.php</code></p>";
    echo "<p><strong>View Announcements:</strong> <code>https://your-app.onrender.com/dashboard/announcements/index.php</code></p>";
    echo "<p><strong>Email Queue Processor:</strong> <code>https://your-app.onrender.com/process_email_queue.php</code></p>";
    echo "<p><strong>Admin Console:</strong> <code>https://your-app.onrender.com/dashboard/admin/index.php</code></p>";
    echo "<p><strong>System Status:</strong> <code>https://your-app.onrender.com/dashboard/system_status.php</code></p>";
    echo "<p><strong>Health Check:</strong> <code>https://your-app.onrender.com/health.php</code></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Test Failed</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</div>";
?>
