# Email Configuration Guide

This guide will help you configure actual email delivery for the SmartUnion system.

## Quick Setup

1. **Edit the email configuration file**: `config/email_config.php`
2. **Update the SMTP settings** with your email provider details
3. **Test the configuration** using the test email feature in the profile page

## SMTP Provider Examples

### Gmail
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password'); // Use App Password, not regular password
define('SMTP_ENCRYPTION', 'tls');
```

**Note for Gmail**: You need to enable 2-factor authentication and create an App Password.

### Outlook/Hotmail
```php
define('SMTP_HOST', 'smtp-mail.outlook.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@outlook.com');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_ENCRYPTION', 'tls');
```

### Yahoo Mail
```php
define('SMTP_HOST', 'smtp.mail.yahoo.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@yahoo.com');
define('SMTP_PASSWORD', 'your-app-password'); // Use App Password
define('SMTP_ENCRYPTION', 'tls');
```

### Custom SMTP Server
```php
define('SMTP_HOST', 'your-smtp-server.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-username');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_ENCRYPTION', 'tls');
```

## Configuration Steps

### Step 1: Update Email Configuration
Edit `config/email_config.php` and update these constants:

```php
// Email Configuration
define('EMAIL_FROM_ADDRESS', 'noreply@yourdomain.com');
define('EMAIL_FROM_NAME', 'SmartUnion System');

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_ENCRYPTION', 'tls');

// Development Settings
define('EMAIL_SIMULATE_ON_LOCALHOST', false); // Set to false for real sending
```

### Step 2: Test Configuration
1. Go to the Profile page in your dashboard
2. Click the "Test" button next to the email field
3. Enter a valid email address
4. Click "Send Test Email"
5. Check the success/error message

### Step 3: Verify Email Delivery
- Check the recipient's inbox (and spam folder)
- Look for the test email with subject "Test Email - SmartUnion System"
- If successful, your email configuration is working!

## Troubleshooting

### Common Issues

1. **"Failed to connect to mailserver"**
   - Check your SMTP_HOST and SMTP_PORT settings
   - Ensure your server can connect to the SMTP server
   - Try different ports (587 for TLS, 465 for SSL)

2. **"Authentication failed"**
   - Verify your SMTP_USERNAME and SMTP_PASSWORD
   - For Gmail/Yahoo, use App Passwords instead of regular passwords
   - Ensure 2-factor authentication is enabled (for Gmail/Yahoo)

3. **"Email sent but not received"**
   - Check spam/junk folders
   - Verify the recipient email address is correct
   - Some email providers may block emails from unknown senders

4. **"SMTP not configured"**
   - Make sure SMTP_HOST is not 'localhost'
   - Ensure SMTP_USERNAME and SMTP_PASSWORD are not empty
   - Check that EMAIL_SIMULATE_ON_LOCALHOST is set to false

### Debug Mode
Enable debug mode in `config/email_config.php`:
```php
define('EMAIL_DEBUG', true);
define('EMAIL_LOG_ATTEMPTS', true);
```

This will log detailed information about email attempts to your server's error log.

## Security Notes

- Never commit your email credentials to version control
- Use App Passwords for Gmail/Yahoo instead of regular passwords
- Consider using environment variables for sensitive information
- Regularly rotate your email passwords

## Production Deployment

For production deployment:

1. Set `EMAIL_DEBUG` to `false`
2. Set `EMAIL_SIMULATE_ON_LOCALHOST` to `false`
3. Use a professional email service (SendGrid, Mailgun, etc.) for better deliverability
4. Configure proper SPF, DKIM, and DMARC records for your domain

## Support

If you continue to have issues:
1. Check your server's error logs
2. Verify your SMTP settings with your email provider
3. Test with a different email provider
4. Contact your system administrator
