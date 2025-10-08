<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Auto-update event statuses based on dates
$today = date('Y-m-d');
$currentTime = date('Y-m-d H:i:s');

// Update events to 'completed' if event date is before today
$db->query("UPDATE events SET status = 'completed' WHERE DATE(event_date) < '$today' AND status != 'completed'");

// Update events to 'ongoing' if event date is today
$db->query("UPDATE events SET status = 'ongoing' WHERE DATE(event_date) = '$today' AND status != 'ongoing'");

// Update events to 'upcoming' if event date is after today
$db->query("UPDATE events SET status = 'upcoming' WHERE DATE(event_date) > '$today' AND status != 'upcoming'");

// Handle delete action (admin only)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $id = $_GET['id'];
        $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: index.php?deleted=1');
        exit();
    } else {
        header('Location: index.php');
        exit();
    }
}

// Get all events with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$region_filter = isset($_GET['region']) ? $_GET['region'] : '';

$whereClause = '';
$params = [];

if (!empty($search) || !empty($status_filter) || !empty($region_filter)) {
    $conditions = [];
    if (!empty($search)) {
        $conditions[] = "(name LIKE ? OR place LIKE ? OR description LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    if (!empty($status_filter)) {
        $conditions[] = "status = ?";
        $params[] = $status_filter;
    }
    if (!empty($region_filter)) {
        $conditions[] = "region = ?";
        $params[] = $region_filter;
    }
    $whereClause = "WHERE " . implode(" AND ", $conditions);
}

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM events $whereClause";
$countStmt = $db->prepare($countQuery);
$countStmt->execute($params);
$totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRecords / $limit);

// Get events
$query = "SELECT * FROM events $whereClause ORDER BY event_date DESC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct regions for filter dropdown
$regionsStmt = $db->query("SELECT DISTINCT region FROM events WHERE region IS NOT NULL AND region <> '' ORDER BY region ASC");
$regions = $regionsStmt->fetchAll(PDO::FETCH_COLUMN);

$eventNotification = '';
if (isset($_GET['deleted'])) {
    $eventNotification = '<div class="event-toast-card"><div class="toast-icon"><i class="fas fa-check-circle"></i></div><div class="toast-title">Event deleted successfully!</div></div>';
} elseif (isset($_GET['added'])) {
    $eventNotification = '<div class="event-toast-card"><div class="toast-icon"><i class="fas fa-check-circle"></i></div><div class="toast-title">Event added successfully!</div></div>';
} elseif (isset($_GET['updated'])) {
    $eventNotification = '<div class="event-toast-card"><div class="toast-icon"><i class="fas fa-check-circle"></i></div><div class="toast-title">Event updated successfully!</div></div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - SmartUnion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
.event-toast-center {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1080;
  pointer-events: none;
  animation: toastFadeIn 0.7s cubic-bezier(.23,1.01,.32,1);
}
.event-toast-card {
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
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-calendar me-2"></i>Events</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Add Event
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($eventNotification)): ?>
                    <div class="event-toast-center" id="eventToastContainer"><?php echo $eventNotification; ?></div>
                <?php endif; ?>

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3" id="eventsFilterForm">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" name="search" id="eventsSearchInput" placeholder="Search events..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status" onchange="document.getElementById('eventsFilterForm').submit()">
                                    <option value="">All Status</option>
                                    <option value="upcoming" <?php echo $status_filter == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                    <option value="ongoing" <?php echo $status_filter == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="region" onchange="document.getElementById('eventsFilterForm').submit()">
                                    <option value="">All Regions</option>
                                    <?php foreach ($regions as $region): ?>
                                        <option value="<?php echo htmlspecialchars($region); ?>" <?php echo ($region_filter == $region) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($region); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Events Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Events List
                            <span class="badge bg-primary ms-2"><?php echo $totalRecords; ?> total</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($events)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No events found</h5>
                                <p class="text-muted">Start by adding your first event.</p>
                                <a href="add.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Add Event
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Event</th>
                                            <th>Date & Time</th>
                                            <th>Place</th>
                                            <th>Region</th>
                                            <th>Organizing Club</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($events as $event): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($event['name']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . (strlen($event['description']) > 100 ? '...' : ''); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <div><?php echo date('M d, Y', strtotime($event['event_date'])); ?></div>
                                                        <small class="text-muted"><?php echo date('h:i A', strtotime($event['event_date'])); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($event['place']); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($event['region'] ?? 'N/A'); ?></span>
                                                </td>
                                                <td>
                                                    <i class="fas fa-users me-1"></i><?php echo htmlspecialchars($event['organizing_club'] ?? 'N/A'); ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = '';
                                                    switch ($event['status']) {
                                                        case 'upcoming':
                                                            $statusClass = 'bg-warning';
                                                            break;
                                                        case 'ongoing':
                                                            $statusClass = 'bg-info';
                                                            break;
                                                        case 'completed':
                                                            $statusClass = 'bg-success';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($event['status']); ?></span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="view.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-primary" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                                        <a href="edit.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="?action=delete&id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this event?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="Events pagination">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&region=<?php echo urlencode($region_filter); ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&region=<?php echo urlencode($region_filter); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&region=<?php echo urlencode($region_filter); ?>">Next</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function(){
            var form = document.getElementById('eventsFilterForm');
            var input = document.getElementById('eventsSearchInput');
            if (!form || !input) return;
            var t = null;
            input.addEventListener('input', function(){
                clearTimeout(t);
                t = setTimeout(function(){ form.submit(); }, 400);
            });
        })();

document.addEventListener('DOMContentLoaded', function() {
  var toast = document.getElementById('eventToastContainer');
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
