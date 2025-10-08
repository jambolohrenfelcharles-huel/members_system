# Gmail Setup Guide for SmartUnion System

To enable actual email delivery, you need to configure Gmail with an App Password.

## Step 1: Enable 2-Factor Authentication

1. Go to your Google Account: https://myaccount.google.com/
2. Click on **Security** in the left sidebar
3. Under "How you sign in to Google", click on **2-Step Verification**
4. If it's not enabled, turn it ON and follow the setup process

## Step 2: Generate App Password

1. After enabling 2-Step Verification, go back to **Security**
2. Under "How you sign in to Google", click on **App passwords**
3. You may need to re-enter your Google password
4. In the "Select app" dropdown, choose **Mail**
5. In the "Select device" dropdown, choose **Other (Custom name)**
6. Enter a name like "SmartUnion System"
7. Click **Generate**
8. **Copy the 16-character password immediately** (it won't be shown again)

## Step 3: Update Configuration

1. Open `config/email_config.php`
2. Replace `your-app-password-here` with your 16-character App Password:

```php
define('SMTP_PASSWORD', 'your-16-character-app-password-here');
```

## Step 4: Test Email

1. Go to your SmartUnion dashboard
2. Navigate to **Profile** page
3. Click the **Test** button next to your email address
4. Check your email inbox (and spam folder)

## Important Notes

- **Never use your regular Gmail password** - it won't work
- **App Passwords are required** when 2FA is enabled
- **Keep your App Password secure** - don't share it
- **If you change your Gmail password**, you don't need to update the App Password

## Troubleshooting

- **"Authentication failed"**: Check that you're using the App Password, not your regular password
- **"Connection refused"**: Check your internet connection and firewall settings
- **"TLS/SSL error"**: Make sure you're using port 587 with TLS encryption
- **No email received**: Check spam/junk folder first

## Alternative: Use PHP mail() function

If Gmail SMTP doesn't work, the system will automatically fall back to PHP's mail() function, which uses your server's local mail configuration.
