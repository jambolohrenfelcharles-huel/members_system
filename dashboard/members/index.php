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

// Handle delete action (admin only)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $id = $_GET['id'];
        $stmt = $db->prepare("DELETE FROM " . $members_table . " WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: index.php?deleted=1');
        exit();
    } else {
        header('Location: index.php');
        exit();
    }
}

// Get all members with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$regionFilter = isset($_GET['region']) ? $_GET['region'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_desc';

// Auto-update member status based on renewal date
$db_type = $_ENV['DB_TYPE'] ?? 'mysql';
if ($db_type === 'postgresql') {
    $db->query("UPDATE " . $members_table . " SET status = 'inactive' WHERE renewal_date < CURRENT_DATE AND status = 'active'");
} else {
    $db->query("UPDATE " . $members_table . " SET status = 'inactive' WHERE renewal_date < CURDATE() AND status = 'active'");
}

$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(name LIKE ? OR contact_number LIKE ? OR club_position LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}
if (!empty($regionFilter)) {
    $conditions[] = "region = ?";
    $params[] = $regionFilter;
}
if (!empty($statusFilter)) {
    $conditions[] = "status = ?";
    $params[] = $statusFilter;
}

$whereClause = empty($conditions) ? '' : ('WHERE ' . implode(' AND ', $conditions));

// Build ORDER BY
$orderBy = 'ORDER BY created_at DESC';
if ($sort === 'region_asc') {
    $orderBy = 'ORDER BY region ASC, name ASC';
} elseif ($sort === 'region_desc') {
    $orderBy = 'ORDER BY region DESC, name ASC';
}

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM " . $members_table . " $whereClause";
$countStmt = $db->prepare($countQuery);
$countStmt->execute($params);
$totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRecords / $limit);

// Get members
$query = "SELECT * FROM " . $members_table . " $whereClause $orderBy LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch distinct regions for filter
$regionsStmt = $db->query("SELECT DISTINCT region FROM " . $members_table . " WHERE region IS NOT NULL AND region <> '' ORDER BY region ASC");
$regions = $regionsStmt->fetchAll(PDO::FETCH_COLUMN);

$memberNotification = '';
if (isset($_GET['deleted'])) {
    $memberNotification = '<div class="member-toast-card"><div class="toast-icon"><i class="fas fa-check-circle"></i></div><div class="toast-title">Member deleted successfully!</div></div>';
} elseif (isset($_GET['added'])) {
    $memberNotification = '<div class="member-toast-card"><div class="toast-icon"><i class="fas fa-check-circle"></i></div><div class="toast-title">Member added successfully!</div></div>';
} elseif (isset($_GET['updated'])) {
    $memberNotification = '<div class="member-toast-card"><div class="toast-icon"><i class="fas fa-check-circle"></i></div><div class="toast-title">Member updated successfully!</div></div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members - SmartUnion</title>
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
                    <h1 class="h2"><i class="fas fa-users me-2"></i>Members</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Add Member
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($memberNotification)): ?>
                    <div class="member-toast-center" id="memberToastContainer"><?php echo $memberNotification; ?></div>
                <?php endif; ?>

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3" id="membersFilterForm">
                            <div class="col-md-5">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" name="search" id="membersSearchInput" placeholder="Search by name, contact, or position..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="region" onchange="document.getElementById('membersFilterForm').submit()">
                                    <option value="">All Regions</option>
                                    <?php foreach ($regions as $region): ?>
                                        <option value="<?php echo htmlspecialchars($region); ?>" <?php echo ($regionFilter === $region) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($region); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="status" onchange="document.getElementById('membersFilterForm').submit()">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo ($statusFilter === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($statusFilter === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="sort" onchange="document.getElementById('membersFilterForm').submit()">
                                    <option value="created_desc" <?php echo $sort==='created_desc'?'selected':''; ?>>Newest</option>
                                    <option value="region_asc" <?php echo $sort==='region_asc'?'selected':''; ?>>Region A→Z</option>
                                    <option value="region_desc" <?php echo $sort==='region_desc'?'selected':''; ?>>Region Z→A</option>
                                </select>
                            </div>
                            
                        </form>
                    </div>
                </div>

                <!-- Members Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Members List
                            <span class="badge bg-primary ms-2"><?php echo $totalRecords; ?> total</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($members)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No members found</h5>
                                <p class="text-muted">Start by adding your first member.</p>
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <a href="add.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Add Member
                                </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Position</th>
                                            <th>Contact</th>
                                            <th>Region</th>
                                            <th>Status</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($members as $member): ?>
                                            <tr>
                                                <td><?php echo $member['id']; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                            <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($member['name']); ?></div>
                                                            <small class="text-muted"><?php echo htmlspecialchars($member['club_affiliation']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <i class="fas fa-envelope me-1 text-muted"></i>
                                                    <small><?php echo htmlspecialchars($member['email'] ?? 'Not provided'); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($member['club_position']); ?></span>
                                                </td>
                                                <td>
                                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($member['contact_number']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($member['region']); ?></td>
                                                <td>
                                                    <?php 
                                                    $status = $member['status'] ?? 'active';
                                                    $renewalDate = $member['renewal_date'] ?? null;
                                                    $isExpired = $renewalDate && strtotime($renewalDate) < time();
                                                    ?>
                                                    <span class="badge <?php echo ($status === 'active' && !$isExpired) ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo ($status === 'active' && !$isExpired) ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                    <?php if ($renewalDate): ?>
                                                        <br><small class="text-muted">Renewal: <?php echo date('M d, Y', strtotime($renewalDate)); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="view.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-primary" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                                        <a href="edit.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="?action=delete&id=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this member?')">
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
                                <nav aria-label="Members pagination">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&region=<?php echo urlencode($regionFilter); ?>&status=<?php echo urlencode($statusFilter); ?>&sort=<?php echo urlencode($sort); ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&region=<?php echo urlencode($regionFilter); ?>&status=<?php echo urlencode($statusFilter); ?>&sort=<?php echo urlencode($sort); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&region=<?php echo urlencode($regionFilter); ?>&status=<?php echo urlencode($statusFilter); ?>&sort=<?php echo urlencode($sort); ?>">Next</a>
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
    <style>
        .avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 14px;
        }
        .member-toast-center {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1080;
  pointer-events: none;
  animation: toastFadeIn 0.7s cubic-bezier(.23,1.01,.32,1);
}
.member-toast-card {
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
        (function(){
            var form = document.getElementById('membersFilterForm');
            var input = document.getElementById('membersSearchInput');
            if (!form || !input) return;
            var timer = null;
            input.addEventListener('input', function(){
                clearTimeout(timer);
                timer = setTimeout(function(){ form.submit(); }, 400);
            });
        })();
        document.addEventListener('DOMContentLoaded', function() {
  var toast = document.getElementById('memberToastContainer');
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
