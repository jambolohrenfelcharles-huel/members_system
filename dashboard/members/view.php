<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$members_table = $database->getMembersTable();

$id = $_GET['id'] ?? 0;
$stmt = $db->prepare("SELECT * FROM " . $members_table . " WHERE id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Member - SmartUnion</title>
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
                    <h1 class="h2"><i class="fas fa-user me-2"></i>View Member</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i>Back to Members
                        </a>
                        
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Member Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Full Name</label>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($member['name']); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Email Address</label>
                                            <p class="form-control-plaintext">
                                                <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($member['email'] ?? 'Not provided'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Club Position</label>
                                            <p class="form-control-plaintext">
                                                <span class="badge bg-info"><?php echo htmlspecialchars($member['club_position']); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Home Address</label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($member['home_address']); ?></p>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Contact Number</label>
                                            <p class="form-control-plaintext">
                                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($member['contact_number']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Region</label>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($member['region']); ?></p>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                    <label class="form-label fw-bold">Club Affiliation</label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($member['club_affiliation'] ?: 'Not specified'); ?></p>
                                </div>
                                </div>

                                <h6 class="mt-4 mb-3"><i class="fas fa-id-card me-2"></i>Government IDs</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">PhilHealth Number</label>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($member['philhealth_number'] ?: 'Not provided'); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Pag-IBIG Number</label>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($member['pagibig_number'] ?: 'Not provided'); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">TIN Number</label>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($member['tin_number'] ?: 'Not provided'); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="mt-4 mb-3"><i class="fas fa-user me-2"></i>Personal Information</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Birthdate</label>
                                            <p class="form-control-plaintext"><?php echo date('M d, Y', strtotime($member['birthdate'])); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Height</label>
                                            <p class="form-control-plaintext"><?php echo $member['height'] ? $member['height'] . ' cm' : 'Not provided'; ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Weight</label>
                                            <p class="form-control-plaintext"><?php echo $member['weight'] ? $member['weight'] . ' kg' : 'Not provided'; ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Blood Type</label>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($member['blood_type'] ?: 'Not provided'); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Religion</label>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($member['religion'] ?: 'Not provided'); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="mt-4 mb-3"><i class="fas fa-phone me-2"></i>Emergency Contact</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Emergency Contact Person</label>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($member['emergency_contact_person']); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Emergency Contact Number</label>
                                            <p class="form-control-plaintext">
                                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($member['emergency_contact_number']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-image me-2"></i>Profile Photo</h5>
                            </div>
                            <div class="card-body text-center">
                                <?php if (!empty($member['image_path'])): ?>
                                    <?php 
                                    // Fix image path - make it relative to web root
                                    $image_url = '/uploads/' . $member['image_path'];
                                    ?>
                                    <img src="<?php echo htmlspecialchars($image_url); ?>" class="img-fluid rounded" style="max-height: 320px; object-fit: cover;" alt="Profile Photo">
                                <?php else: ?>
                                    <div class="bg-light p-4 rounded">
                                        <i class="fas fa-user fa-3x text-muted"></i>
                                        <p class="mt-2 text-muted">No photo uploaded</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Member Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Member ID</label>
                                    <p class="form-control-plaintext">#<?php echo $member['id']; ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Date Joined</label>
                                    <p class="form-control-plaintext"><?php echo date('M d, Y', strtotime($member['created_at'])); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Age</label>
                                    <p class="form-control-plaintext"><?php echo date_diff(date_create($member['birthdate']), date_create('today'))->y; ?> years old</p>
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
        function generateQR() {
            // This would integrate with a QR code generation library
            alert('QR Code generation would be implemented here with a library like qrcode.js');
        }
    </script>
</body>
</html>
