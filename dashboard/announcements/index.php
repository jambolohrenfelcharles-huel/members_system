<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $id = $_GET['id'];
        $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: index.php?deleted=1');
        exit();
    } else {
        header('Location: index.php');
        exit();
    }
}

// Get all announcements with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_desc';

$whereClause = '';
$params = [];

if (!empty($search)) {
    $whereClause = "WHERE title LIKE ? OR content LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm];
}

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM announcements $whereClause";
$countStmt = $db->prepare($countQuery);
$countStmt->execute($params);
$totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRecords / $limit);

// Get announcements
$orderBy = 'ORDER BY created_at DESC';
if ($sort === 'created_asc') {
    $orderBy = 'ORDER BY created_at ASC';
} elseif ($sort === 'title_asc') {
    $orderBy = 'ORDER BY title ASC';
} elseif ($sort === 'title_desc') {
    $orderBy = 'ORDER BY title DESC';
}
$query = "SELECT * FROM announcements $whereClause $orderBy LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($query);
$stmt->execute($params);
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - SmartUnion</title>
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
                    <h1 class="h2"><i class="fas fa-bullhorn me-2"></i>Announcements</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Add Announcement
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>Announcement deleted successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['added'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>Announcement added successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['updated'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>Announcement updated successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
    <?php
    $announcementNotification = '';
    if (isset($_GET['deleted'])) {
            $announcementNotification = '<div class="announcement-toast-card"><div class="toast-icon"><i class="fas fa-check-circle"></i></div><div class="toast-title">Announcement deleted successfully!</div></div>';
    } elseif (isset($_GET['added'])) {
            $announcementNotification = '<div class="announcement-toast-card"><div class="toast-icon"><i class="fas fa-check-circle"></i></div><div class="toast-title">Announcement added successfully!</div></div>';
    } elseif (isset($_GET['updated'])) {
            $announcementNotification = '<div class="announcement-toast-card"><div class="toast-icon"><i class="fas fa-check-circle"></i></div><div class="toast-title">Announcement updated successfully!</div></div>';
    }
    if ($announcementNotification) {
            echo '<div class="announcement-toast-center" id="announcementToastContainer">' . $announcementNotification . '</div>';
    }
    ?>

    <style>
    .announcement-toast-center {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1080;
        pointer-events: none;
        animation: toastFadeIn 0.7s cubic-bezier(.23,1.01,.32,1);
    }
    .announcement-toast-card {
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
        var toast = document.getElementById('announcementToastContainer');
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

                <!-- Search and Sort -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3" id="anncFilterForm">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="anncSearchInput" name="search" placeholder="Search announcements..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="sort" onchange="document.getElementById('anncFilterForm').submit()">
                                    <option value="created_desc" <?php echo $sort==='created_desc'?'selected':''; ?>>Newest</option>
                                    <option value="created_asc" <?php echo $sort==='created_asc'?'selected':''; ?>>Oldest</option>
                                    <option value="title_asc" <?php echo $sort==='title_asc'?'selected':''; ?>>Title A→Z</option>
                                    <option value="title_desc" <?php echo $sort==='title_desc'?'selected':''; ?>>Title Z→A</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Announcements List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Announcements
                            <span class="badge bg-primary ms-2"><?php echo $totalRecords; ?> total</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($announcements)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No announcements found</h5>
                                <p class="text-muted">Start by creating your first announcement.</p>
                                <a href="add.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Add Announcement
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($announcements as $announcement): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-bullhorn me-2"></i><?php echo htmlspecialchars($announcement['title']); ?>
                                                </h6>
                                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="edit.php?id=<?php echo $announcement['id']; ?>"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="?action=delete&id=<?php echo $announcement['id']; ?>" onclick="return confirm('Are you sure you want to delete this announcement?')"><i class="fas fa-trash me-2"></i>Delete</a></li>
                                                    </ul>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text">
                                                    <?php echo htmlspecialchars(substr($announcement['content'], 0, 150)) . (strlen($announcement['content']) > 150 ? '...' : ''); ?>
                                                </p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                                                    </small>
                                                    <a href="view.php?id=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-outline-primary">Read More</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="Announcements pagination">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
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
            var form = document.getElementById('anncFilterForm');
            var input = document.getElementById('anncSearchInput');
            if (!form || !input) return;
            var t = null;
            input.addEventListener('input', function(){
                clearTimeout(t);
                t = setTimeout(function(){ form.submit(); }, 400);
            });
        })();
    </script>
</body>
</html>
