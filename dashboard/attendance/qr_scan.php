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
    $event_id = intval($_POST['event_id']);
    $user_id = $_SESSION['user_id'];
    $date = date('Y-m-d H:i:s');

    // Get user email from users table
    $stmt = $db->prepare('SELECT email FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit();
    }
    $email = $user['email'];

    // Get member info from membership_monitoring using email
    $stmt = $db->prepare('SELECT id, name, club_position FROM membership_monitoring WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$member) {
        echo json_encode(['status' => 'error', 'message' => 'Member record not found for this email']);
        exit();
    }
    $member_id = $member['id'];
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
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
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
            <a href="../index.php" class="btn glow-btn w-100 mt-4"><i class="fas fa-arrow-left me-1"></i>Return</a>
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
        let html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader", { fps: 10, qrbox: 250 }
        );
        html5QrcodeScanner.render(onScanSuccess);

        // Attendance AJAX
        function markAttendance(event_id) {
            fetch('qr_scan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'event_id=' + encodeURIComponent(event_id)
            })
            .then(response => response.json())
            .then(data => {
                let resultDiv = document.getElementById('qr-result');
                if (data.status === 'success') {
                    resultDiv.innerHTML = '<div class="alert alert-success">Attendance marked successfully!</div>';
                } else if (data.status === 'already_marked') {
                    resultDiv.innerHTML = '<div class="alert alert-info">Attendance already marked for this event.</div>';
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger">Failed to mark attendance. Try again.</div>';
                }
            });
        }
    </script>
</body>
</html>
