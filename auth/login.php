<?php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard/index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $username = $_POST['username'];
    $password = $_POST['password'];
    $query = "SELECT id, username, password, role, blocked, blocked_reason FROM users WHERE username = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && hash('sha256', $password) === $user['password']) {
        // Check if user is blocked
        if (!empty($user['blocked']) && $user['blocked']) {
            $error = 'Your account has been blocked. Reason: ' . ($user['blocked_reason'] ?: 'No reason provided');
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                echo json_encode(['success' => false, 'error' => $error]);
                exit();
            }
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                echo json_encode(['success' => true]);
                exit();
            } else {
                header('Location: ../dashboard/index.php');
                exit();
            }
        }
    } else {
        $error = 'Invalid username or password';
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(['success' => false, 'error' => $error]);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to SmartUnion</title>
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
        .hero, .features {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 1.2s cubic-bezier(.23,1.01,.32,1) forwards;
        }
        .features {
            animation-delay: 0.5s;
        }
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: none;
            }
        }
        .hero {
            min-height: 40vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-align: center;
        }
        .hero-logo {
            width: 90px;
            height: 90px;
            object-fit: contain;
            margin-bottom: 1.2rem;
            box-shadow: 0 4px 24px rgba(102,126,234,0.18);
            border-radius: 50%;
            background: #fff;
        }
        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 0.7rem;
        }
        .hero-tagline {
            font-size: 1.2rem;
            font-weight: 400;
            margin-bottom: 2.2rem;
            opacity: 0.93;
        }
        .btn-signin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.85rem 2.2rem;
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: 1px;
            color: #fff;
            box-shadow: 0 4px 16px rgba(102,126,234,0.13);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-signin:hover {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 8px 24px rgba(102,126,234,0.22);
            color: #fff;
        }
        
        .features {
            background: transparent;
            border-radius: 18px;
            box-shadow: none;
            margin: 2rem auto 1.5rem auto;
            max-width: 900px;
            padding: 2.5rem 1.5rem 2rem 1.5rem;
            display: flex;
            gap: 2.5rem;
            flex-wrap: wrap;
        }
        .feature-icon {
            font-size: 2.2rem;
            color: #667eea;
            margin-bottom: 0.7rem;
        }
        .feature-title {
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
        }
        .feature-desc {
            font-size: 1rem;
            color: #555;
            opacity: 0.92;
        }
        .footer {
            text-align: center;
            color: #fff;
            font-size: 1rem;
            margin-top: auto;
            padding: 1.2rem 0 0.5rem 0;
            opacity: 0.85;
        }
        .fade-out {
            opacity: 0 !important;
            pointer-events: none;
            transition: opacity 0.7s cubic-bezier(.23,1.01,.32,1);
        }
        #loginSection {
            display: none;
            opacity: 0;
            transform: translateY(80px) scale(0.98);
            transition: opacity 1.4s cubic-bezier(.23,1.01,.32,1), transform 1.4s cubic-bezier(.23,1.01,.32,1);
        }
        #loginSection.show {
            display: block;
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        .slide-up {
            opacity: 0;
            transform: translateY(-80px) scale(0.97);
            transition: opacity 1.1s cubic-bezier(.23,1.01,.32,1), transform 1.1s cubic-bezier(.23,1.01,.32,1);
        }
        .hero, .features {
            will-change: opacity, transform;
        }
        .splash {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(120deg, #667eea 0%, #764ba2 100%);
            background-size: 200% 200%;
            animation: gradientMove 3s ease-in-out infinite alternate;
            color: #fff;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            transition: opacity 0.5s;
        }
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            100% { background-position: 100% 50%; }
        }
        .splash.hide {
            opacity: 0;
            pointer-events: none;
        }
        .splash-card {
            background: rgba(30, 30, 60, 0.85);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
            padding: 2.5rem 3rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 320px;
        }
        .splash-title {
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 1.2rem;
        }
        .splash-desc {
            font-size: 1.1rem;
            font-weight: 400;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .spinner {
            margin-bottom: 1.5rem;
        }
        .spinner .fa-spinner {
            font-size: 3.5rem;
            color: #fff;
            filter: drop-shadow(0 0 8px #764ba2);
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-header.d-flex {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .login-header .d-flex {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
        }
        .login-body {
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
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        #loginSection {
            display: none;
            opacity: 0;
            transform: translateY(80px) scale(0.98);
            transition: opacity 1.4s cubic-bezier(.23,1.01,.32,1), transform 1.4s cubic-bezier(.23,1.01,.32,1);
        }
        #loginSection.show {
            display: block;
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        .slide-up {
            opacity: 0;
            transform: translateY(-80px) scale(0.97);
            transition: opacity 1.1s cubic-bezier(.23,1.01,.32,1), transform 1.1s cubic-bezier(.23,1.01,.32,1);
        }
        .hero, .features {
            will-change: opacity, transform;
        }
        .signin-splash {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(120deg, #667eea 0%, #764ba2 100%);
            background-size: 200% 200%;
            animation: gradientMove 3s ease-in-out infinite alternate;
            color: #fff;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            transition: opacity 0.7s cubic-bezier(.23,1.01,.32,1), transform 0.7s cubic-bezier(.23,1.01,.32,1);
            transform: scale(1.04);
            opacity: 0;
            pointer-events: none;
        }
        .signin-splash.active {
            opacity: 1;
            pointer-events: all;
            transform: scale(1);
        }
        .signin-splash-card {
            background: rgba(30, 30, 60, 0.97);
            border-radius: 28px;
            box-shadow: 0 8px 32px 0 rgba(102,126,234,0.18), 0 0 0 6px rgba(118,75,162,0.13);
            padding: 2.7rem 3.2rem 2.2rem 3.2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 320px;
            position: relative;
            border: 2.5px solid #764ba2;
            animation: splashGlow 2.2s infinite alternate;
            transition: box-shadow 0.7s cubic-bezier(.23,1.01,.32,1), background 0.7s cubic-bezier(.23,1.01,.32,1);
        }
        @keyframes splashGlow {
            0% { box-shadow: 0 8px 32px 0 rgba(102,126,234,0.18), 0 0 0 6px rgba(118,75,162,0.13); }
            100% { box-shadow: 0 8px 32px 0 rgba(102,126,234,0.28), 0 0 0 16px rgba(118,75,162,0.18); }
        }
        .signin-splash-title {
            font-size: 2.1rem;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 1.1rem;
            background: linear-gradient(90deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .signin-splash-desc {
            font-size: 1.1rem;
            font-weight: 400;
            margin-bottom: 1.3rem;
            text-align: center;
            color: #e0e0ff;
        }
        .signin-splash .spinner-border {
            width: 3.2rem;
            height: 3.2rem;
            color: #fff;
            filter: drop-shadow(0 0 12px #764ba2);
            margin-bottom: 1.2rem;
        }
        .splash-check {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.2rem;
            box-shadow: 0 0 18px 0 #43cea244, 0 0 0 6px #667eea22;
            opacity: 0;
            animation: checkAppear 0.7s 0.7s forwards cubic-bezier(.23,1.01,.32,1);
            transition: opacity 0.5s cubic-bezier(.23,1.01,.32,1), transform 0.5s cubic-bezier(.23,1.01,.32,1);
        }
        @keyframes checkAppear {
            from { opacity: 0; transform: scale(0.7); }
            to { opacity: 1; transform: scale(1); }
        }
        .splash-check svg {
            width: 32px;
            height: 32px;
            stroke: #fff;
            stroke-width: 3.5;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: checkDraw 0.7s 1.2s forwards cubic-bezier(.23,1.01,.32,1);
        }
        @keyframes checkDraw {
            to { stroke-dashoffset: 0; }
        }
        .features .col-md-4 {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(102,126,234,0.13);
            position: relative;
            overflow: hidden;
            transition: box-shadow 0.4s cubic-bezier(.23,1.01,.32,1), transform 0.4s cubic-bezier(.23,1.01,.32,1);
            animation: decayCardFloat 3.2s ease-in-out infinite alternate;
            will-change: transform, box-shadow;
        }
        @keyframes decayCardFloat {
            0% { transform: translateY(0) scale(1); box-shadow: 0 8px 32px rgba(102,126,234,0.13); }
            50% { transform: translateY(-12px) scale(1.04) rotate(-2deg); box-shadow: 0 16px 48px rgba(102,126,234,0.18); }
            100% { transform: translateY(0) scale(1); box-shadow: 0 8px 32px rgba(102,126,234,0.13); }
        }
        .features .col-md-4:hover {
            transform: translateY(-22px) scale(1.08) rotate(-3deg);
            box-shadow: 0 24px 64px rgba(102,126,234,0.22), 0 2px 8px rgba(67,206,162,0.10);
            z-index: 2;
        }
        .features .col-md-4::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none;
            background: radial-gradient(circle at 60% 20%, #764ba2 0%, #43cea2 60%, transparent 100%);
            opacity: 0.13;
            transition: opacity 0.4s;
            z-index: 1;
        }
        .features .col-md-4:hover::before {
            opacity: 0.22;
        }
        .features .feature-icon {
            font-size: 2.7rem;
            color: #667eea;
            margin-bottom: 0.7rem;
            z-index: 2;
            position: relative;
            filter: drop-shadow(0 2px 8px #764ba2aa);
            transition: filter 0.3s;
        }
        .features .col-md-4:hover .feature-icon {
            filter: drop-shadow(0 6px 18px #764ba2cc);
        }
        .features .feature-title {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 0.4rem;
            z-index: 2;
            position: relative;
            color: #181a1b;
            letter-spacing: 0.5px;
        }
        .features .feature-desc {
            font-size: 1.08rem;
            color: #555;
            opacity: 0.92;
            z-index: 2;
            position: relative;
        }
        }
        .features .col-md-4:nth-child(2) {
            animation-delay: 0.7s;
        }
        .features .col-md-4:nth-child(3) {
            animation-delay: 1.4s;
        }
        @keyframes featureFloat {
            0% { transform: translateY(0); }
            50% { transform: translateY(-10px) scale(1.03); }
            100% { transform: translateY(0); }
        }
        .features .col-md-4:hover {
            transform: translateY(-18px) scale(1.06) rotate(-1deg) !important;
            box-shadow: 0 8px 32px rgba(102,126,234,0.13), 0 2px 8px rgba(67,206,162,0.10);
            z-index: 2;
        }
        .btn-signin .fa-rocket {
            transition: transform 0.7s cubic-bezier(.23,1.01,.32,1), opacity 0.7s cubic-bezier(.23,1.01,.32,1);
        }
        .btn-signin.rocket-fly .fa-rocket {
            transform: translateY(-80px) scale(1.25) rotate(-18deg);
            opacity: 0;
        }
        /* Typing animation styles for hero-title/tagline */
        .hero-typing {
          display: inline-block;
          position: relative;
          min-height: 2.5em;
          color: #fff;
          font-size: 2.5rem;
          font-weight: 700;
          letter-spacing: 1px;
          margin-bottom: 2.2rem;
          text-align: center;
        }
        .hero-typing .tagline {
          display: inline;
          font-size: 1.2rem;
          font-weight: 400;
          opacity: 0.93;
          margin-left: 0;
        }
        .typing-cursor {
          display: inline-block;
          width: 1ch;
          color: #fff;
          animation: blink-cursor 0.7s steps(1) infinite;
          font-weight: 700;
          font-size: 1.1em;
          vertical-align: bottom;
        }
        @keyframes blink-cursor {
          0%, 49% { opacity: 1; }
          50%, 100% { opacity: 0; }
        }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.querySelector('.btn-signin');
        var hero = document.querySelector('.hero');
        var features = document.querySelector('.features');
        var loginSection = document.getElementById('loginSection');
        var splash = document.getElementById('signinSplash');
        if (btn && hero && features && loginSection) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var rocket = btn.querySelector('.fa-rocket');
                if (rocket) {
                    btn.classList.add('rocket-fly');
                    setTimeout(function() {
                        btn.classList.remove('rocket-fly');
                        hero.classList.add('slide-up');
                        features.classList.add('slide-up');
                        loginSection.classList.add('show');
                        loginSection.scrollIntoView({behavior: 'smooth'});
                    }, 900);
                } else {
                    hero.classList.add('slide-up');
                    features.classList.add('slide-up');
                    loginSection.classList.add('show');
                    loginSection.scrollIntoView({behavior: 'smooth'});
                }
            });
        }

        // AJAX login
        var loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(loginForm);
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'login.php', true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                // Hide forgot password on new attempt
                var forgotContainer = document.getElementById('forgotPasswordContainer');
                if (forgotContainer) forgotContainer.style.display = 'none';
                xhr.onload = function() {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        var alertBox = loginForm.querySelector('.alert-danger');
                        if (alertBox) alertBox.remove();
                        if (res.success) {
                            if (splash) {
                                splash.classList.add('active');
                                var spinner = document.getElementById('signinSpinner');
                                var check = document.getElementById('splashCheck');
                                setTimeout(function() {
                                    if (spinner) {
                                        spinner.style.opacity = '0';
                                        spinner.style.transition = 'opacity 0.5s cubic-bezier(.23,1.01,.32,1)';
                                        setTimeout(function(){ spinner.style.display = 'none'; }, 500);
                                    }
                                    if (check) {
                                        check.style.display = 'flex';
                                        check.style.opacity = '1';
                                        check.style.transform = 'scale(1)';
                                    }
                                }, 700);
                                setTimeout(function() {
                                    splash.classList.remove('active');
                                    setTimeout(function(){ window.location.href = '../dashboard/index.php'; }, 500);
                                }, 1800);
                            } else {
                                window.location.href = '../dashboard/index.php';
                            }
                        } else {
                            var errorDiv = document.createElement('div');
                            errorDiv.className = 'alert alert-danger';
                            errorDiv.role = 'alert';
                            errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>' + res.error;
                            loginForm.insertBefore(errorDiv, loginForm.firstChild);
                            // Show forgot password button
                            var forgotContainer = document.getElementById('forgotPasswordContainer');
                            if (forgotContainer) forgotContainer.style.display = 'block';
                        }
                    } catch (err) {
                        // fallback: reload
                        window.location.reload();
                    }
                };
                xhr.send(formData);
            });
        }

        // Typing animation for combined hero text (title + tagline), tagline under title, cursor at end
        (function() {
          const title = "SmartUnion";
          const tagline = "Empowering Clubs & Members with Smart Management";
          const fullText = title + "\n" + tagline;
          const typingSpeed = 65;
          const cursorChar = '|';
          let charIndex = 0;
          const el = document.getElementById('heroTyping');
          if (!el) return;

          function type() {
            charIndex++;
            let current = fullText.substring(0, charIndex);
            let html = '';
            if (current.includes('\n')) {
              const [typedTitle, ...typedTagline] = current.split('\n');
              html =
                typedTitle +
                '<br><span class="tagline">' + (typedTagline.join('\n') || '') + '<span class="typing-cursor">' + cursorChar + '</span></span>';
            } else {
              html = current + '<span class="typing-cursor">' + cursorChar + '</span>';
            }
            el.innerHTML = html;
            if (charIndex < fullText.length) {
              setTimeout(type, typingSpeed);
            }
          }
          type();
        })();
        
    });
    </script>
</head>
<body>
    <div class="signin-splash" id="signinSplash">
        <div class="signin-splash-card">
            <div class="spinner-border" role="status" id="signinSpinner">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="splash-check" id="splashCheck" style="display:none;">
                <svg viewBox="0 0 32 32"><polyline points="7 17 14 24 25 9"></polyline></svg>
            </div>
            <div class="signin-splash-title">Signing In...</div>
            <div class="signin-splash-desc">Redirecting to your dashboard. Please wait.</div>
        </div>
    </div>
    <div class="hero">
        <img src="../logo.png" alt="SmartUnion Logo" class="hero-logo">
        <div class="hero-typing" id="heroTyping"></div>
        <div class="d-flex justify-content-center" style="gap: 1rem;">
            <a href="#loginSection" class="btn btn-signin"><i class="fas fa-rocket me-2"></i>Get Started</a>
            <a href="learn_more.php" class="btn btn-signin"><i class="fas fa-info-circle me-2"></i>Learn More</a>
        </div>
    </div>
    <div class="features row justify-content-center align-items-stretch g-4">
        <div class="col-md-4 text-center">
            <div class="feature-icon"><i class="fas fa-users"></i></div>
            <div class="feature-title">Member Management</div>
            <div class="feature-desc">Easily add, update, and track all your club members in one place.</div>
        </div>
        <div class="col-md-4 text-center">
            <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="feature-title">Event Scheduling</div>
            <div class="feature-desc">Organize, schedule, and monitor club events with automated reminders.</div>
        </div>
        <div class="col-md-4 text-center">
            <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
            <div class="feature-title">Reports & Analytics</div>
            <div class="feature-desc">Get instant insights into attendance, engagement, and club growth.</div>
        </div>
    </div>
    <div id="loginSection" class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-card">
                    <div class="login-header d-flex flex-column align-items-center justify-content-center" style="gap:12px;">
                        <div class="d-flex align-items-center justify-content-center" style="gap:16px;">
                            <div style="position:relative;display:flex;align-items:center;">

                                <img src="../logo.png" alt="SmartUnion Logo" style="width:64px;height:64px;object-fit:contain;z-index:2;">
                            </div>
                            <h3 class="mb-0">SmartUnion</h3>
                        </div>
                        <p class="mb-0">Sign in to your account</p>
                    </div>
                    <div class="login-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" id="loginForm">
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>Username
                                </label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <div class="position-relative">
                                    <input type="password" class="form-control" id="password" name="password" required style="padding-right:2.7rem;">
                                    <span id="togglePassword" style="position:absolute;top:0;right:0;height:100%;display:flex;align-items:center;cursor:pointer;padding-right:0.75rem;padding-left:0.5rem;">
                                        <i class="fas fa-eye" id="eyeIcon" style="font-size:1.15rem;color:#888;"></i>
                                    </span>
                                </div>
                                <div id="forgotPasswordContainer" style="display:none;">
                                    <a href="forgot_password.php" class="btn btn-link p-0" style="color:#764ba2;font-weight:600;">Forgot Password?</a>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-login">
                                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                                </button>
                            </div>
                        </form>
                        <div class="mt-3 text-center">
                            <span>Don't have an account?</span>
                            <a href="signup.php" class="fw-bold text-decoration-none"> Sign Up</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="text-center mt-4 mb-2">
        <a href="terms.php" class="text-decoration-underline">
            Terms of Use & Privacy Policy
        </a>
    </footer>
  
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('togglePassword').addEventListener('click', function() {
        var pwd = document.getElementById('password');
        var eye = document.getElementById('eyeIcon');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            eye.classList.remove('fa-eye');
            eye.classList.add('fa-eye-slash');
        } else {
            pwd.type = 'password';
            eye.classList.remove('fa-eye-slash');
            eye.classList.add('fa-eye');
        }
    });
    // Rocket fly animation on Get Started, then scroll to login
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.querySelector('.btn-signin');
        var hero = document.querySelector('.hero');
        var features = document.querySelector('.features');
        var loginSection = document.getElementById('loginSection');
        if (btn && hero && features && loginSection) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var rocket = btn.querySelector('.fa-rocket');
                if (rocket) {
                    btn.classList.add('rocket-fly');
                    setTimeout(function() {
                        btn.classList.remove('rocket-fly');
                        hero.classList.add('slide-up');
                        features.classList.add('slide-up');
                        loginSection.classList.add('show');
                        loginSection.scrollIntoView({behavior: 'smooth'});
                    }, 900);
                } else {
                    hero.classList.add('slide-up');
                    features.classList.add('slide-up');
                    loginSection.classList.add('show');
                    loginSection.scrollIntoView({behavior: 'smooth'});
                }
            });
        }
    });
    </script>
</body>

</html>
