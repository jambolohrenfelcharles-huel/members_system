<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$members_table = $database->getMembersTable();

// Handle user management actions
if (isset($_GET['action'])) {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($_GET['action'] == 'delete_user' && $id) {
        if ($id != $_SESSION['user_id']) { // Prevent self-deletion
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: index.php?deleted=1');
            exit();
        }
    }
    // Block user
    if ($_GET['action'] == 'block_user' && $id) {
        $stmt = $db->prepare("UPDATE users SET blocked = 1 WHERE id = ?");
        $stmt->execute([$id]);
        exit();
    }
    // Unblock user
    if ($_GET['action'] == 'unblock_user' && $id) {
        $stmt = $db->prepare("UPDATE users SET blocked = 0 WHERE id = ?");
        $stmt->execute([$id]);
        exit();
    }
}

// Get all users
$stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get comprehensive system statistics
$stats = [];
$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

$stmt = $db->query("SELECT COUNT(*) as total FROM membership_monitoring");
$stats['total_members'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

$stmt = $db->query("SELECT COUNT(*) as total FROM events");
$stats['total_events'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

$stmt = $db->query("SELECT COUNT(*) as total FROM attendance");
$stats['total_attendance'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

$stmt = $db->query("SELECT COUNT(*) as total FROM announcements");
$stats['total_announcements'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

$stmt = $db->query("SELECT COUNT(*) as total FROM membership_monitoring WHERE status = 'active' AND renewal_date >= CURDATE()");
$stats['active_members'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

$stmt = $db->query("SELECT COUNT(*) as total FROM events WHERE status = 'upcoming'");
$stats['upcoming_events'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

$stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE DATE(date) = CURDATE()");
$stats['today_attendance'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

// Get recent activity (last 7 days)
$recent_activity = [];
$stmt = $db->query("SELECT 'member' as type, name as title, created_at FROM membership_monitoring WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY created_at DESC LIMIT 5");
$recent_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT 'event' as type, name as title, created_at FROM events WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY created_at DESC LIMIT 5");
$recent_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT 'announcement' as type, title, created_at FROM announcements WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY created_at DESC LIMIT 5");
$recent_announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$recent_activity = array_merge($recent_members, $recent_events, $recent_announcements);
usort($recent_activity, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recent_activity = array_slice($recent_activity, 0, 8);

// Get monthly trends for charts
$monthly_data = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_name = date('M Y', strtotime("-$i months"));
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM membership_monitoring WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'");
    $members_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM events WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'");
    $events_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $monthly_data[] = [
        'month' => $month_name,
        'members' => $members_count,
        'events' => $events_count
    ];
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Console - SmartUnion</title>
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
                    <h1 class="h2"><i class="fas fa-cog me-2"></i>System Console</h1>
                </div>

                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>User deleted successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['cache_cleared'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-broom me-2"></i>Cache cleared successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                

                <!-- Enhanced System Statistics -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            System Users
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800 stat-number" data-target="<?php echo $stats['total_users']; ?>">
                                            0
                                        </div>
                                        <div class="text-xs text-muted">
                                            <i class="fas fa-shield-alt me-1"></i>Admin Access
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Active Members
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800 stat-number" data-target="<?php echo $stats['active_members']; ?>">
                                            0
                                        </div>
                                        <div class="text-xs text-muted">
                                            <i class="fas fa-check-circle me-1"></i>Renewed
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-friends fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Upcoming Events
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800 stat-number" data-target="<?php echo $stats['upcoming_events']; ?>">
                                            0
                                        </div>
                                        <div class="text-xs text-muted">
                                            <i class="fas fa-clock me-1"></i>Scheduled
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Today's Attendance
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800 stat-number" data-target="<?php echo $stats['today_attendance']; ?>">
                                            0
                                        </div>
                                        <div class="text-xs text-muted">
                                            <i class="fas fa-calendar-day me-1"></i><?php echo date('M d'); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                

                <!-- Analytics Dashboard -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>System Analytics (Last 12 Months)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="analyticsChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>System Overview</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Total Members</span>
                                        <span class="fw-bold"><?php echo $stats['total_members']; ?></span>
                                    </div>
                                    <div class="progress mt-1" style="height: 4px;">
                                        <div class="progress-bar bg-primary" style="width: <?php echo min(100, ($stats['total_members'] / 100) * 100); ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Total Events</span>
                                        <span class="fw-bold"><?php echo $stats['total_events']; ?></span>
                                    </div>
                                    <div class="progress mt-1" style="height: 4px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo min(100, ($stats['total_events'] / 50) * 100); ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Attendance Records</span>
                                        <span class="fw-bold"><?php echo $stats['total_attendance']; ?></span>
                                    </div>
                                    <div class="progress mt-1" style="height: 4px;">
                                        <div class="progress-bar bg-info" style="width: <?php echo min(100, ($stats['total_attendance'] / 200) * 100); ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Announcements</span>
                                        <span class="fw-bold"><?php echo $stats['total_announcements']; ?></span>
                                    </div>
                                    <div class="progress mt-1" style="height: 4px;">
                                        <div class="progress-bar bg-warning" style="width: <?php echo min(100, ($stats['total_announcements'] / 20) * 100); ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Management Table -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>All Users</h5>
                        <input type="text" id="userSearchInput" class="form-control w-auto ms-3" placeholder="Search users..." style="max-width: 240px;">
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="userTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td class="fw-bold"><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><span class="badge bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'secondary'; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                                            <td>
                                                <?php if (!empty($user['blocked']) && $user['blocked']): ?>
                                                    <span class="badge bg-danger">Blocked</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <button type="button" class="btn btn-sm btn-warning btn-block-user" data-user-id="<?php echo $user['id']; ?>" <?php echo (!empty($user['blocked']) && $user['blocked']) ? 'disabled' : ''; ?>>Block</button>
                                                    <button type="button" class="btn btn-sm btn-success btn-unblock-user" data-user-id="<?php echo $user['id']; ?>" <?php echo (empty($user['blocked']) || !$user['blocked']) ? 'disabled' : ''; ?>>Unblock</button>
                                                    <button type="button" class="btn btn-sm btn-danger delete-user" data-user-id="<?php echo $user['id']; ?>">Delete</button>
                                                <?php else: ?>
                                                    <span class="text-muted">(You)</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // User search filter
                    var searchInput = document.getElementById('userSearchInput');
                    var userTable = document.getElementById('userTable');
                    if (searchInput && userTable) {
                        searchInput.addEventListener('input', function() {
                            var filter = searchInput.value.toLowerCase();
                            var rows = userTable.querySelectorAll('tbody tr');
                            rows.forEach(function(row) {
                                var username = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                                var email = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                                if (username.includes(filter) || email.includes(filter)) {
                                    row.style.display = '';
                                } else {
                                    row.style.display = 'none';
                                }
                            });
                        });
                    }
                    // Block/Unblock AJAX
                    document.querySelectorAll('.btn-block-user, .btn-unblock-user').forEach(function(button) {
                        button.addEventListener('click', function(e) {
                            e.preventDefault();
                            const userId = this.getAttribute('data-user-id');
                            const row = this.closest('tr');
                            const statusCell = row.querySelector('td:nth-child(5)');
                            const blockBtn = row.querySelector('.btn-block-user');
                            const unblockBtn = row.querySelector('.btn-unblock-user');
                            const originalContent = this.innerHTML;
                            this.disabled = true;
                            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                            let action = this.classList.contains('btn-block-user') ? 'block_user' : 'unblock_user';
                            fetch(`?action=${action}&id=${userId}`)
                                .then(response => response.text())
                                .then(() => {
                                    if (action === 'block_user') {
                                        statusCell.innerHTML = '<span class="badge bg-danger">Blocked</span>';
                                        blockBtn.disabled = true;
                                        unblockBtn.disabled = false;
                                        blockBtn.classList.add('disabled');
                                        unblockBtn.classList.remove('disabled');
                                        blockBtn.innerHTML = 'Block';
                                        unblockBtn.innerHTML = 'Unblock';
                                        showNotification('User blocked successfully!', 'warning');
                                    } else {
                                        statusCell.innerHTML = '<span class="badge bg-success">Active</span>';
                                        blockBtn.disabled = false;
                                        unblockBtn.disabled = true;
                                        blockBtn.classList.remove('disabled');
                                        unblockBtn.classList.add('disabled');
                                        blockBtn.innerHTML = 'Block';
                                        unblockBtn.innerHTML = 'Unblock';
                                        showNotification('User unblocked successfully!', 'success');
                                    }
                                    this.disabled = false;
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    showNotification('Error updating user status', 'danger');
                                    this.disabled = false;
                                    this.innerHTML = originalContent;
                                });
                        });
                    });
                    // Delete AJAX
                    document.querySelectorAll('.delete-user').forEach(function(button) {
                        button.addEventListener('click', function(e) {
                            e.preventDefault();
                            const userId = this.getAttribute('data-user-id');
                            const row = this.closest('tr');
                            const originalContent = this.innerHTML;
                            this.disabled = true;
                            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                            fetch(`?action=delete_user&id=${userId}`)
                                .then(response => response.text())
                                .then(() => {
                                    row.style.animation = 'fadeOut 0.5s';
                                    setTimeout(() => {
                                        row.remove();
                                        showNotification('User deleted successfully!', 'success');
                                    }, 500);
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    showNotification('Error deleting user', 'danger');
                                    this.disabled = false;
                                    this.innerHTML = originalContent;
                                });
                        });
                    });
                });
                function showNotification(message, type) {
                    let alert = document.createElement('div');
                    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
                    alert.role = 'alert';
                    alert.innerHTML = `<span>${message}</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
                    document.body.appendChild(alert);
                    setTimeout(() => { alert.remove(); }, 3000);
                }
                </script>


                 
                </div>

                

                

                
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <script>
    // Analytics Chart
    (function() {
        var ctx = document.getElementById('analyticsChart');
        if (!ctx) return;
        
        var monthlyData = <?php echo json_encode($monthly_data); ?>;
        var labels = monthlyData.map(item => item.month);
        var membersData = monthlyData.map(item => item.members);
        var eventsData = monthlyData.map(item => item.events);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'New Members',
                        data: membersData,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'New Events',
                        data: eventsData,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    })();

    // Admin Tools Functions
    function exportData() {
        if (confirm('Export all system data to CSV?')) {
            // Simulate export process
            showNotification('Export started...', 'info');
            setTimeout(() => {
                showNotification('Data exported successfully!', 'success');
            }, 2000);
        }
    }

    function backupDatabase() {
        if (confirm('Create a backup of the database?')) {
            showNotification('Backup in progress...', 'info');
            setTimeout(() => {
                showNotification('Database backup completed!', 'success');
            }, 3000);
        }
    }

    function clearCache() {
        if (confirm('Clear system cache?')) {
            showNotification('Cache cleared successfully!', 'success');
        }
    }

    function showNotification(message, type) {
        // Create notification element
        var notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // Real-time updates for system metrics
    function updateSystemMetrics() {
        // Simulate real-time updates
        var elements = document.querySelectorAll('.fw-bold');
        elements.forEach(element => {
            if (element.textContent.includes('%')) {
                var currentValue = parseInt(element.textContent);
                var newValue = Math.max(0, Math.min(100, currentValue + Math.floor(Math.random() * 6) - 3));
                element.textContent = newValue + '%';
            }
        });
    }

    // Update metrics every 30 seconds
    setInterval(updateSystemMetrics, 30000);

    document.addEventListener('DOMContentLoaded', function() {
        // Existing search functionality
        var searchInput = document.getElementById('userSearchInput');
        var table = document.getElementById('userTable');
        if (!searchInput || !table) return;
        
        searchInput.addEventListener('input', function() {
            var filter = searchInput.value.toLowerCase();
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function(row) {
                var username = row.querySelector('td:nth-child(2) .fw-bold').textContent.toLowerCase();
                var role = row.querySelector('td:nth-child(3) span').textContent.toLowerCase();
                if (username.includes(filter) || role.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Delete user functionality
        document.querySelectorAll('.delete-user').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                if (!confirm('Are you sure you want to delete this user?')) {
                    return;
                }

                const userId = this.getAttribute('data-user-id');
                const row = this.closest('tr');
                const button = this;

                // Disable button and show loading state
                button.disabled = true;
                const originalContent = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

                fetch('?action=delete_user&id=' + userId)
                    .then(response => response.text())
                    .then(() => {
                        row.style.animation = 'fadeOut 0.5s';
                        setTimeout(() => {
                            row.remove();
                            showNotification('User deleted successfully!', 'success');
                        }, 500);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error deleting user', 'danger');
                        button.disabled = false;
                        button.innerHTML = originalContent;
                    });
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.stat-number').forEach(function(el) {
            let target = parseInt(el.getAttribute('data-target'), 10);
            if (isNaN(target) || target < 1) target = 0;
            let current = 0;
            const duration = 1200;
            const stepTime = target > 0 ? Math.max(Math.floor(duration / target), 20) : duration;
            function update() {
                current += Math.ceil(target / (duration / stepTime));
                if (current >= target) {
                    el.textContent = target;
                } else {
                    el.textContent = current;
                    setTimeout(update, stepTime);
                }
            }
            el.textContent = 0;
            update();
        });
    });
    </script>
    
    <style>
        .avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 14px;
        }
        
        .timeline-item {
            border-left: 2px solid #e9ecef;
            padding-left: 15px;
            position: relative;
        }
        
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -6px;
            top: 8px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #6c757d;
        }
        
        .timeline-item:last-child {
            border-left: none;
        }
        
        .card {
            transition: transform 0.2s ease-in-out;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .progress {
            border-radius: 10px;
        }
        
        .progress-bar {
            border-radius: 10px;
        }
        
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }
        
        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }
        
        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }
        
        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </style>
</body>
</html>
