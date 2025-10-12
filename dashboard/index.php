<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();



// Get dashboard statistics
$stats = [];

// Total members - check which table exists
$db_type = $_ENV['DB_TYPE'] ?? 'mysql';
$members_table = ($db_type === 'postgresql') ? 'members' : 'membership_monitoring';
$stmt = $db->query("SELECT COUNT(*) as total FROM $members_table");
$stats['total_members'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

// Total events
$stmt = $db->query("SELECT COUNT(*) as total FROM events");
$stats['total_events'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

// Upcoming events
$stmt = $db->query("SELECT COUNT(*) as total FROM events WHERE status = 'upcoming'");
$stats['upcoming_events'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

// Today's attendance - use appropriate date function
if ($db_type === 'postgresql') {
    $stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE attendance_date = CURRENT_DATE");
} else {
$stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE attendance_date = CURRENT_DATE");
}
$stats['today_attendance'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

// Recent announcements
$stmt = $db->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
$recent_announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent events
$stmt = $db->query("SELECT * FROM events ORDER BY event_date DESC LIMIT 5");
$recent_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent News Feed
$stmt = $db->query("SELECT * FROM news_feed ORDER BY created_at DESC LIMIT 5");
$recent_news_feed = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $db->prepare("DELETE FROM news_feed WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php?deleted=1');
    exit();
}

// Fetch news feed (latest first)
$query = "SELECT * FROM news_feed ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt = $db->prepare($query);
$stmt->execute();
$recent_news_feed = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Year filter for chart (default current year, clamp to last 10 years)
$currentYear = (int)date('Y');
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
if ($selectedYear < $currentYear - 10 || $selectedYear > $currentYear) {
    $selectedYear = $currentYear;
}
$availableYears = [];
for ($y = $currentYear; $y >= $currentYear - 10; $y--) { $availableYears[] = $y; }

// Build 12-month trends for Members and Events for selected year
$monthLabels = [];
$memberTrend = [];
$eventTrend = [];
for ($m = 1; $m <= 12; $m++) {
    $label = DateTime::createFromFormat('!m', (string)$m)->format('M') . ' ' . $selectedYear;
    $monthKey = sprintf('%04d-%02d', $selectedYear, $m);
    $monthLabels[] = $label;
    // Members by created_at month - use appropriate table and date function
    if ($db_type === 'postgresql') {
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM members WHERE TO_CHAR(created_at, 'YYYY-MM') = ?");
        $stmt->execute([$monthKey]);
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM $members_table WHERE EXTRACT(YEAR FROM created_at) = EXTRACT(YEAR FROM CURRENT_DATE) AND EXTRACT(MONTH FROM created_at) = EXTRACT(MONTH FROM CURRENT_DATE)");
        $stmt->execute();
    }
    $memberTrend[] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    // Events by event_date month - use appropriate date function
    if ($db_type === 'postgresql') {
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM events WHERE TO_CHAR(event_date, 'YYYY-MM') = ?");
        $stmt->execute([$monthKey]);
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM events WHERE EXTRACT(YEAR FROM event_date) = EXTRACT(YEAR FROM CURRENT_DATE) AND EXTRACT(MONTH FROM event_date) = EXTRACT(MONTH FROM CURRENT_DATE)");
        $stmt->execute();
    }
    $eventTrend[] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SmartUnion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    

    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
              
                <div class="d-flex flex-column flex-md-row align-items-center mb-4" style="gap:12px; margin-top:18px;">
                    <a href="#" id="dashboardBtn" class="btn btn-primary btn-lg shadow-sm active" style="border:none;border-radius:24px;padding:8px 32px;font-weight:600;display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="#" id="mediaFeedBtn" class="btn btn-light btn-lg shadow-sm" style="border-radius:24px;padding:8px 32px;font-weight:600;display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-newspaper"></i> News Feed
                    </a>
               
                </div>

                <!-- Dashboard Content Wrapper -->
                <div id="dashboardContent">
                    <!-- Statistics Cards -->
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
                                                Upcoming Events
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <span class="stat-number" data-target="<?php echo $stats['upcoming_events']; ?>">0</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Moving Graph: Members vs Events (last 12 months) -->
                    <div class="card mb-4">
                        <div class="card-header py-2 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-chart-line me-2"></i>Members and Events (<?php echo $selectedYear; ?>)</h6>
                            <form method="GET" class="d-flex align-items-center" id="chartYearForm">
                                <label class="me-2 mb-0 small text-muted">Year</label>
                                <select class="form-select form-select-sm" name="year" onchange="document.getElementById('chartYearForm').submit()">
                                    <?php foreach ($availableYears as $y): ?>
                                        <option value="<?php echo $y; ?>" <?php echo ($y === $selectedYear) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                        <div class="card-body">
                            <canvas id="membersEventsChart" height="90"></canvas>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Recent Announcements -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-bullhorn me-2"></i>Recent Announcements
                                    </h6>
                                    <a href="announcements/index.php" class="btn btn-sm btn-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_announcements)): ?>
                                        <p class="text-muted">No announcements yet.</p>
                                    <?php else: ?>
                                        <?php foreach ($recent_announcements as $announcement): ?>
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="flex-shrink-0">
                                                    <i class="fas fa-bullhorn text-primary"></i>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                                                    <small class="text-muted">
                                                        <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Events -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-calendar me-2"></i>Recent Events
                                    </h6>
                                    <a href="events/index.php" class="btn btn-sm btn-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_events)): ?>
                                        <p class="text-muted">No events yet.</p>
                                    <?php else: ?>
                                        <?php foreach ($recent_events as $event): ?>
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="flex-shrink-0">
                                                    <i class="fas fa-calendar-alt text-success"></i>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($event['name']); ?></h6>
                                                    <small class="text-muted">
                                                        <?php echo date('M d, Y', strtotime($event['event_date'])); ?> - 
                                                        <?php echo ucfirst($event['status']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Media Feed Card Wrapper (hidden by default) -->
                <div id="mediaFeedCard" style="display:none;">
                    <div class="card shadow-sm mb-4" style="max-width: 1200px;">
                        <div class="card-header py-2 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-newspaper me-2"></i>
                            </h6>
                            <div class="d-flex align-items-center">
                                <!-- Sorting button removed -->
                                <a href="news_feed/add.php" class="btn btn-sm btn-primary">Add New</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (isset($_GET['deleted'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>News deleted successfully!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            <?php if (empty($recent_news_feed)): ?>
                                <p class="text-muted">No posts yet.</p>
                            <?php else: ?>
                                <div id="news-feed-list">
                                <?php foreach ($recent_news_feed as $news): ?>
                                    <div class="mb-3 border-bottom pb-2" data-created-at="<?php echo strtotime($news['created_at']); ?>">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($news['title']); ?></h6>
                                        <p class="small"><?php echo nl2br(htmlspecialchars($news['description'] ?? $news['content'] ?? '')); ?></p>
                                        <?php if ($news['media_type'] === 'image'): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($news['media_path']); ?>" 
                                        class="img-fluid rounded mb-2 d-block mx-auto w-100" 
                                        style="height:auto;max-width:100%;object-fit:cover;">
                                        <?php elseif ($news['media_type'] === 'video'): ?>
                                            <div class="ratio ratio-16x9 mb-2">
                                                <video controls 
                                                       class="w-100 h-100 rounded"
                                                       style="object-fit:cover;">
                                                    <source src="../uploads/<?php echo htmlspecialchars($news['media_path']); ?>" type="video/mp4">
                                                    Your browser does not support the video tag.
                                                </video>
                                            </div>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <?php echo date('M d, Y', strtotime($news['created_at'])); ?>
                                            </small>
                                            <div>
                                                <?php if ($news['user_id'] == $_SESSION['user_id']): ?>
                                                    <a href="news_feed/edit.php?id=<?php echo $news['id']; ?>" 
                                                       class="text-decoration-none text-primary me-3">
                                                       <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="?action=delete&id=<?php echo $news['id']; ?>" 
                                                       class="text-decoration-none text-primary" 
                                                       onclick="return confirm('Are you sure you want to delete this post?');">
                                                       <i class="fas fa-trash"></i> Delete
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <!-- Reactions UI -->
                                        <div class="mt-2 mb-1">
                                            <form class="d-inline react-form" data-news-id="<?php echo $news['id']; ?>">
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary react-btn" data-type="like">👍 Like</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger react-btn" data-type="love">❤️ Love</button>
                                                    <button type="button" class="btn btn-sm btn-outline-warning react-btn" data-type="haha">😂 Haha</button>
                                                    <button type="button" class="btn btn-sm btn-outline-info react-btn" data-type="wow">😮 Wow</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary react-btn" data-type="sad">😢 Sad</button>
                                                    <button type="button" class="btn btn-sm btn-outline-dark react-btn" data-type="angry">😡 Angry</button>
                                                </div>
                                                <span class="reaction-counts" id="reaction-counts-<?php echo $news['id']; ?>"></span>
                                            </form>
                                        </div>
                                        <!-- Comments UI -->
                                        <div class="mb-2">
                                            <form class="comment-form" data-news-id="<?php echo $news['id']; ?>">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control comment-input" name="comment" placeholder="Write a comment..." autocomplete="off" data-news-id="<?php echo $news['id']; ?>">
                                                    <button class="btn btn-primary" type="submit">Post</button>
                                                </div>
                                                <div class="autocomplete-suggestions bg-white border rounded shadow-sm position-absolute" style="z-index:1000;display:none;"></div>
                                            </form>
                                            <div class="comments-list mt-2" id="comments-list-<?php echo $news['id']; ?>"></div>
                                        </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
    </div>
    </div>


    <script>
    // Sorting button and logic removed
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // --- Comment Autocomplete, Posting, Fetching ---
    document.addEventListener('DOMContentLoaded', function() {
        // Autocomplete for comments
        document.querySelectorAll('.comment-input').forEach(function(input) {
            var timeout, lastQuery = '';
            var form = input.closest('.comment-form');
            var suggestionsBox = form.querySelector('.autocomplete-suggestions');
            input.addEventListener('input', function() {
                var val = input.value.trim();
                var newsId = input.getAttribute('data-news-id');
                if (val.length < 2) { suggestionsBox.style.display = 'none'; return; }
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    fetch('news_feed/autocomplete_comment.php?q=' + encodeURIComponent(val) + '&news_feed_id=' + newsId)
                        .then(r => r.json()).then(data => {
                            suggestionsBox.innerHTML = '';
                            if (data.length) {
                                data.forEach(function(s) {
                                    var div = document.createElement('div');
                                    div.className = 'p-1 suggestion-item';
                                    div.textContent = s;
                                    div.onclick = function() { input.value = s; suggestionsBox.style.display = 'none'; };
                                    suggestionsBox.appendChild(div);
                                });
                                suggestionsBox.style.display = 'block';
                            } else {
                                suggestionsBox.style.display = 'none';
                            }
                        });
                }, 200);
            });
            document.addEventListener('click', function(e) {
                if (!form.contains(e.target)) suggestionsBox.style.display = 'none';
            });
        });

        // Post comment
        document.querySelectorAll('.comment-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var input = form.querySelector('.comment-input');
                var newsId = form.getAttribute('data-news-id');
                var comment = input.value.trim();
                if (!comment) return;
                fetch('news_feed/post_comment.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'news_feed_id=' + encodeURIComponent(newsId) + '&comment=' + encodeURIComponent(comment)
                }).then(r => r.json()).then(function(data) {
                    if (data.success) {
                        input.value = '';
                        loadComments(newsId);
                    }
                });
            });
        });

        // Load comments for each news post
        function loadComments(newsId) {
            fetch('news_feed/fetch_comments.php?news_feed_id=' + newsId)
                .then(r => r.json()).then(function(comments) {
                    var list = document.getElementById('comments-list-' + newsId);
                    if (!list) return;
                    list.innerHTML = '';
                    comments.forEach(function(c) {
                        if (!c.parent_id) { // Only show reply button for top-level comments
                            var div = document.createElement('div');
                            div.className = 'mb-1';
                            div.innerHTML = '<strong>' + c.username + ':</strong> ' + c.comment +
                                ' <span class="text-muted small">(' + c.created_at + ')</span>' +
                                (c.is_owner ? ' <button class="btn btn-link btn-sm text-danger p-0 delete-comment-btn" data-comment-id="' + c.id + '" data-news-id="' + newsId + '">Delete</button>' : '') +
                                ' <button class="btn btn-link btn-sm p-0 reply-btn" data-comment-id="' + c.id + '" data-news-id="' + newsId + '">Reply</button>' +
                                '<div class="reply-form-container" style="display:none;"></div>' +
                                '<div class="replies-list mt-1" id="replies-list-' + c.id + '"></div>';
                            list.appendChild(div);
                        }
                    });
                    // Attach reply button logic
                    list.querySelectorAll('.reply-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var container = btn.parentElement.querySelector('.reply-form-container');
                            if (!container) return;
                            if (container.innerHTML === '') {
                                container.innerHTML = '<form class="reply-form" data-parent-id="' + btn.getAttribute('data-comment-id') + '" data-news-id="' + btn.getAttribute('data-news-id') + '">' +
                                    '<div class="input-group input-group-sm mt-1">' +
                                    '<input type="text" class="form-control reply-input" name="reply" placeholder="Write a reply..." autocomplete="off">' +
                                    '<button class="btn btn-primary" type="submit">Reply</button>' +
                                    '</div>' +
                                    '<div class="autocomplete-suggestions bg-white border rounded shadow-sm position-absolute" style="z-index:1000;display:none;"></div>' +
                                    '</form>';
                                // Attach reply form submit
                                var replyForm = container.querySelector('.reply-form');
                                var replyInput = container.querySelector('.reply-input');
                                var suggestionsBox = container.querySelector('.autocomplete-suggestions');
                                replyInput.addEventListener('input', function() {
                                    var val = replyInput.value.trim();
                                    var parentId = replyForm.getAttribute('data-parent-id');
                                    if (val.length < 2) { suggestionsBox.style.display = 'none'; return; }
                                    fetch('news_feed/autocomplete_reply.php?q=' + encodeURIComponent(val) + '&parent_id=' + parentId)
                                        .then(r => r.json()).then(function(data) {
                                            suggestionsBox.innerHTML = '';
                                            if (data.length) {
                                                data.forEach(function(s) {
                                                    var div = document.createElement('div');
                                                    div.className = 'p-1 suggestion-item';
                                                    div.textContent = s;
                                                    div.onclick = function() { replyInput.value = s; suggestionsBox.style.display = 'none'; };
                                                    suggestionsBox.appendChild(div);
                                                });
                                                suggestionsBox.style.display = 'block';
                                            } else {
                                                suggestionsBox.style.display = 'none';
                                            }
                                        });
                                });
                                document.addEventListener('click', function(e) {
                                    if (!replyForm.contains(e.target)) suggestionsBox.style.display = 'none';
                                });
                                replyForm.addEventListener('submit', function(e) {
                                    e.preventDefault();
                                    var newsId = replyForm.getAttribute('data-news-id');
                                    var parentId = replyForm.getAttribute('data-parent-id');
                                    var reply = replyInput.value.trim();
                                    if (!reply) return;
                                    fetch('news_feed/post_reply.php', {
                                        method: 'POST',
                                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                        body: 'news_feed_id=' + encodeURIComponent(newsId) + '&parent_id=' + encodeURIComponent(parentId) + '&comment=' + encodeURIComponent(reply)
                                    }).then(r => r.json()).then(function(data) {
                                        if (data.success) {
                                            replyInput.value = '';
                                            loadReplies(parentId);
                                        }
                                    });
                                });
                            }
                            container.style.display = container.style.display === 'none' ? 'block' : 'none';
                        });
                    });
                    // Load replies for each comment
                    comments.forEach(function(c) {
                        if (!c.parent_id) {
                            loadReplies(c.id);
                        }
                    });
                    function loadReplies(parentId) {
                        fetch('news_feed/fetch_replies.php?parent_id=' + parentId)
                            .then(r => r.json()).then(function(replies) {
                                var list = document.getElementById('replies-list-' + parentId);
                                if (!list) return;
                                list.innerHTML = '';
                                replies.forEach(function(r) {
                                    var div = document.createElement('div');
                                    div.className = 'ms-4 mb-1';
                                    div.innerHTML = '<strong>' + r.username + ':</strong> ' + r.comment +
                                        ' <span class="text-muted small">(' + r.created_at + ')</span>' +
                                        (r.is_owner ? ' <button class="btn btn-link btn-sm text-danger p-0 delete-reply-btn" data-comment-id="' + r.id + '" data-parent-id="' + parentId + '">Delete</button>' : '');
            // Use event delegation for delete-comment-btn
            list.addEventListener('click', function(e) {
                if (e.target.classList.contains('delete-comment-btn')) {
                    if (!confirm('Delete this comment?')) return;
                    var commentId = e.target.getAttribute('data-comment-id');
                    var newsId = e.target.getAttribute('data-news-id');
                    fetch('news_feed/delete_comment.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'comment_id=' + encodeURIComponent(commentId)
                    }).then(r => r.json()).then(function(data) {
                        if (data.success) {
                            loadComments(newsId);
                        } else {
                            alert(data.error || 'Failed to delete comment.');
                        }
                    });
                }
            });
                        // Use event delegation for delete-reply-btn
                        list.addEventListener('click', function(e) {
                            if (e.target.classList.contains('delete-reply-btn')) {
                                if (!confirm('Delete this reply?')) return;
                                var commentId = e.target.getAttribute('data-comment-id');
                                fetch('news_feed/delete_comment.php', {
                                    method: 'POST',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    body: 'comment_id=' + encodeURIComponent(commentId)
                                }).then(r => r.json()).then(function(data) {
                                    if (data.success) {
                                        loadReplies(parentId);
                                    } else {
                                        alert(data.error || 'Failed to delete reply.');
                                    }
                                });
                            }
                        });
                                    list.appendChild(div);
                                });
                            });
                    }
                });
        }
        document.querySelectorAll('.comments-list').forEach(function(list) {
            var newsId = list.id.replace('comments-list-', '');
            loadComments(newsId);
            // Event delegation for delete buttons (comments and replies)
            list.addEventListener('click', function(e) {
                if (e.target.classList.contains('delete-comment-btn')) {
                    if (!confirm('Delete this comment?')) return;
                    var commentId = e.target.getAttribute('data-comment-id');
                    var newsId = e.target.getAttribute('data-news-id');
                    fetch('news_feed/delete_comment.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'comment_id=' + encodeURIComponent(commentId)
                    }).then(r => r.json()).then(function(data) {
                        if (data.success) {
                            loadComments(newsId);
                        } else {
                            alert(data.error || 'Failed to delete comment.');
                        }
                    });
                }
                if (e.target.classList.contains('delete-reply-btn')) {
                    if (!confirm('Delete this reply?')) return;
                    var commentId = e.target.getAttribute('data-comment-id');
                    var parentId = e.target.getAttribute('data-parent-id');
                    fetch('news_feed/delete_comment.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'comment_id=' + encodeURIComponent(commentId)
                    }).then(r => r.json()).then(function(data) {
                        if (data.success) {
                            loadReplies(parentId);
                        } else {
                            alert(data.error || 'Failed to delete reply.');
                        }
                    });
                }
            });
        });

        // --- Reactions ---
        document.querySelectorAll('.react-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var form = btn.closest('.react-form');
                var newsId = form.getAttribute('data-news-id');
                var type = btn.getAttribute('data-type');
                // If already reacted, remove; else, add
                if (btn.classList.contains('reacted')) {
                    fetch('news_feed/remove_reaction.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'news_feed_id=' + encodeURIComponent(newsId) + '&reaction_type=' + encodeURIComponent(type)
                    }).then(r => r.json()).then(function(data) {
                        if (data.success) loadReactions(newsId);
                    });
                } else {
                    fetch('news_feed/post_reaction.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'news_feed_id=' + encodeURIComponent(newsId) + '&reaction_type=' + encodeURIComponent(type)
                    }).then(r => r.json()).then(function(data) {
                        if (data.success) loadReactions(newsId);
                    });
                }
            });
        });
        function loadReactions(newsId) {
            fetch('news_feed/fetch_reactions.php?news_feed_id=' + newsId)
                .then(r => r.json()).then(function(counts) {
                    var el = document.getElementById('reaction-counts-' + newsId);
                    if (!el) return;
                    var html = '<div class="d-flex gap-1 flex-wrap">';
                    if (counts.like) html += '<span class="badge bg-primary d-flex align-items-center"><span style="font-size:1.2em;">👍</span> <span class="ms-1">' + counts.like + '</span></span>';
                    if (counts.love) html += '<span class="badge bg-danger d-flex align-items-center"><span style="font-size:1.2em;">❤️</span> <span class="ms-1">' + counts.love + '</span></span>';
                    if (counts.haha) html += '<span class="badge bg-warning text-dark d-flex align-items-center"><span style="font-size:1.2em;">😂</span> <span class="ms-1">' + counts.haha + '</span></span>';
                    if (counts.wow) html += '<span class="badge bg-info text-dark d-flex align-items-center"><span style="font-size:1.2em;">😮</span> <span class="ms-1">' + counts.wow + '</span></span>';
                    if (counts.sad) html += '<span class="badge bg-secondary d-flex align-items-center"><span style="font-size:1.2em;">😢</span> <span class="ms-1">' + counts.sad + '</span></span>';
                    if (counts.angry) html += '<span class="badge bg-dark d-flex align-items-center"><span style="font-size:1.2em;">😡</span> <span class="ms-1">' + counts.angry + '</span></span>';
                    html += '</div>';
                    el.innerHTML = html;
                });
            // Mark user's own reactions
            fetch('news_feed/fetch_user_reactions.php?news_feed_id=' + newsId)
                .then(r => r.json()).then(function(userReacts) {
                    document.querySelectorAll('.react-form[data-news-id="' + newsId + '"] .react-btn').forEach(function(btn) {
                        var type = btn.getAttribute('data-type');
                        if (userReacts.includes(type)) {
                            btn.classList.add('reacted');
                            btn.textContent = 'Remove ' + btn.textContent.replace('Remove ', '');
                        } else {
                            btn.classList.remove('reacted');
                            btn.textContent = btn.textContent.replace('Remove ', '');
                        }
                    });
                });
        }
        document.querySelectorAll('.reaction-counts').forEach(function(el) {
            var newsId = el.id.replace('reaction-counts-', '');
            loadReactions(newsId);
        });
    });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      var toastEl = document.getElementById('welcomeToast');
      if (toastEl) {
        toastEl.classList.add('show');
        setTimeout(function() {
          toastEl.classList.remove('show');
          toastEl.style.opacity = 0;
        }, 3200);
        setTimeout(function() {
          var parent = toastEl.parentElement;
          if (parent) parent.style.display = 'none';
        }, 3700);
      }
    });
    </script>
    <script>
    (function(){
        var ctx = document.getElementById('membersEventsChart');
        if (!ctx) return;
        var labels = <?php echo json_encode($monthLabels); ?>;
        var members = <?php echo json_encode($memberTrend); ?>;
        var events = <?php echo json_encode($eventTrend); ?>;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Members',
                        data: members,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.35,
                        fill: true,
                        pointRadius: 3
                    },
                    {
                        label: 'Events',
                        data: events,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.35,
                        fill: true,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                animation: {
                    duration: 900,
                    easing: 'easeInOutQuart'
                },
                interaction: { mode: 'index', intersect: false },
                stacked: false,
                plugins: {
                    legend: { display: true },
                    tooltip: { enabled: true }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    })();
    </script>
    
    <style>
    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.8; }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .system-status-icon {
        transition: all 0.3s ease;
    }
    
    .border-left-info {
        border-left: 0.25rem solid #17a2b8 !important;
    }
    
    .text-info {
        color: #17a2b8 !important;
    }
    </style>

    <script>
    function setActiveButton(selected) {
        var dashboardBtn = document.getElementById('dashboardBtn');
        var mediaFeedBtn = document.getElementById('mediaFeedBtn');
        if (selected === 'dashboard') {
            dashboardBtn.classList.add('btn-primary', 'active');
            dashboardBtn.classList.remove('btn-light');
            mediaFeedBtn.classList.add('btn-light');
            mediaFeedBtn.classList.remove('btn-primary', 'active');
        } else {
            mediaFeedBtn.classList.add('btn-primary', 'active');
            mediaFeedBtn.classList.remove('btn-light');
            dashboardBtn.classList.add('btn-light');
            dashboardBtn.classList.remove('btn-primary', 'active');
        }
    }
    document.getElementById('mediaFeedBtn').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('dashboardContent').style.display = 'none';
        document.getElementById('mediaFeedCard').style.display = 'block';
        setActiveButton('mediaFeed');
    });
    document.getElementById('dashboardBtn').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('dashboardContent').style.display = 'block';
        document.getElementById('mediaFeedCard').style.display = 'none';
        setActiveButton('dashboard');
    });
    </script>
    <script>
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
</body>
</html>
