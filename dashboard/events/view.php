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
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
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
    <title>View Event - SmartUnion</title>
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
                    <h1 class="h2"><i class="fas fa-calendar me-2"></i>View Event</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i>Back to Events
                        </a>
                       
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar me-2"></i><?php echo htmlspecialchars($event['name']); ?>
                            </h5>
                            <span class="badge <?php 
                                echo $event['status'] == 'upcoming' ? 'bg-warning' : 
                                    ($event['status'] == 'ongoing' ? 'bg-info' : 'bg-success'); 
                            ?>">
                                <?php echo ucfirst($event['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Event Name</label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($event['name']); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Status</label>
                                    <p class="form-control-plaintext">
                                        <span class="badge <?php 
                                            echo $event['status'] == 'upcoming' ? 'bg-warning' : 
                                                ($event['status'] == 'ongoing' ? 'bg-info' : 'bg-success'); 
                                        ?>">
                                            <?php echo ucfirst($event['status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Event Place</label>
                                    <p class="form-control-plaintext">
                                        <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($event['place']); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Event Date & Time</label>
                                    <p class="form-control-plaintext">
                                        <i class="fas fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                                        <br>
                                        <i class="fas fa-clock me-1"></i><?php echo date('h:i A', strtotime($event['event_date'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Region</label>
                                    <p class="form-control-plaintext">
                                        <i class="fas fa-globe me-1"></i>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($event['region'] ?? 'Not specified'); ?></span>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Organizing Club</label>
                                    <p class="form-control-plaintext">
                                        <i class="fas fa-users me-1"></i><?php echo htmlspecialchars($event['organizing_club'] ?? 'Not specified'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Event Description</label>
                            <div class="form-control-plaintext">
                                <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                            </div>
                        </div>

                        <div class="row">
                            <?php if ($event['status'] === 'ongoing'): ?>
                            <div class="col-lg-6">
                                <div class="card mt-2">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-qrcode me-2"></i>Attendance QR Code</h6>
                                        <button id="downloadQrBtn" class="btn btn-sm btn-outline-secondary"><i class="fas fa-download me-1"></i>Download</button>
                                    </div>
                                    <div class="card-body text-center">
                                        <div id="eventQr" class="d-inline-block bg-white p-3 rounded"></div>
                                        <p class="small text-muted mt-2">Scan to check-in to this event</p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                    
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>Created on <?php echo date('F d, Y \a\t h:i A', strtotime($event['created_at'])); ?>
                            </small>
                        

                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Attendees Modal moved outside the card to prevent blinking -->
    <div class="modal fade" id="attendeesModal" tabindex="-1" aria-labelledby="attendeesModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="attendeesModalLabel">Attendees for <?php echo htmlspecialchars($event['name']); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?php
            $attStmt = $db->prepare("SELECT member_id, full_name, club_position, date FROM attendance WHERE event_id = ? ORDER BY date DESC");
            $attStmt->execute([$event['id']]);
            $attendees = $attStmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($attendees)) {
                echo '<div class="text-muted">No attendance records for this event.</div>';
            } else {
                echo '<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Date</th><th>Member ID</th><th>Full Name</th><th>Club Position</th></tr></thead><tbody>';
                foreach ($attendees as $a) {
                    echo '<tr>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($a['date'])) . '</td>';
                    echo '<td>' . htmlspecialchars($a['member_id']) . '</td>';
                    echo '<td>' . htmlspecialchars($a['full_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($a['club_position']) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table></div>';
            }
            ?>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        (function(){
            var container = document.getElementById('eventQr');
            if (!container) return;
            var payload = {
                type: 'attendance',
                event_id: <?php echo (int)$event['id']; ?>,
                event_name: <?php echo json_encode($event['name']); ?>,
                ts: Date.now()
            };
            var text = JSON.stringify(payload);
            var qr = new QRCode(container, {
                text: text,
                width: 192,
                height: 192,
                correctLevel: QRCode.CorrectLevel.M
            });

            var btn = document.getElementById('downloadQrBtn');
            if (btn) {
                btn.addEventListener('click', function(){
                    // qrcodejs renders a <img> or <canvas>; handle both
                    var img = container.querySelector('img');
                    var canvas = container.querySelector('canvas');
                    if (img && img.src) {
                        var a = document.createElement('a');
                        a.href = img.src;
                        a.download = 'event_<?php echo (int)$event['id']; ?>_qr.png';
                        a.click();
                    } else if (canvas) {
                        var a2 = document.createElement('a');
                        a2.href = canvas.toDataURL('image/png');
                        a2.download = 'event_<?php echo (int)$event['id']; ?>_qr.png';
                        a2.click();
                    }
                });
            }
        })();
    </script>
</body>
</html>
