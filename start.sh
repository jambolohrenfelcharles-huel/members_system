#!/bin/bash

# Startup script for Render deployment
echo "Starting SmartApp..."

# Wait for database to be ready (for Render)
echo "Waiting for database connection..."
sleep 5

# Initialize database if needed
echo "Initializing database..."
php -f render_deploy.php > /tmp/deploy.log 2>&1

# Start Apache
echo "Starting Apache server..."
exec apache2-foreground
