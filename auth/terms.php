
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Terms of Use & Privacy Policy - SmartUnion</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #e0eafc, #cfdef3, #a1c4fd, #c2e9fb, #e0eafc);
            background-size: 400% 400%;
            animation: moveBg 18s ease-in-out infinite;
            min-height: 100vh;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            overflow-x: hidden;
        }
        @keyframes moveBg {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .terms-container {
            max-width: 750px;
            margin: 48px auto;
            padding: 0 16px;
            animation: floatContainer 3s infinite ease-in-out;
        }
        @keyframes floatContainer {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }
        .glass-card {
            background: rgba(255,255,255,0.85);
            box-shadow: 0 8px 32px rgba(60,60,120,0.12);
            border-radius: 1.5rem;
            border: 1px solid rgba(200,200,255,0.18);
            backdrop-filter: blur(8px);
            transition: box-shadow 0.3s;
            opacity: 0;
            transform: translateY(40px);
            animation: fadeInCard 1.2s forwards;
        }
        .glass-card:nth-of-type(2) {
            animation-delay: 0.3s;
        }
        .glass-card:nth-of-type(3) {
            animation-delay: 0.6s;
        }
        @keyframes fadeInCard {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        h1 {
            font-weight: 800;
            color: #2563eb;
            letter-spacing: 1.5px;
            text-shadow: 0 2px 8px rgba(37,99,235,0.08);
        }
        .section-title {
            color: #2563eb;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section-title i {
            font-size: 1.4em;
            animation: floatIcon 2s infinite ease-in-out;
        }
        @keyframes floatIcon {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        ul {
            padding-left: 1.2rem;
            margin-bottom: 0;
        }
        .glass-card ul li {
            margin-bottom: 0.5rem;
            font-size: 1.05em;
        }
        .back-link {
            margin-top: 2.5rem;
        }
        .btn-primary {
            background: linear-gradient(90deg, #2563eb 0%, #60a5fa 100%);
            border: none;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(37,99,235,0.08);
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #1e40af 0%, #2563eb 100%);
        }
        .accent-bar {
            height: 6px;
            width: 80px;
            background: linear-gradient(90deg, #2563eb 0%, #60a5fa 100%);
            border-radius: 3px;
            margin: 0 auto 32px auto;
            position: relative;
            overflow: hidden;
        }
        .accent-bar::before {
            content: '';
            position: absolute;
            left: -80px;
            top: 0;
            height: 100%;
            width: 80px;
            background: linear-gradient(90deg, rgba(96,165,250,0.2) 0%, rgba(37,99,235,0.5) 100%);
            filter: blur(2px);
            animation: shimmerBar 2.5s linear infinite;
        }
        @keyframes shimmerBar {
            0% { left: -80px; }
            100% { left: 80px; }
        }
        @media (max-width: 576px) {
            .terms-container {
                margin: 10px;
                padding: 0;
            }
            h1 {
                font-size: 1.3rem;
            }
            .glass-card {
                border-radius: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="terms-container">
        <h1 class="mb-2 text-center">Terms of Use & Privacy Policy</h1>
        <div class="accent-bar"></div>
        <div class="glass-card mb-4">
            <div class="card-body">
                <h2 class="h5 section-title mb-3"><i class="bi bi-file-earmark-text"></i>Terms of Use</h2>
                <p>
                    Welcome to <b>SmartUnion</b>. By accessing and using our system, you agree to comply with all applicable laws and regulations. You must not misuse the system, attempt unauthorized access, or disrupt service for other users. All content, data, and features provided are for club and member management purposes only.
                </p>
                <ul>
                    <li>Do not share your login credentials with others.</li>
                    <li>Respect the privacy and rights of other users.</li>
                    <li>Do not upload or distribute harmful, illegal, or offensive content.</li>
                    <li>Admins reserve the right to suspend or terminate accounts for violations.</li>
                </ul>
            </div>
        </div>
        <div class="glass-card mb-4">
            <div class="card-body">
                <h2 class="h5 section-title mb-3"><i class="bi bi-shield-lock"></i>Privacy Policy</h2>
                <p>
                    <b>SmartUnion</b> values your privacy. We collect only the information necessary to provide club management services, such as your name, username, email, and activity logs. Your data is stored securely and will not be shared with third parties except as required by law.
                </p>
                <ul>
                    <li>Your personal information is used only for system functionality and communication.</li>
                    <li>We use cookies and session data to keep you signed in and improve your experience.</li>
                    <li>You may request deletion of your account and data at any time by contacting an admin.</li>
                    <li>We take reasonable measures to protect your data from unauthorized access.</li>
                </ul>
            </div>
        </div>
        <div class="text-center back-link">
            <a href="login.php" class="btn btn-primary px-4">
                <i class="bi bi-arrow-left"></i> Return
            </a>
        </div>
    </div>
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</body>
</html>