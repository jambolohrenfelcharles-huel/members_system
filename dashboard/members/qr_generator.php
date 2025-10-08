<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$id = $_GET['id'] ?? 0;
$stmt = $db->prepare("SELECT * FROM membership_monitoring WHERE id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    header('Location: index.php');
    exit();
}

// Simple QR code generation using a basic approach
// In a real application, you would use a library like phpqrcode or similar
function generateQRCode($data, $size = 200) {
    // This is a placeholder - in a real application, you would use a proper QR code library
    $qr_data = urlencode($data);
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . $qr_data;
    return $qr_url;
}

$qr_code_url = generateQRCode($member['qr_code']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - <?php echo htmlspecialchars($member['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-qrcode me-2"></i>QR Code Generator</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i>Back to Members
                        </a>
                        <button class="btn btn-primary" onclick="printQR()">
                            <i class="fas fa-print me-1"></i>Print QR Code
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i>QR Code</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="mb-4">
                                    <img src="<?php echo $qr_code_url; ?>" alt="QR Code" class="img-fluid" style="max-width: 300px;">
                                </div>
                                <div class="mb-3">
                                    <h6>QR Code Data:</h6>
                                    <code class="bg-light p-2 rounded"><?php echo htmlspecialchars($member['qr_code']); ?></code>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-primary" onclick="downloadQR()">
                                        <i class="fas fa-download me-1"></i>Download
                                    </button>
                                    <button class="btn btn-success" onclick="copyQRData()">
                                        <i class="fas fa-copy me-1"></i>Copy Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Member Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Name</label>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($member['name']); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Position</label>
                                            <p class="form-control-plaintext">
                                                <span class="badge bg-info"><?php echo htmlspecialchars($member['club_position']); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Member ID</label>
                                            <p class="form-control-plaintext">#<?php echo $member['id']; ?></p>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Region</label>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($member['region']); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Contact</label>
                                    <p class="form-control-plaintext">
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($member['contact_number']); ?>
                                    </p>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">QR Code</label>
                                    <p class="form-control-plaintext">
                                        <code><?php echo htmlspecialchars($member['qr_code']); ?></code>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>QR Code Usage</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>Member identification</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Event check-in</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Attendance tracking</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Access control</li>
                                </ul>
                                <div class="alert alert-info">
                                    <small>
                                        <i class="fas fa-info-circle me-1"></i>
                                        This QR code contains the member's unique identifier and can be scanned for various purposes.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function downloadQR() {
            const qrImage = document.querySelector('img[alt="QR Code"]');
            const link = document.createElement('a');
            link.download = 'qr-code-<?php echo $member['id']; ?>.png';
            link.href = qrImage.src;
            link.click();
        }

        function copyQRData() {
            const qrData = '<?php echo $member['qr_code']; ?>';
            navigator.clipboard.writeText(qrData).then(function() {
                alert('QR Code data copied to clipboard!');
            });
        }

        function printQR() {
            window.print();
        }
    </script>
    <style>
        @media print {
            .btn-toolbar, .sidebar, .navbar {
                display: none !important;
            }
            .container-fluid {
                margin: 0 !important;
                padding: 0 !important;
            }
            .card {
                border: 1px solid #000 !important;
                box-shadow: none !important;
            }
        }
    </style>
</body>
</html>
