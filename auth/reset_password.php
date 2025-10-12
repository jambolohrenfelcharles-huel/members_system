<?php
session_start();
require_once '../config/database.php';
require_once '../config/direct_email_solution.php';

$error = '';
$success = '';
$showForm = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("SELECT id, email, reset_expires FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && strtotime($user['reset_expires']) > time()) {
        $showForm = true;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'];
            $confirm = $_POST['confirm_password'];
            if (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif ($password !== $confirm) {
                $error = 'Passwords do not match.';
            } else {
                $hashed = hash('sha256', $password);
                $stmt = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
                if ($stmt->execute([$hashed, $user['id']])) {
                    $success = 'Your password has been reset successfully! You can now <a href=\'login.php\'>login</a>.';
                    $showForm = false;
                } else {
                    $error = 'Failed to reset password. Please try again.';
                }
            }
        }
    } else {
        $error = 'Invalid or expired token. Please request a new password reset.';
    }
} else {
    $error = 'No token provided.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SmartUnion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #667eea, #764ba2, #43cea2, #185a9d, #667eea);
            background-size: 400% 400%;
            animation: moveBg 18s ease-in-out infinite;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        @keyframes moveBg {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .reset-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: 80px;
        }
        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .reset-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .footer {
            text-align: center;
            color: #fff;
            font-size: 1rem;
            margin-top: auto;
            padding: 1.2rem 0 0.5rem 0;
            opacity: 0.85;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="reset-card shadow-lg p-0 w-100" style="max-width: 430px; border-radius: 30px;">
            <div class="reset-header" style="border-radius: 30px 30px 0 0;">
                <img src="../logo.png" alt="SmartUnion Logo" style="width:64px;height:64px;object-fit:contain;">
                <h3 class="mb-0 mt-2">Reset Password</h3>
                <p class="mb-0">Set your new password below</p>
            </div>
            <div class="reset-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    </div>
                <?php endif; ?>
                <?php if ($showForm): ?>
                <form method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label for="password" class="form-label"><i class="fas fa-lock me-2"></i>New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="Enter new password">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label"><i class="fas fa-lock me-2"></i>Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Re-enter new password">
                    </div>
                    <div class="d-grid mb-2">
                        <button type="submit" class="btn btn-primary btn-reset py-2">
                            <i class="fas fa-key me-2"></i>Reset Password
                        </button>
                    </div>
                </form>
                <?php endif; ?>
                <div class="mt-3 text-center">
                    <a href="login.php" class="fw-bold text-decoration-none">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
    <div class="footer">
        &copy; <?php echo date('Y'); ?> SmartUnion. All rights reserved;
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
