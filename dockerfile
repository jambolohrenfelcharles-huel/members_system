# Use the official PHP image with Apache
FROM php:8.2-apache

# Copy your project files into the container
COPY . /var/www/html/

# Expose port 80 for Render
EXPOSE 80
