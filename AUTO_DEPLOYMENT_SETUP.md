# Auto-Deployment Setup Guide for SmartApp

## Overview
This guide will help you set up automatic deployment for your SmartApp using GitHub Actions and Render's auto-deployment features.

## Prerequisites
1. GitHub repository with your SmartApp code
2. Render.com account
3. GitHub Actions enabled for your repository

## Auto-Deployment Methods

### Method 1: Render Auto-Deploy (Recommended)

#### Step 1: Enable Auto-Deploy in Render
1. Go to your Render dashboard
2. Select your web service
3. Go to Settings → Auto-Deploy
4. Enable "Auto-Deploy from Git"
5. Select your GitHub repository and branch (main/master)

#### Step 2: Configure Environment Variables
Set these in your Render service:
```
POSTGRES_PASSWORD=your-strong-password
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-gmail-app-password
SMTP_FROM_EMAIL=your-email@gmail.com
SMTP_FROM_NAME=SmartApp
WEBHOOK_SECRET=your-webhook-secret
```

#### Step 3: Test Auto-Deployment
1. Make a small change to your code
2. Commit and push to main/master branch
3. Watch the deployment in Render dashboard
4. Check the health endpoint: `https://your-app.onrender.com/health.php`

### Method 2: GitHub Actions + Render API

#### Step 1: Get Render API Key
1. Go to Render dashboard
2. Go to Account Settings → API Keys
3. Create a new API key
4. Copy the key (you'll need it for GitHub secrets)

#### Step 2: Get Service ID
1. Go to your web service in Render
2. Copy the Service ID from the URL or settings

#### Step 3: Set GitHub Secrets
In your GitHub repository:
1. Go to Settings → Secrets and variables → Actions
2. Add these secrets:
   - `RENDER_API_KEY`: Your Render API key
   - `RENDER_SERVICE_ID`: Your service ID

#### Step 4: Test GitHub Actions
1. Make a change to your code
2. Commit and push to main/master branch
3. Go to Actions tab in GitHub
4. Watch the deployment workflow

### Method 3: Webhook-Based Deployment

#### Step 1: Set Up Webhook Secret
Add to your environment variables:
```
WEBHOOK_SECRET=your-secure-webhook-secret
```

#### Step 2: Configure GitHub Webhook
1. Go to your GitHub repository
2. Settings → Webhooks → Add webhook
3. Payload URL: `https://your-app.onrender.com/webhook_deploy.php`
4. Content type: `application/json`
5. Secret: Your webhook secret
6. Events: Just the push event
7. Active: Checked

#### Step 3: Test Webhook
1. Make a change to your code
2. Commit and push to main/master branch
3. Check webhook deliveries in GitHub
4. Check application logs in Render

## Monitoring and Health Checks

### Health Check Endpoint
Your app includes a health check endpoint at `/health.php` that monitors:
- Database connectivity
- Required tables existence
- File system permissions
- PHP extensions
- Environment variables

### Monitoring Setup
1. **Render Dashboard**: Monitor service status and logs
2. **Health Endpoint**: `https://your-app.onrender.com/health.php`
3. **GitHub Actions**: Monitor deployment status
4. **Webhook Logs**: Check webhook deliveries

## Deployment Configuration Files

### Key Files for Auto-Deployment
- `render.yaml`: Render service configuration with auto-deploy enabled
- `.github/workflows/deploy.yml`: GitHub Actions workflow
- `health.php`: Health check endpoint
- `webhook_deploy.php`: Webhook endpoint for deployments
- `start.sh`: Startup script with database initialization
- `render_deploy.php`: Database initialization script

### Environment Variables
```bash
# Database (auto-configured by Render)
DB_TYPE=postgresql
DB_HOST=from-database
DB_NAME=from-database
DB_USERNAME=from-database
DB_PASSWORD=from-database

# Email Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_FROM_EMAIL=your-email@gmail.com
SMTP_FROM_NAME=SmartApp

# Deployment
DEPLOYMENT_ENV=production
WEBHOOK_SECRET=your-webhook-secret
```

## Troubleshooting

### Common Issues

#### 1. Auto-Deploy Not Triggering
- Check if auto-deploy is enabled in Render
- Verify GitHub repository connection
- Check branch configuration (main vs master)
- Review Render logs for errors

#### 2. GitHub Actions Failing
- Verify API key and service ID secrets
- Check workflow permissions
- Review GitHub Actions logs
- Ensure repository has Actions enabled

#### 3. Health Check Failing
- Check database connectivity
- Verify environment variables
- Review application logs
- Test health endpoint manually

#### 4. Webhook Not Working
- Verify webhook URL is correct
- Check webhook secret configuration
- Review webhook deliveries in GitHub
- Check application logs for webhook requests

### Debug Commands

```bash
# Test health endpoint
curl https://your-app.onrender.com/health.php

# Test webhook endpoint
curl -X POST https://your-app.onrender.com/webhook_deploy.php \
  -H "Content-Type: application/json" \
  -d '{"ref":"refs/heads/main","repository":{"full_name":"user/repo"}}'

# Check Render service status
curl -H "Authorization: Bearer YOUR_API_KEY" \
  https://api.render.com/v1/services/YOUR_SERVICE_ID
```

## Best Practices

### 1. Branch Protection
- Protect main/master branch
- Require pull request reviews
- Require status checks to pass

### 2. Environment Management
- Use different environments for staging/production
- Never commit secrets to repository
- Use environment variables for configuration

### 3. Monitoring
- Set up alerts for deployment failures
- Monitor application health regularly
- Keep deployment logs for debugging

### 4. Security
- Use strong webhook secrets
- Rotate API keys regularly
- Monitor for unauthorized deployments

## Rollback Procedures

### Automatic Rollback
Render automatically rolls back if:
- Health checks fail
- Application crashes on startup
- Database migration fails

### Manual Rollback
1. Go to Render dashboard
2. Select your service
3. Go to Deploys tab
4. Click "Rollback" on previous deployment

### Emergency Rollback
1. Revert the problematic commit
2. Push to main/master branch
3. Monitor deployment status
4. Verify application functionality

## Support and Resources

- [Render Documentation](https://render.com/docs)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [SmartApp Health Check](https://your-app.onrender.com/health.php)
- [Render Dashboard](https://dashboard.render.com)

---

**Need Help?**
- Check the health endpoint first
- Review Render and GitHub logs
- Test locally before deploying
- Use the troubleshooting section above
