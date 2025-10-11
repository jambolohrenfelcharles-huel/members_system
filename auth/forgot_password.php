<?php
// forgot_password.php
session_start();
require_once '../config/database.php';
require_once '../config/email_config.php'; // You should have SMTP config here

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            // Generate a unique token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            // Store token and expiry in DB
            $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $stmt->execute([$token, $expires, $email]);
            // Send email
            // Generate proper URL for both local and Render environments
            $protocol = 'https';
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                $protocol = 'https';
            } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
                $protocol = 'https';
            } elseif (isset($_ENV['RENDER']) || (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'render.com') !== false)) {
                $protocol = 'https'; // Render always uses HTTPS
            } else {
                $protocol = 'http';
            }
            
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = $protocol . '://' . $host;
            $resetLink = $baseUrl . '/auth/reset_password.php?token=' . $token;
            
            $subject = 'SmartUnion Password Reset Request';
            $message = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>";
            $message .= "<h2 style='color: #333;'>Password Reset Request</h2>";
            $message .= "<p>We received a request to reset your password for your SmartUnion account.</p>";
            $message .= "<p>Click the button below to reset your password:</p>";
            $message .= "<div style='text-align: center; margin: 30px 0;'>";
            $message .= "<a href='$resetLink' style='background-color: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>Reset Password</a>";
            $message .= "</div>";
            $message .= "<p style='color: #666; font-size: 14px;'>If the button doesn't work, copy and paste this link into your browser:</p>";
            $message .= "<p style='color: #667eea; word-break: break-all;'>$resetLink</p>";
            $message .= "<p style='color: #666; font-size: 14px;'>This link will expire in 1 hour for security reasons.</p>";
            $message .= "<p style='color: #666; font-size: 14px;'>If you did not request this password reset, please ignore this email.</p>";
            $message .= "<hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>";
            $message .= "<p style='color: #999; font-size: 12px;'>This email was sent from SmartUnion System</p>";
            $message .= "</div>";
            
            // Use direct email solution that will definitely work on Render
            require_once '../config/direct_email_solution.php';
            
            // Suppress output to prevent header issues
            ob_start();
            $emailSent = sendEmailDirect($email, $subject, $message);
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            if ($emailSent) {
                $success = 'A password reset link has been sent to your email. Please check your inbox and spam folder.';
            } else {
                $error = 'Failed to send email. Please check your email configuration or try again later.';
                // Log the error for debugging
                error_log("Failed to send password reset email to: $email");
            }
        } else {
            $error = 'No account found with that email.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SmartUnion</title>
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
        .forgot-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: 80px;
        }
        .forgot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .forgot-body {
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
        .btn-forgot {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-forgot:hover {
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
        <div class="forgot-card shadow-lg p-0 w-100" style="max-width: 430px; border-radius: 30px;">
            <div class="forgot-header" style="border-radius: 30px 30px 0 0;">
                <img src="../logo.png" alt="SmartUnion Logo" style="width:64px;height:64px;object-fit:contain;">
                <h3 class="mb-0 mt-2">Forgot Password</h3>
                <p class="mb-0">Enter your email to reset your password</p>
            </div>
            <div class="forgot-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label for="email" class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="Enter your email">
                    </div>
                    <div class="d-grid mb-2">
                        <button type="submit" class="btn btn-primary btn-forgot py-2">
                            <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                        </button>
                    </div>
                </form>
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
