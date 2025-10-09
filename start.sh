#!/bin/bash

# Startup script for Render deployment
echo "Starting SmartApp..."

# Wait for database to be ready (for Render)
echo "Waiting for database connection..."
sleep 5

# Initialize database if needed
echo "Initializing database..."
php -f render_deploy.php > /tmp/deploy.log 2>&1

# Set up cron job for email queue processing (every minute)
echo "Setting up email queue processor..."
echo "* * * * * cd /var/www/html && php -f process_email_queue.php > /tmp/email_queue_cron.log 2>&1" | crontab -

# Start email queue processor in background
echo "Starting email queue processor..."
php -f process_email_queue.php > /tmp/email_queue.log 2>&1 &

# Start Apache
echo "Starting Apache server..."
exec apache2-foreground
