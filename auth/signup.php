<?php
session_start();
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    // Prevent spaces in username
    if (preg_match('/\s/', $username)) {
        $error = 'Username must not contain spaces.';
    }

    // Check if email exists in members table (use correct table based on database type)
    $members_table = $database->getMembersTable();
    $query = "SELECT id FROM $members_table WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$member) {
        $error = 'Email is not yet registered. Please contact your administrator.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if username is already taken
        $query = "SELECT id FROM users WHERE username = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $error = 'Username is already taken.';
        } else {
            // Update the user with username and password if user exists with this email
            $query = "SELECT id FROM users WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $hashed_password = hash('sha256', $password);
                $query = "UPDATE users SET username = ?, password = ? WHERE email = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$username, $hashed_password, $email])) {
                    require_once '../config/phpmailer_helper.php';
                    $mailSubject = 'SmartUnion Account Created';
                    $mailBody = '<h2>Welcome to SmartUnion!</h2><p>Your account has been successfully created. You can now log in and start using the system.</p>';
                    $mailSent = sendMailPHPMailer($email, $mailSubject, $mailBody);
                    if ($mailSent) {
                        error_log('Signup notification email sent to ' . $email);
                        $success = 'Account created successfully! You can now log in.';
                    } else {
                        error_log('Signup notification email FAILED for ' . $email);
                        $success = 'Account created successfully! You can now log in. (Email notification failed to send)';
                    }
                } else {
                    $error = 'Failed to create account. Please try again.';
                }
            } else {
                // If user does not exist, create a new user
                $hashed_password = hash('sha256', $password);
                $query = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'member')";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$username, $hashed_password, $email])) {
                    require_once '../config/phpmailer_helper.php';
                    $mailSubject = 'SmartUnion Account Created';
                    $mailBody = '<h2>Welcome to SmartUnion!</h2><p>Your account has been successfully created. You can now log in and start using the system.</p>';
                    $mailSent = sendMailPHPMailer($email, $mailSubject, $mailBody);
                    if ($mailSent) {
                        error_log('Signup notification email sent to ' . $email);
                        $success = 'Account created successfully! You can now log in.';
                    } else {
                        error_log('Signup notification email FAILED for ' . $email);
                        $success = 'Account created successfully! You can now log in. (Email notification failed to send)';
                    }
                } else {
                    $error = 'Failed to create account. Please try again.';
                }
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
    <title>Sign Up - SmartUnion</title>
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
        .signup-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: 60px;
        }
        .signup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .signup-body {
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
        .btn-signup {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-signup:hover {
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
    <div class="container-fluid min-vh-100 d-flex align-items-center" style="padding:0;">
        <div class="row w-100" style="min-height: 90vh; margin-top: 2.5rem;">
            <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 30px 0 0 30px;">
                <div class="text-center w-75">
                  
                    <h2 class="animated-gradient-text fw-bold mb-3" style="font-size:2.2rem;">Welcome to SmartUnion</h2>
    <style>
        .animated-gradient-text {
            background: linear-gradient(270deg, #667eea, #43cea2, #764ba2, #667eea);
            background-size: 800% 800%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
            animation: gradientMoveText 4s ease-in-out infinite;
        }
        @keyframes gradientMoveText {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
                    <p class="text-white-50 mb-4" style="font-size:1.15rem;">Empowering Clubs & Members with Smart Management. Join us and experience seamless club management!</p>
                </div>
            </div>
            <div class="col-lg-6 d-flex align-items-center justify-content-center">
                <div class="signup-card shadow-lg p-0 w-100 ms-4" style="max-width: 430px; margin: 2.5rem 0; border-radius: 30px;">
                    <div class="signup-header" style="border-radius: 30px 30px 0 0;">
                        <img src="../logo.png" alt="SmartUnion Logo" style="width:64px;height:64px;object-fit:contain;">
                        <h3 class="mb-0 mt-2">Sign Up</h3>
                        <p class="mb-0">Create your account</p>
                    </div>
                    <div class="signup-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php elseif ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" id="signupForm">
                            <div class="mb-3">
                                <label for="email" class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
                                <input type="email" class="form-control" id="email" name="email" required placeholder="Enter your registered email">
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label"><i class="fas fa-user me-2"></i>Username</label>
                                <input type="text" class="form-control" id="username" name="username" required placeholder="Choose a username">
                                <script>
                                document.getElementById('username').addEventListener('input', function(e) {
                                    this.value = this.value.replace(/\s/g, '');
                                });
                                </script>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label"><i class="fas fa-lock me-2"></i>Password</label>
                                <input type="password" class="form-control" id="password" name="password" required placeholder="Create a password">
                            </div>
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label"><i class="fas fa-lock me-2"></i>Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Re-enter your password">
                            </div>
                            <div class="d-grid mb-2">
                                <button type="submit" class="btn btn-primary btn-signup py-2">
                                    <i class="fas fa-user-plus me-2"></i>Sign Up
                                </button>
                            </div>
                        </form>
                        <div class="mt-3 text-center">
                            Already have an account? <a href="login.php" class="fw-bold text-decoration-none">Sign In</a>
                        </div>
                        <div class="mt-3 text-center">
                            <a href="contact_admin.php" class="btn btn-outline-primary" style="border-radius:10px;">
                                <i class="fas fa-envelope"></i> Contact Administrator
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
