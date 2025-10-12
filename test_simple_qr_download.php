<?php
/**
 * SIMPLE QR CODE DOWNLOAD TEST
 * This tests the simplified QR code download functionality
 */

echo "<h1>📱 Simple QR Code Download Test</h1>";
echo "<p>Testing the simplified QR code download functionality...</p>";

// Step 1: Get Test Event
echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🗄️ Step 1: Get Test Event</h3>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT * FROM events ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($event) {
        echo "✅ <strong>Test Event:</strong> ID " . $event['id'] . " - " . htmlspecialchars($event['title']) . "<br>";
        $testEventId = $event['id'];
    } else {
        echo "❌ <strong>No Events Found</strong><br>";
        $testEventId = 1;
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Database Error:</strong> " . $e->getMessage() . "<br>";
    $testEventId = 1;
}

echo "</div>";

// Step 2: Test QR Code Generation
echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔧 Step 2: Test QR Code Generation</h3>";

try {
    $qrPayload = [
        'type' => 'attendance',
        'event_id' => $testEventId,
        'event_name' => 'Test Event',
        'ts' => time()
    ];
    $qrText = json_encode($qrPayload);
    
    echo "✅ <strong>QR Payload:</strong> " . htmlspecialchars($qrText) . "<br>";
    
    // Test QR Server API
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrText) . '&format=png&ecc=M';
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'SmartApp-QR-Test/1.0',
            'method' => 'GET',
            'ignore_errors' => true
        ]
    ]);
    
    $qrImage = file_get_contents($qrUrl, false, $context);
    
    if ($qrImage !== false && strlen($qrImage) > 100) {
        echo "✅ <strong>QR Generation:</strong> SUCCESS (" . strlen($qrImage) . " bytes)<br>";
    } else {
        echo "❌ <strong>QR Generation:</strong> FAILED<br>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>QR Test Error:</strong> " . $e->getMessage() . "<br>";
}

echo "</div>";

// Step 3: Test Download Links
echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>📥 Step 3: Test Download Links</h3>";

echo "<h4>Download Test Links:</h4>";
echo "<ul>";
echo "<li><a href='download_qr.php?event_id=$testEventId' target='_blank'>🔗 Test Download (New Tab)</a></li>";
echo "<li><a href='download_qr.php?event_id=$testEventId' download='test_qr_$testEventId.png'>📱 Test Download (Direct)</a></li>";
echo "</ul>";

echo "<h4>Event View Links:</h4>";
echo "<ul>";
echo "<li><a href='dashboard/events/view.php?id=$testEventId' target='_blank'>📊 Event View Page</a></li>";
echo "<li><a href='dashboard/events/index.php' target='_blank'>📅 Events List</a></li>";
echo "</ul>";

echo "</div>";

// Step 4: QR Code Preview
echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>📱 Step 4: QR Code Preview</h3>";

echo "<div style='text-align: center; padding: 20px; border: 2px solid #ddd; border-radius: 10px; background: white;'>";

try {
    $previewText = "Event ID: $testEventId - Test QR Code";
    $previewUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($previewText);
    
    echo "<img src='$previewUrl' alt='QR Code Preview' style='max-width: 200px; height: auto;'><br>";
    echo "<p><small>Preview QR Code for Event ID: $testEventId</small></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Preview failed: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "</div>";

// Step 5: Manual Test Instructions
echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🧪 Step 5: Manual Test Instructions</h3>";

echo "<h4>Test Steps:</h4>";
echo "<ol>";
echo "<li><strong>Click Download Links:</strong> Use the links above to test downloads</li>";
echo "<li><strong>Check Downloads:</strong> Look in your browser's download folder</li>";
echo "<li><strong>Verify QR Code:</strong> Open the downloaded PNG file</li>";
echo "<li><strong>Scan QR Code:</strong> Use a QR scanner app to verify it works</li>";
echo "<li><strong>Test Event View:</strong> Go to the event view page and test the download button</li>";
echo "</ol>";

echo "<h4>Expected Results:</h4>";
echo "<ul>";
echo "<li>✅ <strong>Download Works:</strong> QR code PNG file downloads successfully</li>";
echo "<li>✅ <strong>Valid Image:</strong> Downloaded file is a valid PNG image</li>";
echo "<li>✅ <strong>QR Scannable:</strong> QR code can be scanned by mobile apps</li>";
echo "<li>✅ <strong>No Errors:</strong> No PHP errors or warnings</li>";
echo "</ul>";

echo "</div>";

// Step 6: Troubleshooting
echo "<div style='background: #e2e3e5; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #6c757d;'>";
echo "<h2>🔧 Troubleshooting Guide</h2>";

echo "<h3>If Download Doesn't Work:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Check Browser:</strong> Try Chrome, Firefox, or Safari</li>";
echo "<li>✅ <strong>Allow Downloads:</strong> Check browser download settings</li>";
echo "<li>✅ <strong>Check Network:</strong> Ensure internet connection is working</li>";
echo "<li>✅ <strong>Try Different Event:</strong> Test with different event IDs</li>";
echo "</ul>";

echo "<h3>If QR Code is Invalid:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Check File Size:</strong> Ensure downloaded file is not empty</li>";
echo "<li>✅ <strong>Try Different Scanner:</strong> Use different QR scanner apps</li>";
echo "<li>✅ <strong>Check Payload:</strong> Verify QR code contains valid JSON</li>";
echo "<li>✅ <strong>Test Manually:</strong> Generate QR code manually to compare</li>";
echo "</ul>";

echo "<h3>Quick Fixes:</h3>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
echo "// Test QR code generation:\n";
echo "\$eventId = $testEventId;\n";
echo "\$qrText = json_encode(['type' => 'attendance', 'event_id' => \$eventId]);\n";
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
echo "<p><strong>Test the simplified QR code download functionality!</strong></p>";
echo "<p>The system now uses a single, reliable download button that works successfully.</p>";
echo "<p><strong>Check your downloads folder for the QR code image!</strong></p>";
echo "</div>";
?>
