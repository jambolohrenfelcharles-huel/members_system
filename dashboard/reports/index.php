<?php
session_start();
require_once '../../config/database.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total members
$stmt = $db->query("SELECT COUNT(*) as total FROM membership_monitoring");
$stats['total_members'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

// Total events
$stmt = $db->query("SELECT COUNT(*) as total FROM events");
$stats['total_events'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

// Total attendance records
$stmt = $db->query("SELECT COUNT(*) as total FROM attendance");
$stats['total_attendance'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

// Today's attendance
$stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE DATE(date) = CURDATE()");
$stats['today_attendance'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

// This week's attendance
$stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE YEARWEEK(date) = YEARWEEK(NOW())");
$stats['week_attendance'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

// This month's attendance
$stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE YEAR(date) = YEAR(NOW()) AND MONTH(date) = MONTH(NOW())");
$stats['month_attendance'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

// Members by position
$stmt = $db->query("SELECT club_position, COUNT(*) as count FROM membership_monitoring GROUP BY club_position ORDER BY count DESC");
$members_by_position = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Events by status
$stmt = $db->query("SELECT status, COUNT(*) as count FROM events GROUP BY status");
$events_by_status = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent attendance (last 7 days)
$stmt = $db->query("SELECT DATE(date) as attendance_date, COUNT(*) as count FROM attendance WHERE date >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(date) ORDER BY attendance_date DESC");
$recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top attending members
$stmt = $db->query("SELECT full_name, club_position, COUNT(*) as attendance_count FROM attendance GROUP BY full_name, club_position ORDER BY attendance_count DESC LIMIT 10");
$top_attending_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports -SmartUnion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary no-print" onclick="window.print()">
                            <i class="fas fa-print me-1"></i>Print Report
                        </button>
                    </div>
                </div>

                <!-- Key Statistics -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Members
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <span class="stat-number" data-target="<?php echo $stats['total_members']; ?>">0</span>
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
                                            Total Events
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <span class="stat-number" data-target="<?php echo $stats['total_events']; ?>">0</span>
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
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Total Attendance
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <span class="stat-number" data-target="<?php echo $stats['total_attendance']; ?>">0</span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <span class="stat-number" data-target="<?php echo $stats['today_attendance']; ?>">0</span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-users me-2"></i>Members by Position
                                </h6>
                            </div>
                            <div class="card-body">
                                <canvas id="membersByPositionChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-calendar me-2"></i>Events by Status
                                </h6>
                            </div>
                            <div class="card-body">
                                <canvas id="eventsByStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Trends -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-chart-line me-2"></i>Attendance Trends (Last 7 Days)
                                </h6>
                            </div>
                            <div class="card-body">
                                <canvas id="attendanceTrendChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-trophy me-2"></i>Top Attending Members
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($top_attending_members)): ?>
                                    <p class="text-muted">No attendance data available.</p>
                                <?php else: ?>
                                    <?php foreach ($top_attending_members as $index => $member): ?>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                                                    <?php echo $index + 1; ?>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <div class="fw-bold"><?php echo htmlspecialchars($member['full_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($member['club_position']); ?></small>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <span class="badge bg-primary"><?php echo $member['attendance_count']; ?> times</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Statistics -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-chart-pie me-2"></i>Attendance Summary
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="text-center">
                                            <div class="h4 text-primary"><?php echo $stats['today_attendance']; ?></div>
                                            <div class="text-muted">Today</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center">
                                            <div class="h4 text-success"><?php echo $stats['week_attendance']; ?></div>
                                            <div class="text-muted">This Week</div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="text-center">
                                            <div class="h4 text-info"><?php echo $stats['month_attendance']; ?></div>
                                            <div class="text-muted">This Month</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center">
                                            <div class="h4 text-warning"><?php echo $stats['total_attendance']; ?></div>
                                            <div class="text-muted">Total</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-info-circle me-2"></i>System Information
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Report Generated:</strong> <?php echo date('F d, Y \a\t h:i A'); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Database Records:</strong>
                                    <ul class="list-unstyled ms-3">
                                        <li>• Members: <?php echo $stats['total_members']; ?></li>
                                        <li>• Events: <?php echo $stats['total_events']; ?></li>
                                        <li>• Attendance Records: <?php echo $stats['total_attendance']; ?></li>
                                    </ul>
                                </div>
                                <div class="mb-0">
                                    <strong>Attendance Rate:</strong> 
                                    <?php 
                                    $attendance_rate = $stats['total_members'] > 0 ? round(($stats['total_attendance'] / $stats['total_members']) * 100, 1) : 0;
                                    echo $attendance_rate . '%';
                                    ?>
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
        // Members by Position Chart
        const membersByPositionCtx = document.getElementById('membersByPositionChart').getContext('2d');
        new Chart(membersByPositionCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($members_by_position, 'club_position')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($members_by_position, 'count')); ?>,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Events by Status Chart
        const eventsByStatusCtx = document.getElementById('eventsByStatusChart').getContext('2d');
        new Chart(eventsByStatusCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($events_by_status, 'status')); ?>,
                datasets: [{
                    label: 'Events',
                    data: <?php echo json_encode(array_column($events_by_status, 'count')); ?>,
                    backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Attendance Trend Chart
        const attendanceTrendCtx = document.getElementById('attendanceTrendChart').getContext('2d');
        new Chart(attendanceTrendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($recent_attendance, 'attendance_date')); ?>,
                datasets: [{
                    label: 'Attendance',
                    data: <?php echo json_encode(array_column($recent_attendance, 'count')); ?>,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
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
        /* Hide elements with .no-print when printing */
        @media print {
            .no-print {
                display: none !important;
            }
        }

        .avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 14px;
        }
    </style>
</body>
</html>
