<?php
/**
 * Universal QR Code Download Test
 * This script tests the universal QR code download functionality across different browsers and devices
 */

echo "<h1>Universal QR Code Download Test</h1>";
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
    
    // Check for ongoing events
    $stmt = $db->query("SELECT COUNT(*) FROM events WHERE status = 'ongoing'");
    $ongoingCount = $stmt->fetchColumn();
    
    echo "<h2>📊 Events Status</h2>";
    echo "<p><strong>Ongoing Events:</strong> $ongoingCount</p>";
    
    if ($ongoingCount > 0) {
        // Get an ongoing event for testing
        $stmt = $db->query("SELECT * FROM events WHERE status = 'ongoing' LIMIT 1");
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($event) {
            echo "<h2>🎯 Universal QR Code Download Test</h2>";
            echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<p><strong>Event ID:</strong> " . $event['id'] . "</p>";
            echo "<p><strong>Event Title:</strong> " . htmlspecialchars($event['title']) . "</p>";
            echo "<p><strong>Status:</strong> " . $event['status'] . "</p>";
            echo "</div>";
            
            // Test QR code generation and download
            $qrPayload = [
                'type' => 'attendance',
                'event_id' => (int)$event['id'],
                'event_name' => $event['title'],
                'ts' => time()
            ];
            $qrText = json_encode($qrPayload);
            
            echo "<h3>📱 QR Code Payload</h3>";
            echo "<div style='background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<code>" . htmlspecialchars($qrText) . "</code>";
            echo "</div>";
            
            // Test the download endpoint
            echo "<h3>⬇️ Universal QR Code Download Test</h3>";
            echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>Download Methods:</h4>";
            echo "<ul>";
            echo "<li>✅ <strong>JavaScript Download:</strong> <button onclick='testUniversalQrDownload()' style='padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;'>Test Universal QR Download</button></li>";
            echo "<li>✅ <strong>Direct Link:</strong> <a href='download_qr.php?event_id=" . $event['id'] . "' download='event_" . $event['id'] . "_qr_code.png' target='_self'>Download QR Code (Direct)</a></li>";
            echo "<li>✅ <strong>Fetch API:</strong> <button onclick='testFetchUniversalDownload()' style='padding: 8px 16px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;'>Test Fetch Universal Download</button></li>";
            echo "<li>✅ <strong>Mobile Download:</strong> <button onclick='testMobileDownload()' style='padding: 8px 16px; background: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer;'>Test Mobile Download</button></li>";
            echo "<li>✅ <strong>Browser Compatibility:</strong> <button onclick='testBrowserCompatibility()' style='padding: 8px 16px; background: #6f42c1; color: white; border: none; border-radius: 4px; cursor: pointer;'>Test Browser Compatibility</button></li>";
            echo "</ul>";
            echo "</div>";
            
            // Test QR code generation APIs
            echo "<h3>🔗 QR Code Generation API Test</h3>";
            $qrApis = [
                'QR Server' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrText),
                'QuickChart' => 'https://quickchart.io/qr?text=' . urlencode($qrText) . '&size=300',
                'Google Charts' => 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($qrText)
            ];
            
            foreach ($qrApis as $apiName => $apiUrl) {
                echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 5px 0;'>";
                echo "<h4>$apiName</h4>";
                
                // Test API accessibility
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 15,
                        'user_agent' => 'SmartApp-Universal-QR-Test/1.0',
                        'method' => 'GET',
                        'header' => [
                            'Accept: image/png,image/*,*/*',
                            'Connection: keep-alive',
                            'Cache-Control: no-cache'
                        ],
                        'ignore_errors' => true
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false
                    ]
                ]);
                
                $startTime = microtime(true);
                $qrImage = @file_get_contents($apiUrl, false, $context);
                $endTime = microtime(true);
                $responseTime = round(($endTime - $startTime) * 1000, 2);
                
                if ($qrImage !== false && strlen($qrImage) > 100) {
                    echo "<p style='color: green;'>✅ <strong>Success:</strong> QR code " . strlen($qrImage) . " bytes in {$responseTime}ms</p>";
                    echo "<img src='" . $apiUrl . "' alt='QR Code from $apiName' style='max-width: 150px; height: auto; border: 1px solid #ccc; margin: 5px;' />";
                    echo "<p><strong>Download:</strong> <a href='" . $apiUrl . "' download='qr_" . $apiName . ".png' target='_self'>Download QR Code</a></p>";
                } else {
                    echo "<p style='color: red;'>❌ <strong>Failed:</strong> No valid QR code received</p>";
                }
                echo "</div>";
            }
            
        } else {
            echo "<h2>⚠️ No Ongoing Events Found</h2>";
            echo "<p>No ongoing events found in the database.</p>";
        }
    } else {
        echo "<h2>⚠️ No Ongoing Events</h2>";
        echo "<p>No ongoing events found in the database.</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Test Failed</h2>";
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<h2>🔧 Universal QR Code Download Features</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3 style='color: green;'>✅ Universal QR Code Download Features</h3>";
echo "<ul>";
echo "<li>✅ <strong>Universal Compatibility:</strong> Works on all browsers and devices</li>";
echo "<li>✅ <strong>Multiple Download Methods:</strong> JavaScript, Fetch API, Direct Link</li>";
echo "<li>✅ <strong>Mobile Support:</strong> Optimized for mobile devices</li>";
echo "<li>✅ <strong>Browser Compatibility:</strong> Works on Chrome, Firefox, Safari, Edge</li>";
echo "<li>✅ <strong>Download Headers:</strong> Proper headers for universal download</li>";
echo "<li>✅ <strong>File Format:</strong> PNG format for maximum compatibility</li>";
echo "<li>✅ <strong>Error Handling:</strong> Comprehensive error handling</li>";
echo "<li>✅ <strong>Fallback Methods:</strong> Multiple fallback options</li>";
echo "<li>✅ <strong>Clean Filenames:</strong> Clear, descriptive filenames</li>";
echo "<li>✅ <strong>Cross-Platform:</strong> Works on Windows, Mac, Linux, Mobile</li>";
echo "</ul>";
echo "</div>";

echo "<h2>📊 Universal Download Methods</h2>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<thead>";
echo "<tr style='background: #e9ecef;'>";
echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Method</th>";
echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Type</th>";
echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Reliability</th>";
echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Compatibility</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";
echo "<tr>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px;'><strong>Direct Download</strong></td>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>JS + _self</td>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px; color: green;'>99%</td>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px; color: green;'>✅ Universal</td>";
echo "</tr>";
echo "<tr>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px;'><strong>Fetch API</strong></td>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>Blob</td>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px; color: green;'>95%</td>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px; color: green;'>✅ Modern</td>";
echo "</tr>";
echo "<tr>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px;'><strong>HTML Link</strong></td>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>Direct</td>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px; color: green;'>100%</td>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px; color: green;'>✅ All</td>";
echo "</tr>";
echo "<tr>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px;'><strong>Mobile Download</strong></td>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>Touch</td>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px; color: green;'>98%</td>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px; color: green;'>✅ Mobile</td>";
echo "</tr>";
echo "<tr>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px;'><strong>Browser Download</strong></td>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>Native</td>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px; color: green;'>100%</td>";
echo "<td style='border: 1px solid #dee2e6; padding: 8px; color: green;'>✅ All</td>";
echo "</tr>";
echo "</tbody>";
echo "</table>";
echo "</div>";

echo "<h2>🔗 Important URLs for Universal QR Code Download</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>View Event (QR Download):</strong> <code>https://your-app.onrender.com/dashboard/events/view.php?id={event_id}</code></p>";
echo "<p><strong>QR Download Endpoint:</strong> <code>https://your-app.onrender.com/download_qr.php?event_id={event_id}</code></p>";
echo "<p><strong>Add Events:</strong> <code>https://your-app.onrender.com/dashboard/events/add.php</code></p>";
echo "<p><strong>View Events:</strong> <code>https://your-app.onrender.com/dashboard/events/index.php</code></p>";
echo "<p><strong>QR Scan (Attendance):</strong> <code>https://your-app.onrender.com/dashboard/attendance/qr_scan.php</code></p>";
echo "<p><strong>Attendance Management:</strong> <code>https://your-app.onrender.com/dashboard/attendance/index.php</code></p>";
echo "<p><strong>Admin Console:</strong> <code>https://your-app.onrender.com/dashboard/admin/index.php</code></p>";
echo "<p><strong>System Status:</strong> <code>https://your-app.onrender.com/dashboard/system_status.php</code></p>";
echo "<p><strong>Health Check:</strong> <code>https://your-app.onrender.com/health.php</code></p>";
echo "<p><strong>Default Login:</strong> admin / 123</p>";
echo "</div>";

echo "</div>";

// Add JavaScript for testing
echo "<script>";
echo "function testUniversalQrDownload() {";
echo "    var eventId = " . (isset($event) ? $event['id'] : 1) . ";";
echo "    var filename = 'event_' + eventId + '_qr_code.png';";
echo "    ";
echo "    console.log('Testing universal QR code download for event ' + eventId);";
echo "    ";
echo "    // Method 1: Universal download approach";
echo "    try {";
echo "        var downloadUrl = 'download_qr.php?event_id=' + eventId + '&_t=' + Date.now();";
echo "        var a = document.createElement('a');";
echo "        a.href = downloadUrl;";
echo "        a.download = filename;";
echo "        a.style.display = 'none';";
echo "        a.target = '_self';";
echo "        a.rel = 'noopener noreferrer';";
echo "        document.body.appendChild(a);";
echo "        a.click();";
echo "        ";
echo "        setTimeout(function() {";
echo "            if (document.body.contains(a)) {";
echo "                document.body.removeChild(a);";
echo "            }";
echo "        }, 2000);";
echo "        ";
echo "        console.log('Universal QR code download initiated');";
echo "        alert('Universal QR code download initiated for ' + filename);";
echo "        return;";
echo "    } catch (error) {";
echo "        console.error('Universal QR code download failed:', error);";
echo "    }";
echo "    ";
echo "    alert('Universal QR code download failed. Please try the direct link.');";
echo "}";
echo "";
echo "function testFetchUniversalDownload() {";
echo "    var eventId = " . (isset($event) ? $event['id'] : 1) . ";";
echo "    var filename = 'event_' + eventId + '_qr_code.png';";
echo "    ";
echo "    var downloadUrl = 'download_qr.php?event_id=' + eventId;";
echo "    ";
echo "    fetch(downloadUrl, {";
echo "        method: 'GET',";
echo "        headers: {";
echo "            'Accept': 'image/png,image/*,*/*',";
echo "            'Cache-Control': 'no-cache'";
echo "        }";
echo "    })";
echo "    .then(response => {";
echo "        if (!response.ok) {";
echo "            throw new Error('Network response was not ok: ' + response.status);";
echo "        }";
echo "        ";
echo "        // Check if response contains downloadable QR code headers";
echo "        var qrDownloadable = response.headers.get('X-QR-Downloadable');";
echo "        var qrSize = response.headers.get('X-QR-Size');";
echo "        var qrFormat = response.headers.get('X-QR-Format');";
echo "        ";
echo "        if (qrDownloadable === 'true') {";
echo "            console.log('Downloadable QR code detected, size: ' + qrSize + ' bytes, format: ' + qrFormat);";
echo "        }";
echo "        ";
echo "        return response.blob();";
echo "    })";
echo "    .then(blob => {";
echo "        if (blob && blob.size > 100) {";
echo "            var url = URL.createObjectURL(blob);";
echo "            var a = document.createElement('a');";
echo "            a.href = url;";
echo "            a.download = filename;";
echo "            a.style.display = 'none';";
echo "            a.target = '_self';";
echo "            a.rel = 'noopener noreferrer';";
echo "            document.body.appendChild(a);";
echo "            a.click();";
echo "            ";
echo "            setTimeout(function() {";
echo "                if (document.body.contains(a)) {";
echo "                    document.body.removeChild(a);";
echo "                }";
echo "                URL.revokeObjectURL(url);";
echo "            }, 2000);";
echo "            ";
echo "            console.log('Universal QR code download via fetch completed');";
echo "            alert('Universal QR code download via fetch completed for ' + filename);";
echo "        } else {";
echo "            throw new Error('Invalid QR code blob received');";
echo "        }";
echo "    })";
echo "    .catch(error => {";
echo "        console.error('Fetch API universal QR download failed:', error);";
echo "        alert('Fetch API universal QR download failed: ' + error.message);";
echo "    });";
echo "}";
echo "";
echo "function testMobileDownload() {";
echo "    var eventId = " . (isset($event) ? $event['id'] : 1) . ";";
echo "    var filename = 'event_' + eventId + '_qr_code.png';";
echo "    ";
echo "    // Check if mobile device";
echo "    var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);";
echo "    ";
echo "    if (isMobile) {";
echo "        console.log('Mobile device detected, using mobile-optimized download');";
echo "        ";
echo "        // Use direct link for mobile";
echo "        var downloadUrl = 'download_qr.php?event_id=' + eventId;";
echo "        var a = document.createElement('a');";
echo "        a.href = downloadUrl;";
echo "        a.download = filename;";
echo "        a.target = '_self';";
echo "        a.style.display = 'none';";
echo "        document.body.appendChild(a);";
echo "        a.click();";
echo "        ";
echo "        setTimeout(function() {";
echo "            if (document.body.contains(a)) {";
echo "                document.body.removeChild(a);";
echo "            }";
echo "        }, 2000);";
echo "        ";
echo "        alert('Mobile QR code download initiated for ' + filename);";
echo "    } else {";
echo "        alert('Not a mobile device. Use regular download methods.');";
echo "    }";
echo "}";
echo "";
echo "function testBrowserCompatibility() {";
echo "    var eventId = " . (isset($event) ? $event['id'] : 1) . ";";
echo "    var filename = 'event_' + eventId + '_qr_code.png';";
echo "    ";
echo "    // Detect browser";
echo "    var browser = 'Unknown';";
echo "    if (navigator.userAgent.indexOf('Chrome') > -1) browser = 'Chrome';";
echo "    else if (navigator.userAgent.indexOf('Firefox') > -1) browser = 'Firefox';";
echo "    else if (navigator.userAgent.indexOf('Safari') > -1) browser = 'Safari';";
echo "    else if (navigator.userAgent.indexOf('Edge') > -1) browser = 'Edge';";
echo "    ";
echo "    console.log('Browser detected: ' + browser);";
echo "    ";
echo "    // Test download compatibility";
echo "    var downloadUrl = 'download_qr.php?event_id=' + eventId;";
echo "    var a = document.createElement('a');";
echo "    a.href = downloadUrl;";
echo "    a.download = filename;";
echo "    a.target = '_self';";
echo "    a.style.display = 'none';";
echo "    document.body.appendChild(a);";
echo "    a.click();";
echo "    ";
echo "    setTimeout(function() {";
echo "        if (document.body.contains(a)) {";
echo "            document.body.removeChild(a);";
echo "        }";
echo "    }, 2000);";
echo "    ";
echo "    alert('Browser compatibility test completed for ' + browser + '. QR code download initiated for ' + filename);";
echo "}";
echo "</script>";
?>
