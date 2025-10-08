# Render Environment Variables Setup for MySQL

## Required Environment Variables

When deploying to Render, you need to set these environment variables in your web service:

### Database Configuration
```
DB_TYPE=mysql
DB_HOST=[Your MySQL Internal Database URL from Render]
DB_NAME=members_system
DB_USERNAME=smartapp_user
DB_PASSWORD=[Your MySQL Password from Render]
```

### Email Configuration (Optional)
```
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_FROM_EMAIL=your-email@gmail.com
SMTP_FROM_NAME=SmartApp
```

## How to Get MySQL Connection Details

1. **Create MySQL Service in Render:**
   - Go to Render Dashboard
   - Click "New +" → "MySQL"
   - Name: `smartapp-mysql-db`
   - Database: `members_system`
   - User: `smartapp_user`
   - Generate strong passwords for both user and root

2. **Get Internal Database URL:**
   - Go to your MySQL service
   - Copy the "Internal Database URL"
   - It will look like: `mysql://smartapp_user:password@hostname:3306/members_system`

3. **Set Environment Variables:**
   - Go to your web service
   - Add each environment variable listed above
   - Use the Internal Database URL for `DB_HOST`

## Example Environment Variables

```
DB_TYPE=mysql
DB_HOST=mysql://smartapp_user:your_password@dpg-abc123-a.oregon-postgres.render.com:3306/members_system
DB_NAME=members_system
DB_USERNAME=smartapp_user
DB_PASSWORD=your_password
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_FROM_EMAIL=your-email@gmail.com
SMTP_FROM_NAME=SmartApp
```

## Important Notes

- The `DB_HOST` should be the full Internal Database URL from Render
- Make sure your MySQL service is running before deploying the web service
- The database will be automatically initialized when the web service starts
- Default admin login: username `admin`, password `123` (change this after first login)

## Troubleshooting

If you get connection errors:
1. Check that the MySQL service is running
2. Verify the Internal Database URL is correct
3. Ensure all environment variables are set
4. Check the web service logs for detailed error messages
