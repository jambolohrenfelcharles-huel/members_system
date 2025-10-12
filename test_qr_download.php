<?php
/**
 * QR CODE DOWNLOAD TEST
 * This tests the QR code download functionality
 */

echo "<h1>📱 QR Code Download Test</h1>";
echo "<p>Testing QR code generation and download functionality...</p>";

// Step 1: Database Connection Test
echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🗄️ Step 1: Database Connection Test</h3>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ <strong>Database Connection:</strong> SUCCESS<br>";
    
    // Get a sample event
    $stmt = $db->prepare("SELECT * FROM events ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($event) {
        echo "✅ <strong>Sample Event Found:</strong> ID " . $event['id'] . " - " . htmlspecialchars($event['title']) . "<br>";
        $testEventId = $event['id'];
    } else {
        echo "❌ <strong>No Events Found:</strong> Create an event first<br>";
        $testEventId = 1; // Use default for testing
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Database Connection:</strong> FAILED - " . $e->getMessage() . "<br>";
    $testEventId = 1;
}

echo "</div>";

// Step 2: QR Code Generation Test
echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔧 Step 2: QR Code Generation Test</h3>";

try {
    // Test QR code payload generation
    $qrPayload = [
        'type' => 'attendance',
        'event_id' => $testEventId,
        'event_name' => 'Test Event',
        'ts' => time()
    ];
    $qrText = json_encode($qrPayload);
    
    echo "✅ <strong>QR Payload Generated:</strong> " . htmlspecialchars($qrText) . "<br>";
    
    // Test QR code APIs
    $qrApis = [
        'QR Server' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrText),
        'QuickChart' => 'https://quickchart.io/qr?text=' . urlencode($qrText) . '&size=300',
        'Google Charts' => 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($qrText)
    ];
    
    $workingApi = null;
    foreach ($qrApis as $apiName => $apiUrl) {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'SmartApp-QR-Test/1.0',
                    'method' => 'GET',
                    'ignore_errors' => true
                ]
            ]);
            
            $qrImage = file_get_contents($apiUrl, false, $context);
            
            if ($qrImage !== false && strlen($qrImage) > 100) {
                echo "✅ <strong>$apiName:</strong> SUCCESS (" . strlen($qrImage) . " bytes)<br>";
                $workingApi = $apiName;
                break;
            } else {
                echo "❌ <strong>$apiName:</strong> FAILED<br>";
            }
        } catch (Exception $e) {
            echo "❌ <strong>$apiName:</strong> ERROR - " . $e->getMessage() . "<br>";
        }
    }
    
    if ($workingApi) {
        echo "<strong>Working API:</strong> $workingApi<br>";
    } else {
        echo "❌ <strong>All QR APIs Failed</strong><br>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>QR Generation Test:</strong> FAILED - " . $e->getMessage() . "<br>";
}

echo "</div>";

// Step 3: Download Endpoint Test
echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>📥 Step 3: Download Endpoint Test</h3>";

echo "<h4>Test Download Links:</h4>";
echo "<ul>";
echo "<li><a href='download_qr.php?event_id=$testEventId' target='_blank'>🔗 Direct Download Link</a></li>";
echo "<li><a href='download_qr.php?event_id=$testEventId' download='test_qr.png'>📱 Download QR Code</a></li>";
echo "</ul>";

echo "<h4>Manual Test Steps:</h4>";
echo "<ol>";
echo "<li>Click the download links above</li>";
echo "<li>Check if a QR code image is downloaded</li>";
echo "<li>Verify the image is a valid QR code</li>";
echo "<li>Test with different events if available</li>";
echo "</ol>";

echo "</div>";

// Step 4: Event View Test
echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🖥️ Step 4: Event View Test</h3>";

echo "<h4>Test URLs:</h4>";
echo "<ul>";
echo "<li><a href='dashboard/events/view.php?id=$testEventId' target='_blank'>📊 Event View Page</a></li>";
echo "<li><a href='dashboard/events/index.php' target='_blank'>📅 Events List</a></li>";
echo "</ul>";

echo "<h4>Expected Results:</h4>";
echo "<ul>";
echo "<li>✅ <strong>Event View:</strong> Shows QR code and download button</li>";
echo "<li>✅ <strong>Download Button:</strong> Downloads actual QR code image</li>";
echo "<li>✅ <strong>QR Code Display:</strong> Shows generated QR code</li>";
echo "<li>✅ <strong>No Errors:</strong> No PHP errors or warnings</li>";
echo "</ul>";

echo "</div>";

// Step 5: QR Code Display Test
echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>📱 Step 5: QR Code Display Test</h3>";

echo "<h4>Sample QR Code (if APIs work):</h4>";
echo "<div style='text-align: center; padding: 20px; border: 2px solid #ddd; border-radius: 10px; background: white;'>";

try {
    $testText = "Test QR Code - Event ID: $testEventId";
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($testText);
    
    echo "<img src='$qrUrl' alt='Test QR Code' style='max-width: 200px; height: auto;'><br>";
    echo "<p><small>Test QR Code for Event ID: $testEventId</small></p>";
    
} catch (Exception $e) {
    echo "<p>❌ QR Code display failed: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "</div>";

// Step 6: Troubleshooting
echo "<div style='background: #e2e3e5; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #6c757d;'>";
echo "<h2>🔧 QR Code Download Troubleshooting</h2>";

echo "<h3>Common Issues and Solutions:</h3>";

echo "<h4>1. Download Not Working:</h4>";
echo "<ul>";
echo "<li>✅ <strong>Check Network:</strong> Ensure internet connection for QR APIs</li>";
echo "<li>✅ <strong>Check Browser:</strong> Try different browsers (Chrome, Firefox, Safari)</li>";
echo "<li>✅ <strong>Check Pop-ups:</strong> Allow pop-ups for download links</li>";
echo "<li>✅ <strong>Check File Permissions:</strong> Ensure download directory is writable</li>";
echo "</ul>";

echo "<h4>2. QR Code Not Generating:</h4>";
echo "<ul>";
echo "<li>✅ <strong>API Issues:</strong> QR APIs might be down, try different ones</li>";
echo "<li>✅ <strong>Payload Size:</strong> QR text might be too long</li>";
echo "<li>✅ <strong>Network Timeout:</strong> Increase timeout settings</li>";
echo "<li>✅ <strong>Error Logs:</strong> Check server error logs for details</li>";
echo "</ul>";

echo "<h4>3. Invalid QR Code:</h4>";
echo "<ul>";
echo "<li>✅ <strong>Scan Test:</strong> Use QR scanner to verify code works</li>";
echo "<li>✅ <strong>Payload Format:</strong> Ensure JSON payload is valid</li>";
echo "<li>✅ <strong>Character Encoding:</strong> Check for special characters</li>";
echo "<li>✅ <strong>Size Settings:</strong> Try different QR code sizes</li>";
echo "</ul>";

echo "<h3>🔧 Quick Fixes:</h3>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
echo "// Test QR code generation manually:\n";
echo "\$qrText = 'Test QR Code';\n";
echo "\$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode(\$qrText);\n";
echo "\$qrImage = file_get_contents(\$qrUrl);\n";
echo "if (\$qrImage && strlen(\$qrImage) > 100) {\n";
echo "    echo 'QR Code generated successfully';\n";
echo "} else {\n";
echo "    echo 'QR Code generation failed';\n";
echo "}\n";
echo "</pre>";

echo "</div>";

echo "<div style='background: #d4edda; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #28a745;'>";
echo "<h2>🎉 QR Code Download Test Complete!</h2>";
echo "<p><strong>Test the QR code download functionality using the links above!</strong></p>";
echo "<p>If downloads work, the QR code system is functioning correctly.</p>";
echo "<p><strong>Check your downloads folder for the QR code image!</strong></p>";
echo "</div>";
?>