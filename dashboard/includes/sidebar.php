<?php
// Determine base path relative to dashboard root regardless of current depth
$scriptDir = dirname($_SERVER['PHP_SELF']);
$base_path = '';
if (($pos = strpos($scriptDir, '/dashboard')) !== false) {
	$after = substr($scriptDir, $pos + strlen('/dashboard'));
	$afterTrim = trim($after, '/');
	$depth = $afterTrim === '' ? 0 : (substr_count($afterTrim, '/') + 1);
	$base_path = str_repeat('../', $depth);
} else {
	$base_path = '';
}

// Compute active section based on current path
$path = $_SERVER['PHP_SELF'];
$isDashboard = basename($path) === 'index.php' && strpos($path, '/dashboard/') !== false && !preg_match('#/dashboard/.+/#', $path);
$isMembers = strpos($path, '/dashboard/members/') !== false;
$isEvents = strpos($path, '/dashboard/events/') !== false;
$isAttendance = strpos($path, '/dashboard/attendance/') !== false;
$isAnnouncements = strpos($path, '/dashboard/announcements/') !== false;
$isReports = strpos($path, '/dashboard/reports/') !== false;
$isSystemStatus = strpos($path, '/dashboard/system_status.php') !== false;
$isAdmin = strpos($path, '/dashboard/admin/') !== false;
?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse pt-0">
    <div class="px-3 pt-2 pb-2 mb-2">
        <div>
           
            <div id="sidebar-local-datetime" class="fw-bold" style="font-size:0.85em; white-space:nowrap; margin-top:1.5rem;"></div>
        </div>
    </div>
    <div class="position-sticky pt-0">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $isDashboard ? 'active' : ''; ?>" href="<?php echo $base_path; ?>index.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
           <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $isMembers ? 'active' : ''; ?>" href="<?php echo $base_path; ?>members/index.php">
                    <i class="fas fa-users me-2"></i>Members
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $isEvents ? 'active' : ''; ?>" href="<?php echo $base_path; ?>events/index.php">
                    <i class="fas fa-calendar me-2"></i>Events
                </a>
            </li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $isAttendance ? 'active' : ''; ?>" href="<?php echo $base_path; ?>attendance/index.php">
                    <i class="fas fa-check-circle me-2"></i>Attendance Records
                </a>
            </li>
            <?php endif; ?>
            <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($path, '/attendance/qr_scan.php') !== false) ? 'active' : ''; ?>" href="<?php echo $base_path; ?>attendance/qr_scan.php">
                    <i class="fas fa-qrcode me-2"></i>Scan
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $isAnnouncements ? 'active' : ''; ?>" href="<?php echo $base_path; ?>announcements/index.php">
                    <i class="fas fa-bullhorn me-2"></i>Announcements
                </a>
            </li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $isReports ? 'active' : ''; ?>" href="<?php echo $base_path; ?>reports/index.php">
                    <i class="fas fa-chart-bar me-2"></i>Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $isSystemStatus ? 'active' : ''; ?>" href="<?php echo $base_path; ?>system_status.php">
                    <i class="fas fa-heartbeat me-2"></i>System Status
                </a>
            </li>
            <?php endif; ?>
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $isAdmin ? 'active' : ''; ?>" href="<?php echo $base_path; ?>admin/index.php">
                    <i class="fas fa-cog me-2"></i>System Console
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<script>
// Sidebar clock with running seconds
(function(){
    function pad(n){ return n<10 ? '0'+n : n; }
    function getAmPm(h){ return h >= 12 ? 'PM' : 'AM'; }
    function updateSidebarClock(){
        var el = document.getElementById('sidebar-local-datetime');
        if(!el) return;
        var now = new Date();
        var dateStr = now.toLocaleDateString(navigator.language || 'en-US', { dateStyle: 'long' });
        var hour = now.getHours();
        var ampm = getAmPm(hour);
        var hour12 = hour % 12;
        if (hour12 === 0) hour12 = 12;
        var timeStr = pad(hour12) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds()) + ' ' + ampm;
        el.innerHTML = '<span style="display:inline-flex;align-items:center;gap:5px;"><i class="far fa-clock" style="font-size:1em;vertical-align:middle;"></i><span style="vertical-align:middle;">' + dateStr + ' ' + timeStr + '</span></span>';
    }
    updateSidebarClock();
    setInterval(updateSidebarClock, 1000);
})();
</script>
