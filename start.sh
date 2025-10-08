#!/bin/bash

# Startup script for Render deployment
echo "Starting SmartApp..."

# Wait for database to be ready
echo "Waiting for database connection..."
sleep 10

# Initialize database if needed
echo "Initializing database..."
php init_db.php

# Start Apache
echo "Starting Apache server..."
apache2-foreground
