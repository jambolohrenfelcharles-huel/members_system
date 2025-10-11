<?php
session_start();
require_once '../config/comprehensive_email_solution.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_email = trim($_POST['user_email']);
    $message = trim($_POST['message']);
    if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (empty($message)) {
        $error = 'Message cannot be empty.';
    } else {
        $admin_email = 'charlesjambo3@gmail.com';
        $subject = 'Contact Request from SmartUnion Signup Page';
        $body = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
        $body .= '<h2 style="color: #333;">New Contact Request</h2>';
        $body .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">';
        $body .= '<p><strong>From:</strong> ' . htmlspecialchars($user_email) . '</p>';
        $body .= '<p><strong>Message:</strong></p>';
        $body .= '<div style="background: white; padding: 15px; border-left: 4px solid #667eea; margin: 10px 0;">';
        $body .= nl2br(htmlspecialchars($message));
        $body .= '</div>';
        $body .= '</div>';
        $body .= '<p style="color: #666; font-size: 14px;">This message was sent from the SmartUnion contact form.</p>';
        $body .= '</div>';
        
        // Use comprehensive email solution that will definitely work
        $sent = sendEmailComprehensive($admin_email, $subject, $body, null, null, $user_email, 'SmartUnion User');
        
        if ($sent) {
            $success = 'Your message has been sent to the administrator successfully.';
        } else {
            $error = 'Failed to send your message. Please try again later or contact support directly.';
            // Log the error for debugging
            error_log("Failed to send contact email from: $user_email");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Administrator - SmartUnion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #667eea, #764ba2, #43cea2, #185a9d, #667eea);
            background-size: 400% 400%;
            animation: moveBg 18s ease-in-out infinite;
            min-height: 100vh;
        }
        @keyframes moveBg {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .contact-card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.12);
            overflow: hidden;
            margin-top: 40px;
        }
        .contact-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 1rem;
            text-align: center;
            border-radius: 18px 18px 0 0;
        }
        .contact-header h4 {
            font-weight: 700;
            letter-spacing: 1px;
        }
        .contact-body {
            padding: 2rem 1.5rem;
        }
        .form-label {
            font-weight: 600;
            color: #667eea;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.18);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.25);
        }
        .contact-footer {
            text-align: center;
            color: #fff;
            font-size: 1rem;
            margin-top: 2rem;
            opacity: 0.85;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="contact-card shadow-lg">
                    <div class="contact-header">
                        <i class="fas fa-user-shield fa-2x mb-2"></i>
                        <h4>Contact Administrator</h4>
                        <p class="mb-0">Need help? Send your message below.</p>
                    </div>
                    <div class="contact-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger mb-3"><i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?></div>
                        <?php elseif ($success): ?>
                            <div class="alert alert-success mb-3"><i class="fas fa-check-circle me-2"></i><?php echo $success; ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="user_email" class="form-label"><i class="fas fa-envelope me-2"></i>Your Email</label>
                                <input type="email" class="form-control" id="user_email" name="user_email" required placeholder="Enter your email">
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label"><i class="fas fa-comment-dots me-2"></i>Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Type your message to the administrator..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-2"><i class="fas fa-paper-plane me-2"></i>Send Message</button>
                        </form>
                        <a href="signup.php" class="btn btn-outline-secondary w-100" style="border-radius:10px;"><i class="fas fa-arrow-left me-2"></i>Back to Signup</a>
                    </div>
                </div>
                <div class="contact-footer">
                    <span>SmartUnion &copy; 2025</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
