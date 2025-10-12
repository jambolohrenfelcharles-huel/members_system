<?php
// Simple Event QR Code View - Single Download Button
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($eventId <= 0) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event QR Code - <?php echo htmlspecialchars($event['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .qr-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .qr-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .qr-body {
            padding: 30px;
            text-align: center;
        }
        .qr-code-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            margin: 20px 0;
            min-height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-download {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        .btn-download:hover {
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="qr-card">
                    <div class="qr-header">
                        <h4><i class="fas fa-qrcode me-2"></i>Event QR Code</h4>
                        <p class="mb-0"><?php echo htmlspecialchars($event['title']); ?></p>
                    </div>
                    <div class="qr-body">
                        <div class="qr-code-container" id="qrCodeContainer">
                            <div class="text-muted">
                                <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                                <p>Generating QR Code...</p>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <a href="../../download_qr.php?event_id=<?php echo $eventId; ?>" 
                               download="event_<?php echo $eventId; ?>_qr_code.png" 
                               class="btn-download">
                                <i class="fas fa-download me-2"></i>Download QR Code
                            </a>
                        </div>
                        
                        <div class="text-muted small">
                            <p><strong>Event:</strong> <?php echo htmlspecialchars($event['title']); ?></p>
                            <p><strong>Date:</strong> <?php echo date('M d, Y g:i A', strtotime($event['event_date'])); ?></p>
                            <p><strong>Status:</strong> <?php echo ucfirst($event['status']); ?></p>
                        </div>
                        
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Events
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            generateQRCode();
        });
        
        function generateQRCode() {
            var container = document.getElementById('qrCodeContainer');
            var eventId = <?php echo $eventId; ?>;
            
            // Clear loading message
            container.innerHTML = '';
            
            // Generate QR code payload
            var qrPayload = {
                type: 'attendance',
                event_id: eventId,
                event_name: '<?php echo addslashes($event['title']); ?>',
                ts: Math.floor(Date.now() / 1000)
            };
            
            var qrText = JSON.stringify(qrPayload);
            
            try {
                // Generate QR code
                var qr = new QRCode(container, {
                    text: qrText,
                    width: 200,
                    height: 200,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
                
                console.log('QR code generated successfully');
                
            } catch (error) {
                console.error('QR code generation failed:', error);
                container.innerHTML = '<div class="text-danger"><i class="fas fa-exclamation-triangle fa-2x mb-3"></i><p>QR Code generation failed</p></div>';
            }
        }
    </script>
</body>
</html>
