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

// Resolve avatar path if present
$avatarDir = __DIR__ . '/../uploads/avatars';
$allowedExt = ['jpg','jpeg','png','gif','webp'];
$avatarBasename = null;
foreach ($allowedExt as $ext) {
	$candidate = $avatarDir . '/' . ($_SESSION['user_id'] ?? '0') . '.' . $ext;
	if (file_exists($candidate)) {
		$avatarBasename = ($_SESSION['user_id'] ?? '0') . '.' . $ext;
		break;
	}
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <button class="btn btn-outline-light d-lg-none me-2" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <a class="navbar-brand d-flex align-items-center" href="<?php echo $base_path; ?>index.php" style="font-weight:700; font-size:1.5rem; letter-spacing:1px;">
            <img src="<?php echo $base_path; ?>../logo.png" alt="SmartUnion Logo" style="width:64px;height:45px;object-fit:contain;z-index:2;">
            <span style="color:#fff;text-shadow:0 2px 12px #1cc88a77,0 1px 0 #4e54c8;margin-left:10px;">SmartUnion</span>
        </a>
</script>
<style>
/* Revert to original Bootstrap dark navbar */
.navbar {
    background: #212529 !important;
    color: #fff !important;
    box-shadow: none !important;
    border-bottom: none;
}
.navbar .navbar-brand,
.navbar .nav-link,
.navbar .dropdown-toggle,
.navbar .form-check-label {
    color: #fff !important;
    text-shadow: none;
}
.navbar .nav-link.active,
.navbar .nav-link:focus,
.navbar .nav-link:hover {
    color: #ffe066 !important;
    background: rgba(255,255,255,0.07) !important;
}
.navbar .dropdown-menu {
    background: #fff;
    color: #222;
    border-radius: 1rem;
    box-shadow: 0 4px 24px #667eea22;
}
.navbar .dropdown-item:hover {
    background: #f1f3f7;
    color: #667eea;
}

</style>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
                <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ms-auto align-items-center">
                                <li class="nav-item me-3">
                                    <div class="form-check form-switch mb-0" style="user-select:none;">
                                        <input class="form-check-input" type="checkbox" id="darkModeSwitch" style="cursor:pointer;" title="Toggle dark mode">
                                        <label class="form-check-label text-light" for="darkModeSwitch" id="darkModeLabel" style="cursor:pointer;">
                                            <i class="fas fa-moon"></i>
                                        </label>
                                    </div>
                                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <?php if ($avatarBasename): ?>
                            <img src="<?php echo $base_path; ?>uploads/avatars/<?php echo htmlspecialchars($avatarBasename); ?>?v=<?php echo time(); ?>" alt="Avatar" class="rounded-circle me-2" style="width: 28px; height: 28px; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user me-2"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-lg-end">
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<script>
// Dark mode toggle logic for dashboard
document.addEventListener('DOMContentLoaded', function() {
    const darkModeSwitch = document.getElementById('darkModeSwitch');
    const darkModeLabel = document.getElementById('darkModeLabel');
    function setDarkMode(on) {
        if (on) {
            document.body.classList.add('dark-mode');
            darkModeSwitch.checked = true;
            if (darkModeLabel) { darkModeLabel.innerHTML = '<i class="fas fa-sun"></i>'; }
        } else {
            document.body.classList.remove('dark-mode');
            darkModeSwitch.checked = false;
            if (darkModeLabel) { darkModeLabel.innerHTML = '<i class="fas fa-moon"></i>'; }
        }
    }
    let dark = localStorage.getItem('dashboardDarkMode') === 'on';
    setDarkMode(dark);
    if (darkModeSwitch) {
        darkModeSwitch.addEventListener('change', function() {
            dark = darkModeSwitch.checked;
            setDarkMode(dark);
            localStorage.setItem('dashboardDarkMode', dark ? 'on' : 'off');
        });
    }
});
</script>
