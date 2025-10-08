# Render Environment Variables Setup for PostgreSQL

## Required Environment Variables

When deploying to Render, you need to set these environment variables in your web service:

### Database Configuration
```
DB_TYPE=postgresql
DB_HOST=[Your PostgreSQL Internal Database URL from Render]
DB_NAME=members_system
DB_USERNAME=smartapp_user
DB_PASSWORD=[Your PostgreSQL Password from Render]
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

## How to Get PostgreSQL Connection Details

1. **Create PostgreSQL Service in Render:**
   - Go to Render Dashboard
   - Click "New +" → "PostgreSQL"
   - Name: `smartapp-db`
   - Database: `members_system`
   - User: `smartapp_user`
   - Generate a strong password

2. **Get Internal Database URL:**
   - Go to your PostgreSQL service
   - Copy the "Internal Database URL"
   - It will look like: `postgresql://smartapp_user:password@hostname:5432/members_system`

3. **Set Environment Variables:**
   - Go to your web service
   - Add each environment variable listed above
   - Use the Internal Database URL for `DB_HOST`

## Example Environment Variables

```
DB_TYPE=postgresql
DB_HOST=postgresql://smartapp_user:your_password@dpg-abc123-a.oregon-postgres.render.com:5432/members_system
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
- Make sure your PostgreSQL service is running before deploying the web service
- The database will be automatically initialized when the web service starts
- Default admin login: username `admin`, password `123` (change this after first login)

## Troubleshooting

If you get connection errors:
1. Check that the PostgreSQL service is running
2. Verify the Internal Database URL is correct
3. Ensure all environment variables are set
4. Check the web service logs for detailed error messages
