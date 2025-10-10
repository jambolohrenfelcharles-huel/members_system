<?php
// dashboard/attendance/qr_scan.php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle AJAX attendance marking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    // Set proper headers for AJAX response
    header('Content-Type: application/json');
    
    try {
        $event_id = intval($_POST['event_id']);
        $user_id = $_SESSION['user_id'];
        $date = date('Y-m-d H:i:s');

        // Validate event_id
        if ($event_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid event ID']);
            exit();
        }

        // Get user email from users table
        $stmt = $db->prepare('SELECT email FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit();
        }
        $email = $user['email'];

        // Get member info from members table using email
        $members_table = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql' ? 'members' : 'membership_monitoring';
        
        // Handle different table structures
        if ($_ENV['DB_TYPE'] === 'postgresql') {
            // PostgreSQL uses 'members' table with 'member_id' column
            $stmt = $db->prepare("SELECT id, member_id, name, club_position FROM $members_table WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$member) {
                echo json_encode(['status' => 'error', 'message' => 'Member record not found for this email']);
                exit();
            }
            $member_id = $member['member_id']; // Use the generated member_id (e.g., 'M20241234')
        } else {
            // MySQL uses 'membership_monitoring' table without 'member_id' column
            $stmt = $db->prepare("SELECT id, name, club_position FROM $members_table WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$member) {
                echo json_encode(['status' => 'error', 'message' => 'Member record not found for this email']);
                exit();
            }
            $member_id = 'M' . date('Y') . str_pad($member['id'], 4, '0', STR_PAD_LEFT); // Generate member_id from id
        }
        
        $full_name = $member['name'];
        $club_position = $member['club_position'] ?? '';

        // Check if already marked
        $stmt = $db->prepare('SELECT id FROM attendance WHERE member_id = ? AND event_id = ?');
        $stmt->execute([$member_id, $event_id]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'already_marked']);
            exit();
        }

        // Insert attendance with full_name and club_position
        $stmt = $db->prepare('INSERT INTO attendance (member_id, full_name, club_position, event_id, date) VALUES (?, ?, ?, ?, ?)');
        if ($stmt->execute([$member_id, $full_name, $club_position, $event_id, $date])) {
            echo json_encode(['status' => 'success', 'message' => 'Attendance marked successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to insert attendance record']);
        }
        
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("QR Scan Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Attendance Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body>
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1cc88a 0%, #4e54c8 100%);
            background-size: 200% 200%;
            animation: gradientMove 6s ease-in-out infinite;
        }
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .glow-card {
            background: rgba(255,255,255,0.97);
            border-radius: 1.5rem;
            box-shadow: 0 0 32px 0 #1cc88a55, 0 8px 32px rgba(60,60,120,0.13);
            max-width: 420px;
            width: 100%;
            padding: 2.5rem 2.2rem 2.2rem 2.2rem;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }
        .glow-icon {
            width: 70px; height: 70px;
            font-size: 2.7rem;
            background: linear-gradient(135deg, #1cc88a 0%, #4e54c8 100%);
            color: #fff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 18px #1cc88a99, 0 0 0 8px #1cc88a22;
            margin-bottom: 0.7rem;
            animation: iconPulse 1.7s infinite cubic-bezier(.23,1.01,.32,1);
        }
        @keyframes iconPulse {
            0% { box-shadow: 0 0 18px #1cc88a99, 0 0 0 8px #1cc88a22; }
            50% { box-shadow: 0 0 32px #4e54c8cc, 0 0 0 16px #1cc88a33; }
            100% { box-shadow: 0 0 18px #1cc88a99, 0 0 0 8px #1cc88a22; }
        }
        .glow-title {
            font-weight: 700;
            font-size: 2rem;
            letter-spacing: 0.5px;
            color: #222;
        }
        .glow-desc {
            color: #4e54c8;
            font-size: 1.08em;
            margin-bottom: 1.2rem;
        }
        .glow-btn {
            background: linear-gradient(90deg, #1cc88a 0%, #4e54c8 100%);
            color: #fff;
            border: none;
            border-radius: 2rem;
            font-weight: 600;
            box-shadow: 0 2px 12px #1cc88a33;
            transition: background 0.3s, box-shadow 0.3s;
        }
        .glow-btn:hover {
            background: linear-gradient(90deg, #4e54c8 0%, #1cc88a 100%);
            box-shadow: 0 4px 24px #4e54c855;
        }
        #qr-reader {
            border-radius: 1.1rem;
            overflow: hidden;
            box-shadow: 0 2px 12px #4e54c822;
            background: #f8f9fa;
        }
    </style>
    <div class="container py-5 d-flex justify-content-center align-items-center min-vh-100">
        <div class="glow-card">
            <div class="text-center mb-4">
                <span class="glow-icon"><i class="fas fa-qrcode"></i></span>
                <div class="glow-title mb-1">QR Code Attendance</div>
                <div class="glow-desc">Choose how to mark your attendance:</div>
                <!-- Option buttons removed: Only live scan is available -->
            </div>
            <div id="scan-section">
                <div id="qr-reader" style="width: 100%; min-height: 260px;"></div>
            </div>
            <div id="qr-result" class="mt-3"></div>
            
            <!-- Debug section (hidden by default) -->
            <div id="debug-section" class="mt-3" style="display: none;">
                <div class="alert alert-info">
                    <h6><i class="fas fa-bug me-2"></i>Debug Information</h6>
                    <div id="debug-info"></div>
                </div>
            </div>
            
            <div class="d-flex gap-2 mt-4">
                <button type="button" class="btn btn-outline-info btn-sm" onclick="toggleDebug()">
                    <i class="fas fa-bug me-1"></i>Debug
                </button>
                <button type="button" class="btn btn-outline-warning btn-sm" onclick="testQRScan()">
                    <i class="fas fa-test-tube me-1"></i>Test
                </button>
                <a href="../index.php" class="btn glow-btn flex-grow-1">
                    <i class="fas fa-arrow-left me-1"></i>Return
                </a>
            </div>
        </div>
    </div>
    <script>

        // QR Scan (live only)
        function onScanSuccess(decodedText, decodedResult) {
            let event_id = null;
            try {
                // Try to parse as JSON (for event QR codes)
                let obj = JSON.parse(decodedText);
                if (obj && obj.event_id) {
                    event_id = obj.event_id;
                }
            } catch (e) {
                // Fallback: treat as plain event_id
                event_id = decodedText;
            }
            if (event_id) {
                markAttendance(event_id);
            } else {
                let resultDiv = document.getElementById('qr-result');
                resultDiv.innerHTML = '<div class="alert alert-danger">Invalid QR code for event attendance.</div>';
            }
        }
        // Render-optimized QR scanner configuration
        let html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader", { 
                fps: 10, 
                qrbox: 250,
                aspectRatio: 1.0,
                disableFlip: false,
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                }
            }
        );
        
        // Add Render-specific error handling
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        
        function onScanFailure(error) {
            // Handle scan failures gracefully on Render
            console.log('QR scan failed:', error);
            // Don't show error to user unless it's a critical issue
        }
        
        // Debug functions
        function toggleDebug() {
            const debugSection = document.getElementById('debug-section');
            const debugInfo = document.getElementById('debug-info');
            
            if (debugSection.style.display === 'none') {
                // Show debug info
                fetch('qr_debug.php')
                    .then(response => response.json())
                    .then(data => {
                        debugInfo.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                        debugSection.style.display = 'block';
                    })
                    .catch(error => {
                        debugInfo.innerHTML = '<p class="text-danger">Debug failed: ' + error.message + '</p>';
                        debugSection.style.display = 'block';
                    });
            } else {
                debugSection.style.display = 'none';
            }
        }
        
        // Test QR scan function
        function testQRScan() {
            const testEventId = 1; // Test with event ID 1
            markAttendance(testEventId);
        }

        // Attendance AJAX with Render optimizations
        function markAttendance(event_id) {
            // Show loading state
            let resultDiv = document.getElementById('qr-result');
            resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Processing attendance...</div>';
            
            fetch('qr_scan.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'event_id=' + encodeURIComponent(event_id)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                let resultDiv = document.getElementById('qr-result');
                if (data.status === 'success') {
                    resultDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Attendance marked successfully!</div>';
                } else if (data.status === 'already_marked') {
                    resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Attendance already marked for this event.</div>';
                } else {
                    let errorMsg = data.message || 'Failed to mark attendance. Try again.';
                    resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>${errorMsg}</div>`;
                }
            })
            .catch(error => {
                console.error('QR Scan Error:', error);
                let resultDiv = document.getElementById('qr-result');
                resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-wifi me-2"></i>Network error. Please check your connection and try again.</div>';
            });
        }
    </script>
</body>
</html>
