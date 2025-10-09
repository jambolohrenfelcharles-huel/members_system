<?php
/**
 * Test QR Code Generation for Events
 * This script tests the QR code generation functionality for ongoing events
 */

echo "<h1>QR Code Generation Test</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

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
    
    // Check if events table exists and has ongoing events
    $stmt = $db->query("SELECT COUNT(*) FROM events WHERE status = 'ongoing'");
    $ongoingCount = $stmt->fetchColumn();
    
    echo "<h2>📊 Events Status</h2>";
    echo "<p><strong>Ongoing Events:</strong> $ongoingCount</p>";
    
    if ($ongoingCount > 0) {
        // Get an ongoing event for testing
        $stmt = $db->query("SELECT * FROM events WHERE status = 'ongoing' LIMIT 1");
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($event) {
            echo "<h2>🎯 Testing QR Code for Event</h2>";
            echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<p><strong>Event ID:</strong> " . $event['id'] . "</p>";
            echo "<p><strong>Event Title:</strong> " . htmlspecialchars($event['title']) . "</p>";
            echo "<p><strong>Status:</strong> " . $event['status'] . "</p>";
            echo "<p><strong>Event Date:</strong> " . $event['event_date'] . "</p>";
            echo "</div>";
            
            // Generate QR code payload
            $payload = [
                'type' => 'attendance',
                'event_id' => (int)$event['id'],
                'event_name' => $event['title'],
                'ts' => time()
            ];
            $qrText = json_encode($payload);
            
            echo "<h3>📱 QR Code Payload</h3>";
            echo "<div style='background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<code>" . htmlspecialchars($qrText) . "</code>";
            echo "</div>";
            
            // Test QR code generation with JavaScript
            echo "<h3>🔍 QR Code Generation Test</h3>";
            echo "<div id='testQrContainer' style='background: white; padding: 20px; border-radius: 5px; margin: 10px 0; text-align: center; min-height: 200px; display: flex; align-items: center; justify-content: center;'>";
            echo "<div class='spinner-border text-primary' role='status'>";
            echo "<span class='visually-hidden'>Generating QR Code...</span>";
            echo "</div>";
            echo "</div>";
            
            echo "<div id='qrTestError' class='alert alert-warning d-none' role='alert'>";
            echo "<i class='fas fa-exclamation-triangle me-2'></i>";
            echo "<span id='qrTestErrorMessage'>QR Code generation failed.</span>";
            echo "</div>";
            
            echo "<button id='testDownloadBtn' class='btn btn-primary' onclick='downloadTestQr()'>Download Test QR Code</button>";
            
        } else {
            echo "<h2>⚠️ No Ongoing Events Found</h2>";
            echo "<p>No ongoing events found in the database. Create an event and set its status to 'ongoing' to test QR code generation.</p>";
        }
    } else {
        echo "<h2>⚠️ No Ongoing Events</h2>";
        echo "<p>No ongoing events found in the database. Create an event and set its status to 'ongoing' to test QR code generation.</p>";
        
        // Show how to create a test event
        echo "<h3>📝 How to Create a Test Event</h3>";
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<ol>";
        echo "<li>Go to <code>dashboard/events/add.php</code></li>";
        echo "<li>Fill in the event details</li>";
        echo "<li>Set the status to 'ongoing'</li>";
        echo "<li>Save the event</li>";
        echo "<li>Go to <code>dashboard/events/view.php?id={event_id}</code></li>";
        echo "<li>The QR code should appear for ongoing events</li>";
        echo "</ol>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Test Failed</h2>";
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<h2>🔗 Important URLs</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>Add Events:</strong> <code>dashboard/events/add.php</code></p>";
echo "<p><strong>View Events:</strong> <code>dashboard/events/index.php</code></p>";
echo "<p><strong>View Specific Event:</strong> <code>dashboard/events/view.php?id={event_id}</code></p>";
echo "<p><strong>QR Scan (Attendance):</strong> <code>dashboard/attendance/qr_scan.php</code></p>";
echo "</div>";

echo "<h2>📋 QR Code Features</h2>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>✅ QR Code Features for Ongoing Events</h3>";
echo "<ul>";
echo "<li>✅ <strong>Conditional Display:</strong> Only shows for events with status 'ongoing'</li>";
echo "<li>✅ <strong>Dual Library Support:</strong> Primary QRCodeJS + Fallback QRCode library</li>";
echo "<li>✅ <strong>Error Handling:</strong> Graceful fallback and error messages</li>";
echo "<li>✅ <strong>Loading States:</strong> Spinner while generating QR code</li>";
echo "<li>✅ <strong>Download Functionality:</strong> Download QR code as PNG</li>";
echo "<li>✅ <strong>Console Logging:</strong> Detailed logs for debugging</li>";
echo "<li>✅ <strong>Responsive Design:</strong> Works on all screen sizes</li>";
echo "<li>✅ <strong>CDN Fallback:</strong> Multiple CDN sources for reliability</li>";
echo "</ul>";
echo "</div>";

echo "</div>";

// Add JavaScript for QR code testing
echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>";
echo "<script src='https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js'></script>";
echo "<script src='https://unpkg.com/qrcode@1.5.3/build/qrcode.min.js'></script>";
echo "<script>";
echo "document.addEventListener('DOMContentLoaded', function() {";
echo "    console.log('QR Code Test Page Loaded');";
echo "    ";
echo "    var container = document.getElementById('testQrContainer');";
echo "    if (!container) return;";
echo "    ";
echo "    // Test QR code generation";
echo "    var payload = {";
echo "        type: 'attendance',";
echo "        event_id: " . (isset($event) ? $event['id'] : 1) . ",";
echo "        event_name: " . (isset($event) ? json_encode($event['title']) : '"Test Event"') . ",";
echo "        ts: Date.now()";
echo "    };";
echo "    var text = JSON.stringify(payload);";
echo "    ";
echo "    console.log('Generating test QR code with payload:', text);";
echo "    ";
echo "    // Try primary library first";
echo "    if (typeof QRCode !== 'undefined') {";
echo "        try {";
echo "            var qr = new QRCode(container, {";
echo "                text: text,";
echo "                width: 192,";
echo "                height: 192,";
echo "                correctLevel: QRCode.CorrectLevel.M,";
echo "                colorDark: '#000000',";
echo "                colorLight: '#ffffff'";
echo "            });";
echo "            console.log('Test QR code generated successfully with primary library');";
echo "        } catch (error) {";
echo "            console.error('Primary library failed:', error);";
echo "            // Try fallback";
echo "            if (typeof QRCodeLib !== 'undefined') {";
echo "                container.innerHTML = '';";
echo "                QRCodeLib.toCanvas(container, text, {";
echo "                    width: 192,";
echo "                    height: 192,";
echo "                    color: { dark: '#000000', light: '#ffffff' }";
echo "                }, function (error) {";
echo "                    if (error) {";
echo "                        console.error('Fallback library failed:', error);";
echo "                        showTestError('Both QR code libraries failed');";
echo "                    } else {";
echo "                        console.log('Test QR code generated successfully with fallback library');";
echo "                    }";
echo "                });";
echo "            } else {";
echo "                showTestError('No QR code libraries available');";
echo "            }";
echo "        }";
echo "    } else {";
echo "        showTestError('Primary QR code library not loaded');";
echo "    }";
echo "    ";
echo "    function showTestError(message) {";
echo "        var errorDiv = document.getElementById('qrTestError');";
echo "        var errorMessage = document.getElementById('qrTestErrorMessage');";
echo "        if (errorDiv && errorMessage) {";
echo "            errorMessage.textContent = message;";
echo "            errorDiv.classList.remove('d-none');";
echo "        }";
echo "        container.innerHTML = '<div class=\"text-danger\"><i class=\"fas fa-exclamation-triangle me-2\"></i>QR Code Error</div>';";
echo "    }";
echo "});";
echo "";
echo "function downloadTestQr() {";
echo "    var container = document.getElementById('testQrContainer');";
echo "    var img = container.querySelector('img');";
echo "    var canvas = container.querySelector('canvas');";
echo "    ";
echo "    if (img && img.src) {";
echo "        var a = document.createElement('a');";
echo "        a.href = img.src;";
echo "        a.download = 'test_event_qr.png';";
echo "        document.body.appendChild(a);";
echo "        a.click();";
echo "        document.body.removeChild(a);";
echo "        console.log('Test QR code downloaded as image');";
echo "    } else if (canvas) {";
echo "        var a2 = document.createElement('a');";
echo "        a2.href = canvas.toDataURL('image/png');";
echo "        a2.download = 'test_event_qr.png';";
echo "        document.body.appendChild(a2);";
echo "        a2.click();";
echo "        document.body.removeChild(a2);";
echo "        console.log('Test QR code downloaded as canvas');";
echo "    } else {";
echo "        alert('No QR code available for download');";
echo "    }";
echo "}";
echo "</script>";
?>
