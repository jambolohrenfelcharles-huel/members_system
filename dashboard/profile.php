<?php
session_start();
require_once '../config/database.php';
require_once '../config/email_config.php';
require_once '../config/smtp_email.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();


// Get current user info
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If member, get status and renewal from members table
$member_status = null;
$member_renewal = null;
if (isset($user['role']) && $user['role'] === 'member') {
    $members_table = ($_ENV['DB_TYPE'] ?? 'mysql') === 'postgresql' ? 'members' : 'membership_monitoring';
    $stmt = $db->prepare("SELECT status, renewal_date FROM $members_table WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($member) {
        $member_status = $member['status'];
        $member_renewal = $member['renewal_date'];
    }
}

$errors = [];
$success = '';

// Email configuration
function sendEmailNotification($to, $subject, $message, $from = null) {
    // Use configured email settings
    $config = getEmailConfig();
    $fromAddress = $from ?: $config['from_address'];
    $fromName = $config['from_name'];
    
    // Check if we're on localhost and should simulate
    $isLocalhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']);
    if ($isLocalhost && $config['simulate_on_localhost']) {
        // Simulate email sending on localhost
        if ($config['log_attempts']) {
            error_log("Email simulation: To=$to, Subject=$subject, From=$fromAddress, Result=Simulated");
        }
        return true; // Return true to simulate success
    }
    
    // Try SMTP first if configured
    if (!empty($config['smtp_host']) && $config['smtp_host'] !== 'localhost' && !empty($config['smtp_username'])) {
        $result = sendEmailViaSMTP($to, $subject, $message, $fromAddress, $fromName);
        if ($result) {
            return true;
        }
        // If SMTP fails, fall back to PHP mail()
    }
    
    // Fallback to PHP mail() function
    if (!function_exists('mail')) {
        return false;
    }
    
    // Create proper headers
    $headers = array();
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/html; charset=" . $config['charset'];
    $headers[] = "From: $fromName <$fromAddress>";
    $headers[] = "Reply-To: " . $config['reply_to'];
    $headers[] = "X-Mailer: PHP/" . phpversion();
    $headers[] = "X-Priority: " . $config['priority'];
    
    $headerString = implode("\r\n", $headers);
    
    // Try to send email
    $result = @mail($to, $subject, $message, $headerString);
    
    // Log the attempt (for debugging)
    if ($config['log_attempts']) {
        error_log("Email attempt: To=$to, Subject=$subject, From=$fromAddress, Result=" . ($result ? 'Success' : 'Failed'));
    }
    
    return $result;
}

function sendTestEmail($email) {
    $subject = "Test Email - SmartUnion System";
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Test Email</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>SmartUnion System</h1>
            </div>
            <div class='content'>
                <h2>Email Test Successful!</h2>
                <p>Hello,</p>
                <p>This is a test email from your SmartUnion membership system.</p>
                <p>If you received this email, your email notifications are working correctly.</p>
                <p>You can now receive important notifications about your account and system updates.</p>
            </div>
            <div class='footer'>
                <p>Sent on " . date('F d, Y \a\t h:i A') . "</p>
                <p>SmartUnion Membership System</p>
            </div>
        </div>
    </body>
    </html>";
    
    return sendEmailNotification($email, $subject, $message);
}

function checkEmailConfiguration() {
    $emailConfig = getEmailConfig();
    $emailStatus = getEmailStatus();
    
    $config = array();
    
    // Check if mail function exists
    $config['mail_function'] = $emailStatus['mail_function'];
    
    // Check sendmail path (common on Unix systems)
    $config['sendmail_path'] = ini_get('sendmail_path');
    
    // Check SMTP settings
    $config['smtp_host'] = $emailConfig['smtp_host'];
    $config['smtp_port'] = $emailConfig['smtp_port'];
    $config['smtp_configured'] = $emailStatus['smtp_configured'];
    $config['smtp_username'] = $emailConfig['smtp_username'];
    $config['smtp_encryption'] = $emailConfig['smtp_encryption'];
    
    // Check if running on localhost (common development issue)
    $config['is_localhost'] = $emailStatus['is_localhost'];
    
    // Check if email is properly configured
    $config['properly_configured'] = $emailStatus['configured'];
    
    // Get from address
    $config['from_address'] = $emailConfig['from_address'];
    
    // Check if SMTP is ready for actual sending
    $config['smtp_ready'] = !empty($emailConfig['smtp_host']) && 
                           $emailConfig['smtp_host'] !== 'localhost' && 
                           !empty($emailConfig['smtp_username']) && 
                           !empty($emailConfig['smtp_password']);
    
    return $config;
}

// Avatar setup and helpers
$avatarDir = __DIR__ . '/uploads/avatars';
if (!is_dir($avatarDir)) {
    @mkdir($avatarDir, 0777, true);
}
$allowedExt = ['jpg','jpeg','png','gif','webp'];
function findUserAvatarBasename($dir, $userId, $exts) {
    foreach ($exts as $ext) {
        $candidate = $dir . '/' . $userId . '.' . $ext;
        if (file_exists($candidate)) {
            return $userId . '.' . $ext;
        }
    }
    return null;
}
function detectImageExtension($tmpPath) {
    if (class_exists('finfo')) {
        $fi = new finfo(FILEINFO_MIME_TYPE);
        $mime = $fi->file($tmpPath) ?: '';
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        if (isset($map[$mime])) {
            return $map[$mime];
        }
    }
    $info = @getimagesize($tmpPath);
    if (is_array($info) && isset($info[2])) {
        switch ($info[2]) {
            case IMAGETYPE_JPEG: return 'jpg';
            case IMAGETYPE_PNG: return 'png';
            case IMAGETYPE_GIF: return 'gif';
            case IMAGETYPE_WEBP: return 'webp';
        }
    }
    return null;
}
$currentAvatarBasename = findUserAvatarBasename($avatarDir, $user['id'], $allowedExt);

if ($_POST) {
    if (isset($_POST['upload_avatar'])) {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Please select an image to upload.";
        } else {
            $fileTmp = $_FILES['avatar']['tmp_name'];
            $fileSize = (int)$_FILES['avatar']['size'];
            if (!is_uploaded_file($fileTmp)) {
                $errors[] = 'Invalid upload source.';
            } elseif ($fileSize <= 0 || $fileSize > 500 * 1024 * 1024) {
                $errors[] = 'Image must be between 1 byte and 500MB.';
            } else {
                $ext = detectImageExtension($fileTmp);
                if (!$ext) {
                    $errors[] = 'Only JPG, PNG, GIF, or WEBP images are allowed.';
                } else {
                    // Remove old avatar files with different extensions
                    foreach ($allowedExt as $e) {
                        $old = $avatarDir . '/' . $user['id'] . '.' . $e;
                        if (file_exists($old)) {
                            @unlink($old);
                        }
                    }
                    $targetPath = $avatarDir . '/' . $user['id'] . '.' . $ext;
                    if (!@move_uploaded_file($fileTmp, $targetPath)) {
                        $errors[] = 'Failed to save uploaded image.';
                    } else {
                        @chmod($targetPath, 0644);
                        $success = 'Profile picture updated successfully!';
                        $currentAvatarBasename = $user['id'] . '.' . $ext;
                    }
                }
            }
        }
    } elseif (isset($_POST['test_email'])) {
        $email = trim($_POST['email'] ?? '');
        if (empty($email)) {
            $errors[] = "Email address is required for testing";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        } else {
            // Check email configuration first
            $emailConfig = checkEmailConfiguration();
            
            if (!$emailConfig['mail_function']) {
                $errors[] = "Mail function is not available on this server.";
            } else {
                if (sendTestEmail($email)) {
                    if ($emailConfig['is_localhost']) {
                        $success = "Test email simulation completed successfully! Note: You're running on localhost, so this was simulated. In a production environment, this would send a real email to $email.";
                    } else {
                        $success = "Test email sent successfully to $email! Please check your inbox (and spam folder).";
                    }
                } else {
                    $errors[] = "Failed to send test email. This could be due to server email configuration. Please contact your system administrator.";
                }
            }
        }
    } else {
        $new_username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $new_email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        if ($new_username === '') {
            $errors[] = "Username is required";
        } else {
            // Check if username is taken by another user
            $check = $db->prepare("SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1");
            $check->execute([$new_username, $_SESSION['user_id']]);
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                $errors[] = "Username is already taken";
            }
        }

        if ($new_email === '') {
            $errors[] = "Email address is required";
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        } else {
            // Check if email is taken by another user
            $check = $db->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
            $check->execute([$new_email, $_SESSION['user_id']]);
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                $errors[] = "Email address is already taken";
            }
        }

        if (!empty($new_password)) {
            if (strlen($new_password) < 6) {
                $errors[] = "Password must be at least 6 characters long";
            }
            if ($new_password !== $confirm_password) {
                $errors[] = "Passwords do not match";
            }
        }

        if (empty($errors)) {
            $email_changed = ($new_email !== $user['email']);
            
            if (!empty($new_password)) {
                $hashed_password = hash('sha256', $new_password);
                $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                $result = $stmt->execute([$new_username, $new_email, $hashed_password, $_SESSION['user_id']]);
            } else {
                $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $result = $stmt->execute([$new_username, $new_email, $_SESSION['user_id']]);
            }

            if ($result) {
                $_SESSION['username'] = $new_username;
                
                // Send email notification if email was changed
                if ($email_changed) {
                    $subject = "Email Address Updated - SmartUnion";
                    $message = "
                    <html>
                    <head>
                        <title>Email Address Updated</title>
                    </head>
                    <body>
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                            <h2 style='color: #333;'>Email Address Updated</h2>
                            <p>Hello " . htmlspecialchars($new_username) . ",</p>
                            <p>Your email address has been successfully updated to: <strong>" . htmlspecialchars($new_email) . "</strong></p>
                            <p>If you did not make this change, please contact your administrator immediately.</p>
                            <hr style='border: 1px solid #eee; margin: 20px 0;'>
                            <p style='color: #666; font-size: 12px;'>
                                This email was sent on " . date('F d, Y \a\t h:i A') . "<br>
                                SmartUnion Membership System
                            </p>
                        </div>
                    </body>
                    </html>";
                    
                    sendEmailNotification($new_email, $subject, $message);
                }
                
                $success = !empty($new_password) ? "Profile updated successfully! Email notification sent." : "Profile updated successfully!";
            } else {
                $errors[] = "Failed to update profile";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - SmartUnion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <link href="assets/css/profile-card.css" rel="stylesheet">
    <style>
    .btn-gradient-profile {
        background: linear-gradient(90deg, #1cc88a 0%, #007bff 100%);
        color: #fff !important;
        border: none;
        outline: none;
        font-size: 1.1rem;
        letter-spacing: 0.5px;
        font-weight: 600;
        padding: 0.7em 2.2em;
        border-radius: 2em;
        box-shadow: 0 4px 24px #007bff33, 0 1.5px 0 #fff;
        transition: all 0.18s cubic-bezier(.4,2,.6,1);
        position: relative;
        overflow: hidden;
    }
    .btn-gradient-profile:hover, .btn-gradient-profile:focus {
        background: linear-gradient(90deg, #007bff 0%, #1cc88a 100%);
        transform: scale(1.045);
        box-shadow: 0 8px 32px #007bff55, 0 2px 0 #fff;
    }
    .profile-success-alert {
        position: fixed;
        top: 32px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1055;
        min-width: 320px;
        max-width: 90vw;
        text-align: center;
        box-shadow: 0 4px 24px #1cc88a33;
        opacity: 1;
        transition: opacity 0.5s;
    }
    /* Avatar image sizing for profile card */
    .avatar {
    width: 100%;
    max-width: 380px;
    aspect-ratio: 1/1;
    height: auto;
        object-fit: cover;
        border-radius: 10%;
        box-shadow: 0 2px 12px #007bff22;
        margin-bottom: 1px;
        display: block;
        margin-left: auto;
        margin-right: auto;
        background: #f8f9fa;
    }
    @media (max-width: 576px) {
        .avatar {
            max-width: 240px;
        }
    }
    }
    .pc-card .pc-content.pc-avatar-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        padding-top: 16px;
    }
    .pc-user-info {
        margin-top: 8px;
        text-align: center;
    }
    .pc-card {
        overflow: visible;
        background: linear-gradient(135deg, #1cc88a 0%, #007bff 100%);
        border-radius: 24px;
        box-shadow: 0 8px 32px #007bff33;
        color: #fff;
        position: relative;
    }
    .pc-card .pc-content {
        background: transparent;
        color: #fff;
    }
    .pc-details h3 {
        color: #fff;
        font-weight: 700;
        text-shadow: 0 2px 8px #007bff44;
    }
    .pc-details p {
        color: #e0f7fa;
        font-size: 1.1em;
    }
    .pc-handle {
        color: #ffe066;
        font-weight: 600;
        font-size: 1.1em;
    }
    .pc-status {
        color: #fff;
        background: #007bff88;
        border-radius: 12px;
        padding: 2px 12px;
        display: inline-block;
        margin-top: 4px;
        font-size: 0.95em;
    }
    .pc-card .pc-shine {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 24px;
        pointer-events: none;
        background: linear-gradient(120deg, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0.04) 100%);
        z-index: 1;
    }
    .pc-card .pc-glare {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 24px;
        pointer-events: none;
        background: radial-gradient(circle at 30% 10%, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0.01) 80%);
        z-index: 2;
    }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-user me-2"></i>Profile</h1>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div id="profile-success-alert" class="alert alert-success profile-success-alert" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    </div>
                    <script>
                        setTimeout(function() {
                            var alert = document.getElementById('profile-success-alert');
                            if(alert) alert.style.opacity = '0';
                            setTimeout(function(){ if(alert) alert.style.display = 'none'; }, 600);
                        }, 2500);
                    </script>
                <?php endif; ?>


                                <div class="d-flex justify-content-center align-items-center flex-column" style="min-height: 70vh;">
                                    <form id="avatar-upload-form" method="POST" enctype="multipart/form-data" class="mb-4" style="text-align:center;">
                                        <input type="file" name="avatar" id="avatar-input" accept="image/*" style="display:none" onchange="document.getElementById('avatar-upload-form').submit();">
                                        <button class="btn btn-gradient-profile" type="button" onclick="document.getElementById('avatar-input').click();">
                                            <i class="fas fa-image me-2"></i>Change Profile Picture
                                        </button>
                                        <input type="hidden" name="upload_avatar" value="1">
                                    </form>
                                    <div class="pc-card-wrapper">
                                        <section class="pc-card">
                                            <div class="pc-inside">
                                                <div class="pc-shine"></div>
                                                <div class="pc-glare"></div>
                                                <div class="pc-content pc-avatar-content">
                                                    <img
                                                        class="avatar"
                                                        src="uploads/avatars/<?php echo htmlspecialchars($currentAvatarBasename ?? ''); ?>?v=<?php echo time(); ?>"
                                                        alt="<?php echo htmlspecialchars($user['username']); ?> avatar"
                                                        loading="lazy"
                                                        onerror="this.style.display='none';"
                                                    />
                                                    <div class="pc-user-info">
                                                        <div class="pc-handle">@<?php echo htmlspecialchars($user['username']); ?></div>
                                                        <div class="pc-status">
                                                            <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>Admin<?php else: ?><?php echo htmlspecialchars($member_status ?? 'Member'); ?><?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="pc-content">
                                                    <div class="pc-details">
                                                        <h3><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h3>
                                                        <p>
                                                            <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
                                                                System Administrator
                                                            <?php else: ?>
                                                                <?php echo htmlspecialchars($user['email'] ?? ''); ?>
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </section>
                                    </div>
                                </div>

                
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/profile-card.js"></script>
</body>
</html>