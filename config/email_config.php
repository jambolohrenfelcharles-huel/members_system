<?php
/**
 * Email Configuration for SmartUnion System
 * 
 * This file contains email configuration settings.
 * Supports both local development and Render deployment via environment variables.
 */

// Email Configuration - Use environment variables for Render, fallback to defaults for local
define('EMAIL_FROM_ADDRESS', $_ENV['SMTP_FROM_EMAIL'] ?? 'charlesjambo3@gmail.com');
define('EMAIL_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'SmartUnion');
define('EMAIL_REPLY_TO', $_ENV['SMTP_FROM_EMAIL'] ?? 'charlesjambo3@gmail.com');

// SMTP Configuration - Use environment variables for Render deployment
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', (int)($_ENV['SMTP_PORT'] ?? 587)); // Use 587 for TLS, 465 for SSL
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? 'charlesjambo3@gmail.com');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? 'dotf ijlz bgsl nosr');
define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION'] ?? 'tls'); // Use 'tls' for port 587, 'ssl' for port 465

// Alternative SMTP providers
// Gmail: smtp.gmail.com:587 (TLS) or smtp.gmail.com:465 (SSL)
// Outlook: smtp-mail.outlook.com:587 (TLS)
// Yahoo: smtp.mail.yahoo.com:587 (TLS) or smtp.mail.yahoo.com:465 (SSL)
// Custom SMTP: your-smtp-server.com:587 (TLS)

// Email Settings
define('EMAIL_CHARSET', 'UTF-8');
define('EMAIL_PRIORITY', 3); // 1=High, 3=Normal, 5=Low

// Development Settings - Auto-detect environment
$isRender = isset($_ENV['RENDER']) || (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'render.com') !== false);
define('EMAIL_DEBUG', !$isRender); // Disable debug on Render to prevent output issues
define('EMAIL_LOG_ATTEMPTS', true); // Log email attempts to error log
define('EMAIL_SIMULATE_ON_LOCALHOST', false); // Set to true to simulate on localhost, false for real sending

/**
 * Get email configuration array
 */
function getEmailConfig() {
    return [
        'from_address' => EMAIL_FROM_ADDRESS,
        'from_name' => EMAIL_FROM_NAME,
        'reply_to' => EMAIL_REPLY_TO,
        'smtp_host' => SMTP_HOST,
        'smtp_port' => SMTP_PORT,
        'smtp_username' => SMTP_USERNAME,
        'smtp_password' => SMTP_PASSWORD,
        'smtp_encryption' => SMTP_ENCRYPTION,
        'charset' => EMAIL_CHARSET,
        'priority' => EMAIL_PRIORITY,
        'debug' => EMAIL_DEBUG,
        'log_attempts' => EMAIL_LOG_ATTEMPTS,
        'simulate_on_localhost' => EMAIL_SIMULATE_ON_LOCALHOST
    ];
}

/**
 * Check if email is properly configured
 */
function isEmailConfigured() {
    $config = getEmailConfig();
    
    // Basic checks
    if (empty($config['from_address']) || !filter_var($config['from_address'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    if (!function_exists('mail')) {
        return false;
    }
    
    return true;
}

/**
 * Get email configuration status
 */
function getEmailStatus() {
    $status = [
        'configured' => isEmailConfigured(),
        'mail_function' => function_exists('mail'),
        'from_address' => EMAIL_FROM_ADDRESS,
        'smtp_configured' => !empty(SMTP_HOST) && SMTP_HOST !== 'localhost',
        'is_localhost' => in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1'])
    ];
    
    return $status;
}
?>
