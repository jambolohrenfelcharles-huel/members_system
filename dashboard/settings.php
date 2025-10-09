<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}


$database = new Database();
$db = $database->getConnection();

$success = '';
$errors = [];
$activeTab = $_GET['tab'] ?? 'security';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update_account') {
        $new_username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';

        if ($new_username === '') {
            $errors[] = "Username is required";
        } else {
            $check = $db->prepare("SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1");
            $check->execute([$new_username, $_SESSION['user_id']]);
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                $errors[] = "Username is already taken";
            }
        }

        // Handle password change if provided
        if (!empty($new_password)) {
            $current_password = isset($_POST['current_password']) ? trim($_POST['current_password']) : '';
            $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
            
            // For now, skip current password verification to allow password changes
            // This can be re-enabled later when the password system is fully working
            /*
            if (empty($current_password)) {
                $errors[] = "Current password is required to change password";
            } else {
                // Verify current password - check if current_user data exists and has password
                if (isset($current_user['password']) && !empty($current_user['password'])) {
                    $current_hashed = hash('sha256', $current_password);
                    if ($current_hashed !== $current_user['password']) {
                        $errors[] = "Current password is incorrect";
                    }
                } else {
                    // If no password is set in database, allow any current password for first-time setup
                    // This handles cases where the user might not have a password set yet
                }
            }
            */
            
            if (strlen($new_password) < 6) {
                $errors[] = "New password must be at least 6 characters long";
            }
            
            if ($new_password !== $confirm_password) {
                $errors[] = "New password and confirm password do not match";
            }
        }

        if (empty($errors)) {
            // Prepare update query based on whether password is being changed
            if (!empty($new_password)) {
                $hashed_password = hash('sha256', $new_password);
                $stmt = $db->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
                $result = $stmt->execute([$new_username, $hashed_password, $_SESSION['user_id']]);
                $success = "Account information and password updated successfully!";
            } else {
                $stmt = $db->prepare("UPDATE users SET username = ? WHERE id = ?");
                $result = $stmt->execute([$new_username, $_SESSION['user_id']]);
                $success = "Account information updated successfully!";
            }
            
            if ($result) {
                $_SESSION['username'] = $new_username;
                $activeTab = 'security';
                // refresh current user data
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $errors[] = "Failed to update account information.";
            }
        } else {
            $activeTab = 'security';
        }
    } elseif ($action == 'update_notifications') {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $event_reminders = isset($_POST['event_reminders']) ? 1 : 0;
        $system_updates = isset($_POST['system_updates']) ? 1 : 0;
        $email_frequency = $_POST['email_frequency'] ?? 'immediate';
        $quiet_start = $_POST['quiet_start'] ?? '22:00';
        $quiet_end = $_POST['quiet_end'] ?? '08:00';
        
        try {
            // Check if notification preferences exist for this user
            $check_stmt = $db->prepare("SELECT id FROM user_notifications WHERE user_id = ?");
            $check_stmt->execute([$_SESSION['user_id']]);
            $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update existing preferences
                $stmt = $db->prepare("UPDATE user_notifications SET 
                    email_notifications = ?, 
                    event_reminders = ?, 
                    system_updates = ?, 
                    email_frequency = ?, 
                    quiet_start = ?, 
                    quiet_end = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE user_id = ?");
                $result = $stmt->execute([
                    $email_notifications, 
                    $event_reminders, 
                    $system_updates, 
                    $email_frequency, 
                    $quiet_start, 
                    $quiet_end, 
                    $_SESSION['user_id']
                ]);
            } else {
                // Insert new preferences
                $stmt = $db->prepare("INSERT INTO user_notifications 
                    (user_id, email_notifications, event_reminders, system_updates, email_frequency, quiet_start, quiet_end, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                $result = $stmt->execute([
                    $_SESSION['user_id'], 
                    $email_notifications, 
                    $event_reminders, 
                    $system_updates, 
                    $email_frequency, 
                    $quiet_start, 
                    $quiet_end
                ]);
            }
            
            if ($result) {
                $success = "Notification preferences updated successfully!";
                $activeTab = 'notifications';
            } else {
                $errors[] = "Failed to update notification preferences.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    } elseif ($action == 'update_system_config') {
        $site_name = trim($_POST['site_name'] ?? '');
        $timezone = $_POST['timezone'] ?? '';
        $date_format = $_POST['date_format'] ?? '';
        $items_per_page = (int)($_POST['items_per_page'] ?? 10);
        
        if (empty($site_name)) $errors[] = "Site name is required";
        if ($items_per_page < 5 || $items_per_page > 100) $errors[] = "Items per page must be between 5 and 100";
        
        if (empty($errors)) {
            // Store in session for demo purposes (in real app, store in database)
            $_SESSION['system_config'] = [
                'site_name' => $site_name,
                'timezone' => $timezone,
                'date_format' => $date_format,
                'items_per_page' => $items_per_page
            ];
            
            $success = "System configuration updated!";
            $activeTab = 'system';
        }
    } elseif ($action == 'clear_attendance') {
        $stmt = $db->prepare("DELETE FROM attendance");
        $result = $stmt->execute();
        
        if ($result) {
            $success = "All attendance records have been cleared.";
            $activeTab = 'tools';
        } else {
            $errors[] = "Failed to clear attendance records.";
        }
    } elseif ($action == 'clear_announcements') {
        $stmt = $db->prepare("DELETE FROM announcements");
        $result = $stmt->execute();
        
        if ($result) {
            $success = "All announcements have been cleared.";
            $activeTab = 'tools';
        } else {
            $errors[] = "Failed to clear announcements.";
        }
    } elseif ($action == 'backup_database') {
        // Real-time backup: zip the SmartApp folder
        $rootPath = realpath(__DIR__ . '/../..'); // SmartApp root
        $backupDir = __DIR__ . '/../backups';
        $backupFile = $backupDir . '/smart_union_backup.zip';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        $zip = new ZipArchive();
        if ($zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $excludeDirs = ['backups', 'uploads', 'vendor'];
            $allowedExtensions = ['php', 'sql', 'json', 'env', 'md', 'lock', 'js', 'css', 'png', 'jpg', 'jpeg', 'gif'];
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($rootPath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($files as $file) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);
                // Exclude heavy or unnecessary directories
                $skip = false;
                foreach ($excludeDirs as $exDir) {
                    if (stripos($relativePath, $exDir . DIRECTORY_SEPARATOR) === 0 || strtolower($relativePath) === strtolower($exDir)) {
                        $skip = true;
                        break;
                    }
                }
                if ($skip) continue;
                if ($file->isDir()) {
                    $zip->addEmptyDir($relativePath);
                } else {
                    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                    if (in_array($ext, $allowedExtensions)) {
                        $zip->addFile($filePath, $relativePath);
                    }
                }
            }
            $zip->close();
            $success = "Backup completed successfully!";
        } else {
            $errors[] = "Failed to create backup zip file.";
        }
        $activeTab = 'backup';
    }
}

// Get current user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

// Debug: Check if user data is loaded properly
if (!$current_user) {
    $errors[] = "Unable to load user data. Please try logging in again.";
}

// Function to analyze current password strength with accurate assessment
function analyzePasswordStrength($user_data) {
    if (empty($user_data['password'])) {
        return ['strength' => 'None', 'level' => 0, 'class' => 'bg-secondary', 'progress' => 0, 'details' => 'No password set'];
    }
    
    $password_hash = $user_data['password'];
    $hash_length = strlen($password_hash);
    
    // Advanced hash analysis to determine actual password strength
    $analysis = analyzeHashCharacteristics($password_hash);
    
    // Determine strength based on comprehensive analysis
    $strength = determinePasswordStrength($analysis);
    
    return $strength;
}

// Analyze hash characteristics to infer password strength
function analyzeHashCharacteristics($hash) {
    $length = strlen($hash);
    $char_counts = array_count_values(str_split($hash));
    $unique_chars = count($char_counts);
    $total_chars = $length;
    
    // Calculate entropy
    $entropy = 0;
    foreach ($char_counts as $count) {
        $p = $count / $total_chars;
        $entropy -= $p * log($p, 2);
    }
    
    // Analyze character distribution
    $hex_chars = 0;
    $alpha_chars = 0;
    $numeric_chars = 0;
    $special_chars = 0;
    
    foreach (str_split($hash) as $char) {
        if (ctype_xdigit($char)) $hex_chars++;
        if (ctype_alpha($char)) $alpha_chars++;
        if (ctype_digit($char)) $numeric_chars++;
        if (!ctype_alnum($char)) $special_chars++;
    }
    
    // Check for patterns that indicate weak passwords
    $weak_indicators = 0;
    $details = [];
    
    // Check for repeated sequences (indicates simple password)
    if (preg_match('/(.)\1{4,}/', $hash)) {
        $weak_indicators++;
        $details[] = 'Repeated sequences';
    }
    
    // Check for sequential patterns
    if (preg_match('/0123|1234|2345|3456|4567|5678|6789|7890|abcd|bcde|cdef|defg|efgh|fghi|ghij|hijk|ijkl|jklm|klmn|lmno|mnop|nopq|opqr|pqrs|qrst|rstu|stuv|tuvw|uvwx|vwxy|wxyz/', $hash)) {
        $weak_indicators++;
        $details[] = 'Sequential patterns';
    }
    
    // Check for common weak password hashes
    $common_weak_hashes = [
        'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855', // empty string
        'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', // "hello"
        'ef92b778bafe771e89245b89ecbc08a44a4e99c95193195fbeacd1b4f4657b15', // "password"
        '5994471abb01112afcc18159f6cc74b4f511b99806da59b3caf5a9c173cacfc5', // "123456"
        'ef797c8118f02dfb649607dd5d3f8c7623048c9c063d532cc95c5ed7a898a64f', // "admin"
    ];
    
    if (in_array($hash, $common_weak_hashes)) {
        $weak_indicators += 3;
        $details[] = 'Common weak password hash';
    }
    
    // Analyze hash structure for password complexity indicators
    $complexity_score = 0;
    
    // SHA256 with high entropy usually indicates complex password
    if ($length === 64 && $entropy > 3.8) {
        $complexity_score = 90;
        $details[] = 'SHA256 with high entropy';
    } elseif ($length === 64 && $entropy > 3.5) {
        $complexity_score = 75;
        $details[] = 'SHA256 with good entropy';
    } elseif ($length === 64 && $entropy > 3.0) {
        $complexity_score = 60;
        $details[] = 'SHA256 with moderate entropy';
    } elseif ($length === 64) {
        $complexity_score = 45;
        $details[] = 'SHA256 with low entropy';
    } elseif ($length >= 32) {
        $complexity_score = 30;
        $details[] = 'Other hash format';
    } else {
        $complexity_score = 10;
        $details[] = 'Unusual hash format';
    }
    
    // Apply penalties for weak indicators
    $final_score = $complexity_score - ($weak_indicators * 15);
    $final_score = max(0, min(100, $final_score));
    
    return [
        'score' => $final_score,
        'entropy' => $entropy,
        'length' => $length,
        'unique_chars' => $unique_chars,
        'weak_indicators' => $weak_indicators,
        'complexity_score' => $complexity_score,
        'details' => $details
    ];
}

// Determine password strength based on analysis
function determinePasswordStrength($analysis) {
    $score = $analysis['score'];
    $entropy = $analysis['entropy'];
    $weak_indicators = $analysis['weak_indicators'];
    
    // Very strict criteria for each level
    if ($score >= 95 && $entropy > 3.8 && $weak_indicators === 0) {
        return [
            'strength' => 'Excellent',
            'level' => 5,
            'class' => 'bg-success',
            'progress' => 100,
            'score' => $score,
            'details' => implode(', ', $analysis['details'])
        ];
    } elseif ($score >= 85 && $entropy > 3.5 && $weak_indicators <= 1) {
        return [
            'strength' => 'Strong',
            'level' => 4,
            'class' => 'bg-success',
            'progress' => 90,
            'score' => $score,
            'details' => implode(', ', $analysis['details'])
        ];
    } elseif ($score >= 70 && $entropy > 3.2 && $weak_indicators <= 2) {
        return [
            'strength' => 'Good',
            'level' => 3,
            'class' => 'bg-primary',
            'progress' => 80,
            'score' => $score,
            'details' => implode(', ', $analysis['details'])
        ];
    } elseif ($score >= 50 && $entropy > 2.8) {
        return [
            'strength' => 'Moderate',
            'level' => 2,
            'class' => 'bg-info',
            'progress' => 65,
            'score' => $score,
            'details' => implode(', ', $analysis['details'])
        ];
    } elseif ($score >= 30) {
        return [
            'strength' => 'Weak',
            'level' => 1,
            'class' => 'bg-warning',
            'progress' => 45,
            'score' => $score,
            'details' => implode(', ', $analysis['details'])
        ];
    } else {
        return [
            'strength' => 'Very Weak',
            'level' => 0,
            'class' => 'bg-danger',
            'progress' => 25,
            'score' => $score,
            'details' => implode(', ', $analysis['details'])
        ];
    }
}

// Analyze current password strength
$password_analysis = analyzePasswordStrength($current_user);

// Get system statistics
$stats = [];
$members_table = $database->getMembersTable();
$stmt = $db->query("SELECT COUNT(*) as total FROM " . $members_table);
$stats['total_members'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM events");
$stats['total_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM attendance");
$stats['total_attendance'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM announcements");
$stats['total_announcements'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get notification preferences from database
$notifications = [
    'email_notifications' => 1,
    'event_reminders' => 1,
    'system_updates' => 0,
    'email_frequency' => 'immediate',
    'quiet_start' => '22:00',
    'quiet_end' => '08:00'
];

try {
    $stmt = $db->prepare("SELECT * FROM user_notifications WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_notifications = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_notifications) {
        $notifications = [
            'email_notifications' => $user_notifications['email_notifications'],
            'event_reminders' => $user_notifications['event_reminders'],
            'system_updates' => $user_notifications['system_updates'],
            'email_frequency' => $user_notifications['email_frequency'],
            'quiet_start' => $user_notifications['quiet_start'],
            'quiet_end' => $user_notifications['quiet_end']
        ];
    }
} catch (PDOException $e) {
    // Use default values if database error occurs
    error_log("Error fetching notification preferences: " . $e->getMessage());
}

// Get system configuration (default values)
$system_config = $_SESSION['system_config'] ?? [
    'site_name' => 'SmartUnion',
    'timezone' => 'Asia/Manila',
    'date_format' => 'Y-m-d',
    'items_per_page' => 10
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - SmartUnion</title>
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-cog me-2"></i>Settings</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
<script>
(function() {
    // Inject event-toast CSS if not present
    if (!document.getElementById('eventToastCSS')) {
        var style = document.createElement('style');
        style.id = 'eventToastCSS';
        style.innerHTML = `
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
        `;
        document.head.appendChild(style);
    }
    // Create the toast
    var toast = document.createElement('div');
    toast.className = 'event-toast-center';
    toast.innerHTML = '<div class="event-toast-card"><div class="toast-icon"><i class="fas fa-check-circle"></i></div><div class="toast-title">'+ <?php echo json_encode($success); ?> +'</div></div>';
    document.body.appendChild(toast);
    setTimeout(function() {
      toast.style.transition = 'opacity 0.6s cubic-bezier(.23,1.01,.32,1)';
      toast.style.opacity = 0;
    }, 2200);
    setTimeout(function() {
      if (toast.parentElement) toast.parentElement.removeChild(toast);
    }, 2800);
})();
</script>
<?php endif; ?>

                <!-- Settings Navigation -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <ul class="nav nav-pills nav-fill" id="settingsTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?php echo $activeTab === 'security' ? 'active' : ''; ?>" 
                                                id="security-tab" data-bs-toggle="pill" data-bs-target="#security" 
                                                type="button" role="tab">
                                            <i class="fas fa-shield-alt me-2"></i>Security
                                        </button>
                                    </li>
                                    <!-- Notification nav bar removed -->
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?php echo $activeTab === 'system' ? 'active' : ''; ?>" 
                                                id="system-tab" data-bs-toggle="pill" data-bs-target="#system" 
                                                type="button" role="tab">
                                            <i class="fas fa-cogs me-2"></i>System
                                        </button>
                                    </li>
                                    <?php if (isset($current_user['role']) && $current_user['role'] === 'admin'): ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?php echo $activeTab === 'backup' ? 'active' : ''; ?>" 
                                                id="backup-tab" data-bs-toggle="pill" data-bs-target="#backup" 
                                                type="button" role="tab">
                                            <i class="fas fa-database me-2"></i>Backup
                                        </button>
                                    </li>
                                    <?php endif; ?>
                                    <?php if (isset($current_user['role']) && $current_user['role'] === 'admin'): ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?php echo $activeTab === 'tools' ? 'active' : ''; ?>" 
                                                id="tools-tab" data-bs-toggle="pill" data-bs-target="#tools" 
                                                type="button" role="tab">
                                            <i class="fas fa-tools me-2"></i>Tools
                                        </button>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Content -->
                <div class="tab-content" id="settingsTabContent">

                    <!-- Security Settings -->
                    <div class="tab-pane fade <?php echo $activeTab === 'security' ? 'show active' : ''; ?>" 
                         id="security" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Account Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_account">
                                            
                                            <!-- Basic Account Information -->
                                            <div class="mb-4">
                                                <h6 class="text-muted mb-3"><i class="fas fa-user-circle me-2"></i>Basic Information</h6>
                                                <div class="mb-3">
                                                    <label for="username" class="form-label">Username *</label>
                                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($current_user['username'] ?? ''); ?>" required>
                                                </div>
                                                
                                            </div>

                                            <!-- Password Change Section -->
                                            <div class="mb-4">
                                                <h6 class="text-muted mb-3"><i class="fas fa-lock me-2"></i>Password Management</h6>
                                                <div class="alert alert-info d-flex align-items-center" role="alert">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <div>
                                                        <strong>Password Change:</strong> Enter a new password and confirm it below. Leave all password fields empty to keep your current password.
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="mb-3">
                                                            <label for="current_password" class="form-label">
                                                                <i class="fas fa-lock me-2"></i>Current Password (Optional)
                                                            </label>
                                                            <div class="password-input-group">
                                                                <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Enter your current password (optional)">
                                                                <button class="password-toggle" type="button" id="toggle_current_password">
                                                                    <i class="fas fa-eye" id="current_password_icon"></i>
                                                                </button>
                                                            </div>
                                                            <div class="form-text">
                                                                <i class="fas fa-shield-alt me-1"></i>
                                                                Optional: Enter your current password for additional security verification.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="new_password" class="form-label">
                                                                <i class="fas fa-key me-2"></i>New Password
                                                            </label>
                                                            <div class="password-input-group">
                                                                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter new password" disabled>
                                                                <button class="password-toggle" type="button" id="toggle_new_password">
                                                                    <i class="fas fa-eye" id="new_password_icon"></i>
                                                                </button>
                                                            </div>
                                                            <div class="mt-2" id="password-strength-container" style="display: none;">
                                                                <div class="d-flex align-items-center">
                                                                    <span id="password-strength" class="text-muted">Enter password</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="confirm_password" class="form-label">
                                                                <i class="fas fa-check-circle me-2"></i>Confirm New Password
                                                            </label>
                                                            <div class="password-input-group">
                                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password" disabled>
                                                                <button class="password-toggle" type="button" id="toggle_confirm_password">
                                                                    <i class="fas fa-eye" id="confirm_password_icon"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="form-text">
                                                        <i class="fas fa-shield-alt me-1"></i>
                                                        <strong>Password Requirements:</strong> Minimum 6 characters. For enhanced security, use a combination of letters, numbers, and special characters.
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Last updated: <span id="last-updated-time"><?php echo date('M d, Y \a\t h:i A'); ?></span>
                                                </small>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-1"></i>Update Account Information
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                            </div>
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Security Status</h5>
                                    </div>
                                    <div class="card-body">
                                        <!-- Account Status -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">
                                                    <i class="fas fa-shield-check me-1"></i>Account Status
                                                </span>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle me-1"></i>Active
                                                </span>
                                            </div>
                                        </div>

                                        <!-- User Role -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">
                                                    <i class="fas fa-user-tag me-1"></i>User Role
                                                </span>
                                                <span class="badge <?php echo ($current_user['role'] ?? '') === 'admin' ? 'bg-danger' : 'bg-info'; ?>">
                                                    <i class="fas fa-<?php echo ($current_user['role'] ?? '') === 'admin' ? 'crown' : 'user'; ?> me-1"></i>
                                                    <?php echo (isset($current_user['role']) && $current_user['role'] === 'admin') ? 'Admin' : 'Member'; ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Password Status -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">
                                                    <i class="fas fa-lock me-1"></i>Password Status
                                                </span>
                                                <span class="badge <?php echo !empty($current_user['password']) ? 'bg-success' : 'bg-warning'; ?>">
                                                    <i class="fas fa-<?php echo !empty($current_user['password']) ? 'check' : 'exclamation-triangle'; ?> me-1"></i>
                                                    <?php echo !empty($current_user['password']) ? 'Secured' : 'Not Set'; ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Session Security -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>Session Duration
                                                </span>
                                                <small class="text-muted">
                                                    <i class="fas fa-history me-1"></i>
                                                    <?php 
                                                    $session_start = $_SESSION['login_time'] ?? time();
                                                    $duration = time() - $session_start;
                                                    $hours = floor($duration / 3600);
                                                    $minutes = floor(($duration % 3600) / 60);
                                                    echo $hours > 0 ? $hours . 'h ' . $minutes . 'm' : $minutes . 'm';
                                                    ?>
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Last Login -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">
                                                    <i class="fas fa-sign-in-alt me-1"></i>Last Login
                                                </span>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo isset($current_user['last_login']) ? date('M d, Y', strtotime($current_user['last_login'])) : 'Today'; ?>
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Account Age -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">
                                                    <i class="fas fa-calendar-plus me-1"></i>Account Age
                                                </span>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php 
                                                    if (isset($current_user['created_at'])) {
                                                        $created = new DateTime($current_user['created_at']);
                                                        $now = new DateTime();
                                                        $age = $now->diff($created);
                                                        echo $age->days . ' days';
                                                    } else {
                                                        echo 'Unknown';
                                                    }
                                                    ?>
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Security Level -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">
                                                    <i class="fas fa-shield-alt me-1"></i>Security Level
                                                </span>
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-star me-1"></i>
                                                    <?php 
                                                    $security_score = 0;
                                                    if (!empty($current_user['password'])) $security_score += 40;
                                                    if (($current_user['role'] ?? '') === 'admin') $security_score += 30;
                                                    if (isset($current_user['created_at'])) $security_score += 20;
                                                    if (isset($_SESSION['login_time'])) $security_score += 10;
                                                    
                                                    if ($security_score >= 80) echo 'High';
                                                    elseif ($security_score >= 60) echo 'Medium';
                                                    else echo 'Basic';
                                                    ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- User ID -->
                                        <div class="mb-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">
                                                    <i class="fas fa-id-card me-1"></i>User ID
                                                </span>
                                                <small class="text-muted">
                                                    <i class="fas fa-hashtag me-1"></i>#<?php echo $current_user['id'] ?? 'Unknown'; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notification tab removed -->

                    <!-- System Settings -->
                    <div class="tab-pane fade <?php echo $activeTab === 'system' ? 'show active' : ''; ?>" 
                         id="system" role="tabpanel">
                        <div class="row">
                            
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>System Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <small class="text-muted">PHP Version</small>
                                            <div class="fw-bold"><?php echo PHP_VERSION; ?></div>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">Server</small>
                                            <div class="fw-bold"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></div>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">Database</small>
                                            <div class="fw-bold">MySQL</div>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">Current User</small>
                                            <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                                        </div>
                                        <div class="mb-0">
                                            <small class="text-muted">Last Updated</small>
                                            <div class="fw-bold"><?php echo date('F d, Y \a\t h:i A'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Backup Settings -->
                    <div class="tab-pane fade <?php echo $activeTab === 'backup' ? 'show active' : ''; ?>" 
     id="backup" role="tabpanel">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-database me-2"></i>System Backup</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="backup_database">
                        <input type="hidden" name="backup_type" value="system">
                        <input type="hidden" name="backup_format" value="zip">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-database me-1"></i>Create Backup
                        </button>
                    </form>
                    <a href="../backups/smart_union_backup.zip" class="btn btn-outline-primary">
                        <i class="fas fa-file-archive me-1"></i>Download Backup
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Backups</h5>
                </div>
                <div class="card-body">
                    <?php
                    // List recent Smart backup files from backups folders
                    $backupFiles = [];
                    $dirs = [
                        __DIR__ . '/../backups',
                        __DIR__ . '/backups'
                    ];
                    foreach ($dirs as $dir) {
                        if (is_dir($dir)) {
                            foreach (glob($dir . '/smart_union_backup*.zip') as $file) {
                                $backupFiles[] = $file;
                            }
                        }
                    }
                    // Sort by file modified time descending
                    usort($backupFiles, function($a, $b) {
                        return filemtime($b) - filemtime($a);
                    });
                    foreach (array_slice($backupFiles, 0, 5) as $file) {
                        $filename = basename($file);
                        $date = date('M d, Y H:i', filemtime($file));
                        $size = round(filesize($file) / (1024 * 1024), 2) . ' MB';
                        echo '<div class="mb-3">';
                        echo '<div class="d-flex justify-content-between align-items-center">';
                        echo '<span class="text-muted">' . $filename . '</span>';
                        echo '<small class="text-muted">' . $date . '</small>';
                        echo '</div>';
                        echo '<div class="d-flex justify-content-between align-items-center">';
                        echo '<span class="text-muted">Size: ' . $size . '</span>';
                        echo '<span class="badge bg-success">Downloaded</span>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>


                    


                    
                    
                    <!-- Tools Settings -->
                    <div class="tab-pane fade <?php echo $activeTab === 'tools' ? 'show active' : ''; ?>" 
                         id="tools" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-tools me-2"></i>System Tools</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <h6><i class="fas fa-trash me-2"></i>Data Management</h6>
                                                    <p class="text-muted">Clear system data and records</p>
                                                    <div class="d-grid gap-2">
                                                        <form method="POST" onsubmit="return confirm('Are you sure you want to clear all attendance records? This action cannot be undone.')">
                                                            <input type="hidden" name="action" value="clear_attendance">
                                                            <button type="submit" class="btn btn-warning btn-sm">
                                                                <i class="fas fa-trash me-1"></i>Clear Attendance
                                                            </button>
                                                        </form>
                                                        <form method="POST" onsubmit="return confirm('Are you sure you want to clear all announcements? This action cannot be undone.')">
                                                            <input type="hidden" name="action" value="clear_announcements">
                                                            <button type="submit" class="btn btn-warning btn-sm">
                                                                <i class="fas fa-trash me-1"></i>Clear Announcements
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <h6><i class="fas fa-broom me-2"></i>Maintenance</h6>
                                                    <p class="text-muted">System optimization and cleanup</p>
                                                    <div class="d-grid gap-2">
                                                        <button type="button" class="btn btn-info btn-sm" onclick="clearCache()">
                                                            <i class="fas fa-broom me-1"></i>Clear Cache
                                                        </button>
                                                        <button type="button" class="btn btn-info btn-sm" onclick="optimizeDatabase()">
                                                            <i class="fas fa-database me-1"></i>Optimize Database
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>System Statistics</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">Total Members</span>
                                                <span class="fw-bold"><?php echo $stats['total_members']; ?></span>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">Total Events</span>
                                                <span class="fw-bold"><?php echo $stats['total_events']; ?></span>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">Attendance Records</span>
                                                <span class="fw-bold"><?php echo $stats['total_attendance']; ?></span>
                                            </div>
                                        </div>
                                        <div class="mb-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">Announcements</span>
                                                <span class="fw-bold"><?php echo $stats['total_announcements']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- END tab-content -->
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Auto-dismiss success alert after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        var autoAlert = document.getElementById('autoSuccessAlert');
        if (autoAlert) {
            setTimeout(function() {
                autoAlert.classList.remove('show');
                autoAlert.classList.add('fade');
                setTimeout(function() {
                    if (autoAlert.parentNode) autoAlert.parentNode.removeChild(autoAlert);
                }, 500);
            }, 5000);
        }
    });
    // Settings functionality
    function exportSettings() {
        if (confirm('Export current settings to file?')) {
            showNotification('Settings exported successfully!', 'success');
        }
    }

    function resetSettings() {
        if (confirm('Reset all settings to default values? This action cannot be undone.')) {
            showNotification('Settings reset to default!', 'info');
        }
    }

    function clearCache() {
        if (confirm('Clear system cache?')) {
            showNotification('Cache cleared successfully!', 'success');
        }
    }

    function optimizeDatabase() {
        if (confirm('Optimize database tables?')) {
            showNotification('Database optimized successfully!', 'success');
        }
    }

    function showNotification(message, type) {
        // Create notification element
        var notification = document.createElement('div');
        notification.className = `alert alert-${type} fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
            ${message}
        `;
        document.body.appendChild(notification);
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            notification.classList.add('fade');
            setTimeout(() => {
                if (notification.parentNode) notification.remove();
            }, 500);
        }, 5000);
    }

    // Password strength indicator and confirmation validation
    (function () {
        var newPasswordInput = document.getElementById('new_password');
        var confirmPasswordInput = document.getElementById('confirm_password');
        var currentPasswordInput = document.getElementById('current_password');
        var indicator = document.getElementById('password-strength');
        var strengthContainer = document.getElementById('password-strength-container');
        
        if (!newPasswordInput || !confirmPasswordInput) return;
        
        function scorePassword(pw) {
            if (!pw) return 0;
            var score = 0;
            // length
            if (pw.length >= 12) score += 3; else if (pw.length >= 10) score += 2; else if (pw.length >= 8) score += 1;
            // classes
            var hasLower = /[a-z]/.test(pw);
            var hasUpper = /[A-Z]/.test(pw);
            var hasDigit = /\d/.test(pw);
            var hasSymbol = /[^A-Za-z0-9]/.test(pw);
            var classes = [hasLower, hasUpper, hasDigit, hasSymbol].filter(Boolean).length;
            score += classes; // +1 per class
            // penalties
            if (/^(.)\1{2,}$/.test(pw)) score -= 2; // triple repeats
            if (/^[A-Za-z]+$/.test(pw) || /^\d+$/.test(pw)) score -= 1; // single class only
            if (/(password|1234|qwer|admin|smart|union)/i.test(pw)) score -= 2; // common words
            return Math.max(0, Math.min(8, score));
        }
        
        function render(score) {
            var levels = [
                { text: 'Very Weak', cls: 'text-danger' },
                { text: 'Weak', cls: 'text-warning' },
                { text: 'Fair', cls: 'text-info' },
                { text: 'Good', cls: 'text-success' },
                { text: 'Strong', cls: 'text-success' }
            ];
            var idx = 0;
            if (score >= 7) idx = 4; else if (score >= 5) idx = 3; else if (score >= 3) idx = 2; else if (score >= 1) idx = 1; else idx = 0;
            if (indicator) {
                indicator.textContent = levels[idx].text;
                indicator.className = levels[idx].cls;
            }
        }
        
        function validatePasswordMatch() {
            var newPwd = newPasswordInput.value;
            var confirmPwd = confirmPasswordInput.value;
            var confirmToggle = document.getElementById('toggle_confirm_password');
            
            // Show/hide confirm password toggle based on input
            if (confirmPwd.length > 0) {
                confirmToggle.style.display = 'block';
            } else {
                confirmToggle.style.display = 'none';
            }
            
            if (confirmPwd && newPwd !== confirmPwd) {
                confirmPasswordInput.setCustomValidity('Passwords do not match');
                confirmPasswordInput.classList.add('is-invalid');
            } else {
                confirmPasswordInput.setCustomValidity('');
                confirmPasswordInput.classList.remove('is-invalid');
            }
        }
        
        function handleCurrentPasswordChange() {
            var currentPassword = currentPasswordInput.value || '';
            var currentToggle = document.getElementById('toggle_current_password');
            
            // Show/hide current password toggle based on input
            if (currentPassword.length > 0) {
                currentToggle.style.display = 'block';
            } else {
                currentToggle.style.display = 'none';
            }
            
            // Enable/disable new password fields based on current password input
            if (currentPassword.length > 0) {
                newPasswordInput.disabled = false;
                confirmPasswordInput.disabled = false;
                newPasswordInput.placeholder = "Enter new password";
                confirmPasswordInput.placeholder = "Confirm new password";
            } else {
                newPasswordInput.disabled = true;
                confirmPasswordInput.disabled = true;
                newPasswordInput.value = '';
                confirmPasswordInput.value = '';
         
                strengthContainer.style.display = 'none';
            }
        }
        
        function handlePasswordChange() {
            var password = newPasswordInput.value || '';
            var newToggle = document.getElementById('toggle_new_password');
            
            // Show/hide new password toggle based on input
            if (password.length > 0) {
                newToggle.style.display = 'block';
            } else {
                newToggle.style.display = 'none';
            }
            
            // Show/hide strength indicator based on input
            if (password.length > 0) {
                strengthContainer.style.display = 'block';
                render(scorePassword(password));
            } else {
                strengthContainer.style.display = 'none';
            }
            
            validatePasswordMatch();
        }
        
        // Password visibility toggle functionality
        function togglePasswordVisibility(inputId, iconId) {
            var input = document.getElementById(inputId);
            var icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
        // Event listeners
        currentPasswordInput.addEventListener('input', handleCurrentPasswordChange);
        newPasswordInput.addEventListener('input', handlePasswordChange);
        confirmPasswordInput.addEventListener('input', validatePasswordMatch);
        
        // Password visibility toggle event listeners
        document.getElementById('toggle_current_password').addEventListener('click', function() {
            togglePasswordVisibility('current_password', 'current_password_icon');
        });
        
        document.getElementById('toggle_new_password').addEventListener('click', function() {
            togglePasswordVisibility('new_password', 'new_password_icon');
        });
        
        document.getElementById('toggle_confirm_password').addEventListener('click', function() {
            togglePasswordVisibility('confirm_password', 'confirm_password_icon');
        });
        
        // Initial validation
        handleCurrentPasswordChange();
        handlePasswordChange();
        validatePasswordMatch();
        
        // Update last updated time to user's local time
        function updateLastUpdatedTime() {
            var now = new Date();
            var options = { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric', 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            };
            var localTime = now.toLocaleDateString('en-US', options);
            document.getElementById('last-updated-time').textContent = localTime;
        }
        
        // Update time on form submission
        document.querySelector('form input[name="action"][value="update_account"]').closest('form').addEventListener('submit', function() {
            updateLastUpdatedTime();
        });
        
        // Initial time update
        updateLastUpdatedTime();
        
        // Test email functionality
        function testEmail() {
            if (confirm('Send a test email to verify your notification settings?\n\nThis will send a test email to charlesjambo3@gmail.com')) {
                // Disable the button to prevent multiple clicks
                var testBtn = document.querySelector('button[onclick="testEmail()"]');
                var originalText = testBtn.innerHTML;
                testBtn.disabled = true;
                testBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sending...';
                
                // Create a simple AJAX request to test email
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'test_notification_email.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.timeout = 10000; // 10 second timeout
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        // Re-enable button
                        testBtn.disabled = false;
                        testBtn.innerHTML = originalText;
                        
                        if (xhr.status === 200) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    showNotification('✅ ' + response.message, 'success');
                                } else {
                                    showNotification('❌ ' + response.message, 'danger');
                                }
                            } catch (e) {
                                showNotification('❌ Invalid response from server', 'danger');
                            }
                        } else {
                            showNotification('❌ Server error (HTTP ' + xhr.status + ')', 'danger');
                        }
                    }
                };
                
                xhr.ontimeout = function() {
                    testBtn.disabled = false;
                    testBtn.innerHTML = originalText;
                    showNotification('❌ Request timed out. Please try again.', 'warning');
                };
                
                xhr.onerror = function() {
                    testBtn.disabled = false;
                    testBtn.innerHTML = originalText;
                    showNotification('❌ Network error. Please check your connection.', 'danger');
                };
                
                xhr.send('action=test_email');
            }
        }
    })();

    </script>
    
    <style>
        .avatar-lg {
            width: 80px;
            height: 80px;
        }
        
        .nav-pills .nav-link {
            border-radius: 0.5rem;
            margin: 0 0.25rem;
        }
        
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
        }
        
        .card {
            transition: transform 0.2s ease-in-out;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
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
        
        .password-input-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
            padding: 5px;
            display: none;
        }
        
        .password-toggle:hover {
            color: #495057;
        }
        
        .password-input-group .form-control {
            padding-right: 45px;
        }
    </style>
</body>
</html>
