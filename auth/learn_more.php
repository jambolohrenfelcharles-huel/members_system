<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learn More - SmartUnion</title>
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
        .container {
            margin-top: 60px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(102,126,234,0.13);
            padding: 2.5rem 2rem;
            max-width: 800px;
            position: relative;
            animation: floatCard 3.2s ease-in-out infinite alternate;
            will-change: transform, box-shadow;
        }
        @keyframes floatCard {
            0% { transform: translateY(0) scale(1); box-shadow: 0 8px 32px rgba(102,126,234,0.13); }
            50% { transform: translateY(-18px) scale(1.04) rotate(-2deg); box-shadow: 0 16px 48px rgba(102,126,234,0.18); }
            100% { transform: translateY(0) scale(1); box-shadow: 0 8px 32px rgba(102,126,234,0.13); }
        }
        h1 {
            color: #667eea;
            font-weight: 700;
            margin-bottom: 1.2rem;
        }
        .lead {
            color: #555;
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        ul {
            font-size: 1.08rem;
            color: #333;
        }
        .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 10px;
            padding: 0.7em 2em;
            font-weight: 600;
            box-shadow: 0 4px 16px rgba(102,126,234,0.13);
            border: none;
        }
        .btn-back:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-info-circle me-2"></i>Learn More About SmartUnion</h1>
        <p class="lead">SmartUnion is a modern club management system designed to help organizations and their members thrive. Here’s what you can do with SmartUnion:</p>
        <ul>
            <li><strong>Member Management:</strong> Add, update, and track all your club members in one place.</li>
            <li><strong>Event Scheduling:</strong> Organize, schedule, and monitor club events with automated reminders.</li>
            <li><strong>Reports & Analytics:</strong> Get instant insights into attendance, engagement, and club growth.</li>
            <li><strong>Secure Authentication:</strong> Protect member data with secure login and password management.</li>
            <li><strong>Email Notifications:</strong> Stay informed with system alerts and updates.</li>
        </ul>
        <a href="login.php" class="btn btn-back mt-4"><i class="fas fa-arrow-left me-2"></i>Back</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
