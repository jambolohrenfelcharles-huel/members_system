#!/bin/bash

# Simple startup script for Render deployment
echo "Starting SmartApp..."

# Start Apache directly
echo "Starting Apache server..."
exec apache2-foreground
