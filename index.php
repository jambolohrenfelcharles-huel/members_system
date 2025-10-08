<!DOCTYPE html>
<html>
<head>
    <title>SmartApp - Club Management System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 600px;
        }
        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            display: block;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            margin: 10px;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        }
        .status {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .credentials {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #dee2e6;
        }
        .step {
            text-align: left;
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="logo.png" alt="SmartApp Logo" class="logo">
        <h1>SmartApp</h1>
        <p class="subtitle">Club Management System</p>
        
        <?php
        try {
            require_once 'config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            if ($conn) {
                // Check if admin user exists
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    echo "<div class='status success'>✅ Database connected and admin user ready!</div>";
                } else {
                    echo "<div class='status warning'>⚠️ Database connected but admin user not found</div>";
                }
            } else {
                echo "<div class='status info'>⚠️ Database connection failed - please run setup</div>";
            }
        } catch (Exception $e) {
            echo "<div class='status info'>⚠️ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
        
        <div class="credentials">
            <h3>🔐 Login Credentials</h3>
            <p><strong>Username:</strong> <code>admin</code></p>
            <p><strong>Password:</strong> <code>123</code></p>
        </div>
        
        <div class="status info">
            <h3>🚀 Render Deployment Ready!</h3>
            <p>Your SmartApp is configured for successful login on Render with PostgreSQL.</p>
        </div>
        
        <div style="text-align: left;">
            <h4>Quick Start:</h4>
            <div class="step">
                <strong>1. Setup Database:</strong> Run the database setup to create tables and admin user
            </div>
            <div class="step">
                <strong>2. Verify Login:</strong> Test the login functionality
            </div>
            <div class="step">
                <strong>3. Access Dashboard:</strong> Login and access your dashboard
            </div>
        </div>
        
        <div style="text-align: left;">
            <h4>Render Deployment:</h4>
            <div class="step">
                <strong>1. Create PostgreSQL Service:</strong> In Render dashboard, create PostgreSQL service
            </div>
            <div class="step">
                <strong>2. Set Environment Variables:</strong> DB_TYPE=postgresql, DB_HOST=[PostgreSQL URL]
            </div>
            <div class="step">
                <strong>3. Deploy Web Service:</strong> Connect GitHub repo and deploy
            </div>
            <div class="step">
                <strong>4. Setup Database:</strong> Visit setup_postgresql_render.php on your deployed app
            </div>
        </div>
        
        <div style="margin: 30px 0;">
            <a href="setup_universal_database.php" class="btn">Setup Database (Universal)</a>
            <a href="setup_postgresql_render.php" class="btn">Setup PostgreSQL (Render)</a>
            <a href="fix_render_login.php" class="btn btn-secondary">Fix Render Login Issues</a>
        </div>
        
        <div style="margin: 30px 0;">
            <a href="auth/login.php" class="btn">Login to Dashboard</a>
        </div>
        
        <div style="margin-top: 30px; font-size: 14px; color: #666;">
            <p><strong>Environment:</strong> <?php echo $_ENV['DB_TYPE'] ?? 'mysql'; ?> Database</p>
            <p><strong>Status:</strong> Ready for Render deployment</p>
        </div>
    </div>
</body>
</html>