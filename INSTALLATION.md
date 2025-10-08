# Membership System - Installation Guide

## 🚀 Quick Start (5 Minutes)

### Prerequisites
- XAMPP, WAMP, or any PHP/MySQL server
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

### Step 1: Download and Setup
1. **Download the system files** to your web server directory:
   - For XAMPP: `C:\xampp\htdocs\Smart\`
   - For WAMP: `C:\wamp64\www\Smart\`
   - For Linux: `/var/www/html/Smart/`

2. **Extract all files** to the Smart directory

### Step 2: Database Setup
1. **Start your web server** (XAMPP/WAMP)
2. **Open phpMyAdmin** (usually `http://localhost/phpmyadmin`)
3. **Import the database:**
   - Click "Import" tab
   - Choose file: `db/members_system.sql`
   - Click "Go" to import

### Step 3: Database Configuration (Optional)
If your MySQL settings are different, edit `config/database.php`:
```php
private $host = 'localhost';        // Your MySQL host
private $db_name = 'members_system'; // Database name
private $username = 'root';         // Your MySQL username
private $password = '';             // Your MySQL password
```

### Step 4: Access the System
1. **Open your browser** and go to: `http://localhost/Smart/`
2. **Login with default credentials:**
   - Username: `admin`
   - Password: `123`

### Step 5: First Steps
1. **Change the default password** (Profile → Change Password)
2. **Add your first member** (Members → Add Member)
3. **Create an event** (Events → Add Event)
4. **Explore the system** using the sidebar navigation

## 🔧 Detailed Installation

### System Requirements
- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher
- **Web Server:** Apache/Nginx
- **Extensions:** PDO, PDO_MySQL
- **Browser:** Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

### File Permissions (Linux/Mac)
```bash
chmod 755 dashboard/
chmod 644 config/database.php
chmod 644 .htaccess
```

### Security Configuration
1. **Change default admin password** immediately
2. **Update database credentials** in `config/database.php`
3. **Enable HTTPS** in production
4. **Regular backups** of database

## 📁 File Structure
```
Smart/
├── auth/                    # Authentication system
├── config/                  # Configuration files
├── dashboard/               # Main application
│   ├── assets/css/         # Stylesheets
│   ├── includes/           # Shared components
│   ├── members/            # Member management
│   ├── events/             # Event management
│   ├── attendance/         # Attendance tracking
│   ├── announcements/      # Announcement system
│   ├── reports/            # Analytics & reports
│   └── admin/              # Admin panel
├── db/                     # Database files
├── .htaccess              # Security & URL rules
└── index.php              # Entry point
```

## 🛠️ Troubleshooting

### Common Issues

#### 1. "Database connection failed"
- Check MySQL is running
- Verify credentials in `config/database.php`
- Ensure database exists

#### 2. "Page not found" errors
- Check file paths are correct
- Ensure all files are uploaded
- Check web server is running

#### 3. "Permission denied" errors
- Check file permissions
- Ensure web server can read files

#### 4. CSS not loading
- Check `dashboard/assets/css/dashboard.css` exists
- Clear browser cache
- Check file permissions

### Debug Mode
Access `http://localhost/Smart/debug.php` to check:
- File structure
- Database connection
- PHP configuration
- System status

## 🔒 Security Best Practices

### Production Deployment
1. **Change default credentials**
2. **Use strong passwords**
3. **Enable HTTPS**
4. **Regular updates**
5. **Database backups**
6. **Access logging**

### Database Security
- Use dedicated MySQL user
- Limit database permissions
- Regular backups
- Monitor access logs

## 📊 System Features

### Core Modules
- ✅ **Member Management** - Complete CRUD operations
- ✅ **Event Management** - Create and track events
- ✅ **Attendance Tracking** - Mark and monitor attendance
- ✅ **Announcements** - System-wide communications
- ✅ **Reports & Analytics** - Data visualization
- ✅ **Admin Panel** - User and system management

### User Roles
- **Admin:** Full system access
- **Member:** Limited access (if implemented)

## 🎯 Getting Started

### First Login
1. Go to `http://localhost/Smart/`
2. Login with `admin` / `123`
3. Change password in Profile
4. Explore the dashboard

### Adding Your First Member
1. Click "Members" in sidebar
2. Click "Add Member"
3. Fill in required information
4. Save member

### Creating Your First Event
1. Click "Events" in sidebar
2. Click "Add Event"
3. Enter event details
4. Set date and time
5. Save event

### Marking Attendance
1. Click "Attendance" in sidebar
2. Click "Mark Attendance"
3. Select member from list
4. Confirm attendance

## 📞 Support

### Common Solutions
- **Clear browser cache** if styles don't load
- **Check file permissions** for upload issues
- **Restart web server** for connection issues
- **Verify database import** for data issues

### Getting Help
1. Check the debug page: `debug.php`
2. Review error logs
3. Verify file structure
4. Test database connection

## 🎉 Success!

Once installed, you'll have a complete membership management system with:
- Modern, responsive interface
- Full member management
- Event tracking
- Attendance monitoring
- Analytics and reporting
- Admin controls

**Enjoy your new membership system!** 🚀
