<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Add AJAX delete handler (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_delete']) && isset($_POST['id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $id = (int)$_POST['id'];
        $response = ['success' => false];
        try {
            $stmt = $db->prepare("DELETE FROM attendance WHERE id = ?");
            $success = $stmt->execute([$id]);
            $response['success'] = (bool)$success;
        } catch (PDOException $e) {
            $response['error'] = $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    } else {
        http_response_code(403);
        exit();
    }
}

// Keep existing GET delete fallback if present (admin only)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $id = (int)$_GET['id'];
        $stmt = $db->prepare("DELETE FROM attendance WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: index.php');
        exit();
    } else {
        header('Location: index.php');
        exit();
    }
}

// Get all attendance records with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;


$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch all events (filtered by search if provided)
if (!empty($search)) {
    // Search in event name, place, region, organizing_club
    $eventStmt = $db->prepare("SELECT * FROM events WHERE name LIKE ? OR place LIKE ? OR region LIKE ? OR organizing_club LIKE ? ORDER BY event_date DESC");
    $searchTerm = "%$search%";
    $eventStmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $events = $eventStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $events = $db->query("SELECT * FROM events ORDER BY event_date DESC")->fetchAll(PDO::FETCH_ASSOC);
}

// For each event, fetch attendance records (filtered by search if provided)
$event_attendance = [];
foreach ($events as $event) {
    $attendWhere = "event_id = ?";
    $attendParams = [$event['id']];
    if (!empty($search)) {
        $attendWhere .= " AND (member_id LIKE ? OR full_name LIKE ?)";
        $attendParams[] = "%$search%";
        $attendParams[] = "%$search%";
    }
    $orderBy = 'ORDER BY attendance_date DESC';
    $stmt = $db->prepare("SELECT * FROM attendance WHERE $attendWhere $orderBy");
    $stmt->execute($attendParams);
    $event_attendance[$event['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Optionally, you can add pagination for events if needed (not implemented here)

// Get today's attendance count
$stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE attendance_date = CURRENT_DATE");
$todayCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Records - SmartUnion</title>
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
                    <div class="d-flex align-items-center">
                        <h1 class="h2 mb-0"><i class="fas fa-check-circle me-2"></i>Attendance Records</h1>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'member'): ?>
                        <a href="qr_scan.php" class="btn btn-success ms-3">
                            <i class="fas fa-qrcode me-1"></i>QR Code Scanner
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php /*
                        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'):
                        ?>
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Mark Attendance
                        </a>
                        <?php endif; */ ?>
                    </div>
                </div>

                <?php
$attendanceNotification = '';
if (isset($_GET['deleted'])) {
    $attendanceNotification = '<div class="attendance-toast-card"><div class="toast-icon"><i class="fas fa-check-circle"></i></div><div class="toast-title">Attendance record deleted successfully!</div></div>';
} elseif (isset($_GET['added'])) {
    $attendanceNotification = '<div class="attendance-toast-card"><div class="toast-icon"><i class="fas fa-check-circle"></i></div><div class="toast-title">Attendance marked successfully!</div></div>';
}
if ($attendanceNotification) {
    echo '<div class="attendance-toast-center" id="attendanceToastContainer">' . $attendanceNotification . '</div>';
}
?>

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3" id="attendanceFilterForm">
                            <div class="col-12">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" name="search" id="searchInput" placeholder="Search by name, ID, or event..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Redesigned Attendance by Event: Grid of Cards -->
                <div class="container-fluid">
    <div class="row g-4">
        <?php if (empty($events)): ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No events found</h5>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card mt-2">
                        <div class="card-header d-flex flex-column align-items-start">
                            <h5 class="mb-1"><i class="fas fa-calendar me-2"></i><?php echo htmlspecialchars($event['name']); ?></h5>
                            <div class="small mb-1">
                                <span class="badge bg-light text-dark me-1"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($event['place']); ?></span>
                                <span class="badge bg-light text-dark me-1"><i class="fas fa-globe me-1"></i><?php echo htmlspecialchars($event['region'] ?? 'N/A'); ?></span>
                                <span class="badge bg-light text-dark me-1"><i class="fas fa-users me-1"></i><?php echo htmlspecialchars($event['organizing_club'] ?? 'N/A'); ?></span>
                                <span class="badge <?php echo $event['status'] == 'upcoming' ? 'bg-warning' : ($event['status'] == 'ongoing' ? 'bg-info' : 'bg-success'); ?> ms-1"><?php echo ucfirst($event['status']); ?></span>
                            </div>
                            <div class="fw-bold"><i class="fas fa-clock me-1"></i><?php echo date('M d, Y', strtotime($event['event_date'])); ?></div>
                        </div>
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <button type="button" class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#attendeesModal<?php echo $event['id']; ?>">
                                <i class="fas fa-users me-1"></i> View Attendees
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Attendees Modal for this event -->
                <div class="modal fade" id="attendeesModal<?php echo $event['id']; ?>" tabindex="-1" aria-labelledby="attendeesModalLabel<?php echo $event['id']; ?>" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="attendeesModalLabel<?php echo $event['id']; ?>">Attendees for <?php echo htmlspecialchars($event['name']); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?php $records = $event_attendance[$event['id']]; ?>
            <?php if (empty($records)): ?>
                <div class="text-muted mb-2">No attendance records for this event.</div>
            <?php else: ?>
                <div class="table-responsive mb-0">
                    <table class="table table-sm table-hover align-middle mb-0 table-attendees">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Member ID</th>
                                <th>Full Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><i class="fas fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($record['member_id']); ?></span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <?php echo strtoupper(substr($record['full_name'], 0, 1)); ?>
                                            </div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($record['full_name']); ?></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<style>
/* Modal styling */
.modal-content {
    border-radius: 1.2rem;
    box-shadow: 0 8px 32px rgba(60,60,120,0.13);
    background: var(--card-bg, #fff);
    color: var(--main-text, #222);
}
.modal-header {
    background: linear-gradient(90deg, #1cc88a 0%, #4e54c8 100%);
    color: #fff;
    border-radius: 1.2rem 1.2rem 0 0;
    border-bottom: none;
}
.modal-title {
    font-weight: 700;
    letter-spacing: 0.5px;
}
.table-attendees th, .table-attendees td {
    vertical-align: middle;
    font-size: 0.98em;
    background: var(--card-bg, #fff);
    color: var(--main-text, #222);
}
.table-attendees th {
    background: #f1f3f7;
    color: #333;
    font-weight: 600;
}
.table-attendees tr {
    transition: background 0.15s;
}
.table-attendees tr:hover {
    background: #eafaf1;
}
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #1cc88a;
    color: #fff;
    font-weight: 700;
}
    </style>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('attendanceFilterForm');
    const searchInput = document.getElementById('searchInput');
    const dateFilter = document.getElementById('dateFilter');
    let debounceTimeout;

    searchInput.addEventListener('keyup', function() {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(function() {
            form.submit();
        }, 400); // 400ms debounce
    });

    dateFilter.addEventListener('change', function() {
        form.submit();
    });

    document.querySelectorAll('.btn-delete-attendance').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this attendance record?')) return;
            const id = this.getAttribute('data-id');
            const row = this.closest('tr');
            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ajax_delete=1&id=' + encodeURIComponent(id)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    row.remove();
                } else {
                    alert('Failed to delete record.');
                }
            })
            .catch(() => alert('Failed to delete record.'));
        });
    });
});
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-delete-attendance').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this attendance record?')) return;
            var id = this.dataset.id;
            var row = this.closest('tr');

            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ajax_delete=1&id=' + encodeURIComponent(id)
            })
            .then(function(res) {
                return res.json();
            })
            .then(function(data) {
                if (data && data.success) {
                    if (row) row.remove();
                } else {
                    alert('Failed to delete record.');
                }
            })
            .catch(function() {
                alert('Request failed. Please try again.');
            });
        });
    });
});
</script>
    <style>
        .avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 14px;
        }
.attendance-toast-center {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1080;
  pointer-events: none;
  animation: toastFadeIn 0.7s cubic-bezier(.23,1.01,.32,1);
}
.attendance-toast-card {
  background: rgba(255,255,255,0.97);
  border-radius: 1.2rem;
  padding: 2.1rem 2.7rem 1.5rem 2.7rem;
  box-shadow: 0 8px 32px rgba(60,60,120,0.13);
  min-width: 320px;
  max-width: 90vw;
  display: flex;
  flex-direction: column;
  align-items: center;
  pointer-events: all;
  animation: toastPopIn 0.7s cubic-bezier(.23,1.01,.32,1);
}
.toast-icon {
  font-size: 2.2rem;
  color: #1cc88a;
  filter: drop-shadow(0 0 8px #1cc88a55);
  margin-bottom: 0.7rem;
  animation: toastIconPop 1.1s cubic-bezier(.23,1.01,.32,1);
}
.toast-title {
  font-size: 1.18rem;
  font-weight: 600;
  color: #333;
  letter-spacing: 0.2px;
  text-align: center;
}
@keyframes toastPopIn {
  0% { opacity: 0; transform: translateY(40px) scale(0.95); }
  100% { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes toastFadeIn {
  0% { opacity: 0; }
  100% { opacity: 1; }
}
@keyframes toastIconPop {
  0% { transform: scale(0.7) rotate(-10deg); opacity: 0; }
  60% { transform: scale(1.15) rotate(8deg); opacity: 1; }
  100% { transform: scale(1) rotate(0); opacity: 1; }
}
    </style>
    <script>
document.addEventListener('DOMContentLoaded', function() {
  var toast = document.getElementById('attendanceToastContainer');
  if (toast) {
    setTimeout(function() {
      toast.style.transition = 'opacity 0.6s cubic-bezier(.23,1.01,.32,1)';
      toast.style.opacity = 0;
    }, 2200);
    setTimeout(function() {
      if (toast.parentElement) toast.parentElement.removeChild(toast);
    }, 2800);
  }
});
    </script>
</body>
</html>
