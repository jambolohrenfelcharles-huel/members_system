# SmartApp Deployment Guide for Render

## Overview
This guide will help you deploy your SmartApp to Render.com using Docker and PostgreSQL. Your app will use MySQL locally and PostgreSQL on Render.

## Prerequisites
1. GitHub repository with your SmartApp code
2. Render.com account
3. Email service credentials (Gmail/SMTP)

## Quick Deployment (Recommended)

### Option 1: Using render.yaml (Automatic)
1. Push your code to GitHub
2. Go to [Render Dashboard](https://dashboard.render.com)
3. Click "New +" → "Blueprint"
4. Connect your GitHub repository
5. Render will automatically create both web service and PostgreSQL database
6. Set environment variables:
   - `POSTGRES_PASSWORD`: Generate a strong password
   - `SMTP_USERNAME`: Your Gmail address
   - `SMTP_PASSWORD`: Your Gmail app password
   - `SMTP_FROM_EMAIL`: Your Gmail address
   - `SMTP_FROM_NAME`: SmartApp

### Option 2: Manual Setup

#### A. Create PostgreSQL Database
1. Go to [Render Dashboard](https://dashboard.render.com)
2. Click "New +" → "PostgreSQL"
3. Configure:
   - **Name**: `smartapp-db`
   - **Database**: `members_system`
   - **User**: `smartapp_user`
   - **Password**: Generate a strong password (save this!)
   - **Plan**: Free

#### B. Create Web Service
1. Click "New +" → "Web Service"
2. Connect your GitHub repository
3. Configure:
   - **Name**: `smartapp-web`
   - **Environment**: `Docker`
   - **Dockerfile Path**: `./dockerfile`
   - **Plan**: Free

#### C. Set Environment Variables

In your web service, add these environment variables:

##### Database Configuration (Auto-linked if using render.yaml)
```
DB_TYPE=postgresql
DB_HOST=[Your PostgreSQL Internal Database URL]
DB_NAME=members_system
DB_USERNAME=smartapp_user
DB_PASSWORD=[Your PostgreSQL Password]
```

##### Email Configuration (Required for notifications)
```
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_FROM_EMAIL=your-email@gmail.com
SMTP_FROM_NAME=SmartApp
```

### 4. Get Database Connection Details

1. Go to your PostgreSQL service in Render
2. Copy the **Internal Database URL**
3. Extract the connection details:
   - Host: The hostname from the URL
   - Port: Usually 5432
   - Database: `members_system`
   - Username: `smartapp_user`
   - Password: The one you set

### 5. Deploy

1. Click "Create Web Service"
2. Render will automatically:
   - Build your Docker image
   - Install dependencies
   - Initialize the database
   - Start your application

### 6. Access Your Application

Once deployed, you'll get a URL like: `https://smartapp-web.onrender.com`

**Default Admin Login:**
- Username: `admin`
- Password: `123`

## File Structure for Deployment

```
SmartApp/
├── dockerfile              # Docker configuration
├── render.yaml             # Render service configuration
├── start.sh                # Startup script
├── init_db.php             # Database initialization
├── config/
│   └── database.php        # Database connection (supports both MySQL and PostgreSQL)
├── db/
│   ├── members_system.sql           # MySQL version (for local development)
│   └── members_system_postgresql.sql # PostgreSQL version (for Render)
└── ... (rest of your app)
```

## Troubleshooting

### Common Issues:

1. **Database Connection Failed**
   - Check environment variables
   - Ensure PostgreSQL service is running
   - Verify internal database URL

2. **Build Failed**
   - Check Dockerfile syntax
   - Ensure all dependencies are listed
   - Check build logs in Render dashboard

3. **Application Not Starting**
   - Check startup script permissions
   - Verify PHP extensions are installed
   - Check application logs

### Useful Commands:

```bash
# Check if database is accessible
php init_db.php

# Test database connection
php -r "
require_once 'config/database.php';
\$db = new Database();
\$conn = \$db->getConnection();
if (\$conn) echo 'Connected successfully!';
else echo 'Connection failed!';
"
```

## Security Notes

1. **Change Default Admin Password**: After first login, change the admin password
2. **Environment Variables**: Never commit sensitive data to Git
3. **HTTPS**: Render provides HTTPS by default
4. **Database**: Use strong passwords for production

## Monitoring

- Check Render dashboard for service status
- Monitor logs for errors
- Set up alerts for service downtime

## Scaling

- Free tier has limitations (sleeps after inactivity)
- Upgrade to paid plans for always-on service
- Consider database backups for production use

---

**Need Help?**
- Check Render documentation: https://render.com/docs
- Review application logs in Render dashboard
- Test locally with Docker before deploying
