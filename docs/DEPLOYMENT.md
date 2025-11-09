# ReferHarmony Deployment Guide

This guide provides comprehensive instructions for deploying ReferHarmony to production.

## Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Environment Configuration](#environment-configuration)
3. [Laravel Cloud Deployment](#laravel-cloud-deployment)
4. [Database Setup](#database-setup)
5. [File Storage Configuration](#file-storage-configuration)
6. [Email Configuration](#email-configuration)
7. [Error Monitoring](#error-monitoring)
8. [Post-Deployment Steps](#post-deployment-steps)
9. [Rollback Procedure](#rollback-procedure)
10. [Troubleshooting](#troubleshooting)

---

## Pre-Deployment Checklist

Before deploying to production, ensure you have completed the following:

- [ ] All tests passing (`php artisan test`)
- [ ] Code formatted with Laravel Pint (`vendor/bin/pint`)
- [ ] Frontend build completed (`npm run build`)
- [ ] `.env.example` is up to date with all required variables
- [ ] Database migrations reviewed and tested
- [ ] Security audit completed (CSRF, XSS, SQL injection prevention)
- [ ] Performance testing completed (page loads < 2 seconds)
- [ ] Mobile responsiveness verified
- [ ] Cross-browser compatibility tested (Chrome, Firefox, Safari)

---

## Environment Configuration

### Required Environment Variables

Copy `.env.example` to `.env` and configure the following variables for production:

#### Application Settings

```bash
APP_NAME=ReferHarmony
APP_ENV=production
APP_KEY=base64:... # Generate with: php artisan key:generate
APP_DEBUG=false
APP_URL=https://your-domain.com
```

**CRITICAL:** Ensure `APP_DEBUG=false` in production to prevent sensitive information leakage.

#### Logging

```bash
LOG_CHANNEL=production
LOG_LEVEL=error
LOG_DAILY_DAYS=14
```

For production error alerts via Slack (optional):

```bash
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
LOG_SLACK_USERNAME="ReferHarmony Alerts"
LOG_SLACK_EMOJI=":warning:"
```

#### Database

For production, you have two options:

**Option 1: SQLite (Simple, included in MVP)**

```bash
DB_CONNECTION=sqlite
```

Ensure the SQLite database file exists at `database/database.sqlite`.

**Option 2: MySQL/PostgreSQL (Recommended for scale)**

```bash
DB_CONNECTION=mysql
DB_HOST=your-database-host
DB_PORT=3306
DB_DATABASE=referharmony
DB_USERNAME=your-username
DB_PASSWORD=your-secure-password
```

#### Email Configuration

Choose one of the following email services for password resets and notifications:

**SMTP (Generic)**

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@referharmony.com"
MAIL_FROM_NAME="ReferHarmony"
```

**Mailgun**

```bash
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain.mailgun.org
MAILGUN_SECRET=your-mailgun-secret
MAILGUN_ENDPOINT=api.mailgun.net
MAIL_FROM_ADDRESS="noreply@referharmony.com"
MAIL_FROM_NAME="ReferHarmony"
```

**AWS SES**

```bash
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret
AWS_DEFAULT_REGION=us-east-1
MAIL_FROM_ADDRESS="noreply@referharmony.com"
MAIL_FROM_NAME="ReferHarmony"
```

**Postmark**

```bash
MAIL_MAILER=postmark
POSTMARK_TOKEN=your-postmark-token
MAIL_FROM_ADDRESS="noreply@referharmony.com"
MAIL_FROM_NAME="ReferHarmony"
```

#### File Storage

For production scalability, use S3 for file uploads:

```bash
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=referharmony-documents
AWS_USE_PATH_STYLE_ENDPOINT=false
```

Alternatively, use local storage (not recommended for multi-server deployments):

```bash
FILESYSTEM_DISK=local
```

#### Third-Party Services

```bash
OPENAI_API_KEY=sk-... # Required for AI summary generation
```

Get your OpenAI API key at: https://platform.openai.com/api-keys

#### Performance Optimization (Optional)

For better performance, configure Redis for caching and sessions:

```bash
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password
REDIS_PORT=6379
```

---

## Laravel Cloud Deployment

### Initial Setup

1. **Create Laravel Cloud Account**
   - Visit https://cloud.laravel.com
   - Create a new project for ReferHarmony

2. **Connect Repository**
   - Link your Git repository (GitHub, GitLab, or Bitbucket)
   - Select the `main` branch for production deployments

3. **Configure Environment Variables**
   - In Laravel Cloud dashboard, navigate to Environment > Environment Variables
   - Add all production environment variables from the section above
   - **Important:** Do not commit `.env` to version control

4. **Configure Deployment Settings**
   - Build Command: `npm ci && npm run build`
   - Deploy Command: `php artisan migrate --force`

5. **Set Up Database**
   - If using MySQL/PostgreSQL, provision a database through Laravel Cloud
   - Note the connection credentials and add to environment variables

6. **Configure Storage**
   - If using S3, create an S3 bucket and configure IAM credentials
   - Run: `php artisan storage:link` during deployment

7. **Deploy**
   - Click "Deploy" in the Laravel Cloud dashboard
   - Monitor deployment logs for any errors

### Deployment Commands

These commands run automatically during deployment:

```bash
# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci

# Build frontend assets
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
php artisan migrate --force

# Create storage symlink
php artisan storage:link
```

---

## Database Setup

### SQLite Production Setup

If using SQLite for production:

```bash
# Ensure database file exists
touch database/database.sqlite

# Set proper permissions
chmod 644 database/database.sqlite
chmod 755 database

# Run migrations
php artisan migrate --force
```

### MySQL/PostgreSQL Production Setup

```bash
# Run migrations
php artisan migrate --force

# Seed initial data (if needed)
php artisan db:seed --force --class=CommunityPartnerSeeder
php artisan db:seed --force --class=HealthcareSystemSeeder
php artisan db:seed --force --class=HealthcareProviderSeeder
```

### Database Backups

**For SQLite:**

```bash
# Backup command
cp database/database.sqlite database/backups/database-$(date +%Y%m%d).sqlite

# Restore command
cp database/backups/database-YYYYMMDD.sqlite database/database.sqlite
```

**For MySQL/PostgreSQL:**

Configure automated backups through your hosting provider or Laravel Cloud.

---

## File Storage Configuration

### Local Storage Setup

```bash
# Create symlink for public access
php artisan storage:link

# Set proper permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### S3 Storage Setup

1. **Create S3 Bucket**
   - Log in to AWS Console
   - Create a new S3 bucket (e.g., `referharmony-documents`)
   - Set bucket policy for private access

2. **Create IAM User**
   - Create IAM user with S3 access
   - Generate access key and secret key
   - Add to environment variables

3. **Configure CORS (if needed)**

```json
[
  {
    "AllowedHeaders": ["*"],
    "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
    "AllowedOrigins": ["https://your-domain.com"],
    "ExposeHeaders": []
  }
]
```

4. **Test Upload**

```bash
php artisan tinker
>>> Storage::disk('s3')->put('test.txt', 'Hello World');
>>> Storage::disk('s3')->exists('test.txt');
```

---

## Email Configuration

### Testing Email Configuration

After configuring email settings, test password reset emails:

```bash
php artisan tinker
>>> $user = App\Models\User::first();
>>> Password::sendResetLink(['email' => $user->email]);
```

Check that the email is received correctly.

### Email Best Practices

1. **SPF/DKIM/DMARC**: Configure DNS records for better deliverability
2. **From Address**: Use a real domain you own (not @gmail.com)
3. **Testing**: Test password reset flow before going live
4. **Rate Limiting**: Ensure rate limiting is configured (Laravel default: 6 attempts per minute)

---

## Error Monitoring

### Laravel Logs

Production logs are stored in `storage/logs/laravel.log` with daily rotation.

View recent errors:

```bash
tail -n 100 storage/logs/laravel.log
```

### Slack Alerts (Optional)

Configure Slack webhook for critical error notifications:

```bash
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
```

Errors at `critical` level and above will be sent to Slack.

### Third-Party Monitoring (Recommended)

Consider integrating:
- **Sentry** for error tracking
- **New Relic** for performance monitoring
- **Laravel Telescope** for debugging (development only)

---

## Post-Deployment Steps

### 1. Verify Deployment

- [ ] Visit your production URL and verify homepage loads
- [ ] Test user registration
- [ ] Test user login
- [ ] Test password reset flow
- [ ] Create a test appointment
- [ ] Upload a test document
- [ ] Create a test task
- [ ] Verify dashboard displays correctly
- [ ] Test scheduling workflow
- [ ] Verify timeline page
- [ ] Check event feed

### 2. Performance Optimization

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### 3. Security Verification

- [ ] Ensure HTTPS is enabled
- [ ] Verify APP_DEBUG=false
- [ ] Check CSRF protection is working
- [ ] Test XSS protection (Blade escaping)
- [ ] Verify file upload validation
- [ ] Test authorization policies
- [ ] Check rate limiting on login/registration

### 4. Set Up Monitoring

- [ ] Configure uptime monitoring (e.g., UptimeRobot, Pingdom)
- [ ] Set up error alerting (Slack, email)
- [ ] Configure performance monitoring
- [ ] Set up database backup automation

### 5. Create Admin Access

Create your first admin/test user:

```bash
php artisan tinker
>>> $user = User::create(['name' => 'Test User', 'email' => 'test@example.com', 'password' => bcrypt('password')]);
>>> $user->patient()->create(['summary' => 'Test patient', 'latitude' => 37.0842, 'longitude' => -94.5133]);
```

---

## Rollback Procedure

If deployment fails or critical issues are discovered:

### Immediate Rollback

In Laravel Cloud dashboard:
1. Navigate to Deployments
2. Click "Rollback" on the previous successful deployment
3. Confirm rollback

### Manual Rollback

```bash
# Rollback last migration
php artisan migrate:rollback

# Rollback to specific batch
php artisan migrate:rollback --batch=X

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Database Rollback

**SQLite:**

```bash
cp database/backups/database-YYYYMMDD.sqlite database/database.sqlite
```

**MySQL/PostgreSQL:**

Restore from automated backup through your hosting provider.

---

## Troubleshooting

### Common Issues

#### 1. 500 Internal Server Error

**Possible Causes:**
- `APP_KEY` not set
- File permissions incorrect
- `.env` file missing

**Solutions:**

```bash
# Generate app key
php artisan key:generate

# Fix permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Check error logs
tail -n 50 storage/logs/laravel.log
```

#### 2. Database Connection Errors

**Check:**
- Database credentials in `.env`
- Database server is running
- Firewall rules allow connection

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

#### 3. Email Not Sending

**Check:**
- MAIL_* environment variables are correct
- SMTP credentials are valid
- Firewall allows SMTP ports (587, 465)

```bash
# Test email
php artisan tinker
>>> Mail::raw('Test email', function($msg) { $msg->to('test@example.com')->subject('Test'); });
```

#### 4. File Upload Errors

**Check:**
- Storage permissions (`chmod -R 775 storage`)
- Storage symlink exists (`php artisan storage:link`)
- If using S3, credentials are correct

```bash
# Test storage
php artisan tinker
>>> Storage::disk('local')->put('test.txt', 'test');
>>> Storage::disk('local')->exists('test.txt');
```

#### 5. CSS/JS Not Loading

**Possible Causes:**
- Assets not built for production
- Manifest file missing

**Solutions:**

```bash
# Rebuild assets
npm run build

# Clear view cache
php artisan view:clear

# Check public/build directory exists
ls -la public/build
```

#### 6. OpenAI API Errors

**Check:**
- `OPENAI_API_KEY` is set correctly
- API key has sufficient credits
- Rate limits not exceeded

```bash
# Test OpenAI connection
php artisan tinker
>>> $client = OpenAI::client(config('services.openai.key'));
>>> $response = $client->chat()->create(['model' => 'gpt-4', 'messages' => [['role' => 'user', 'content' => 'Hello']]]);
```

---

## Maintenance Mode

Enable maintenance mode during deployments or updates:

```bash
# Enable maintenance mode
php artisan down --message="We're performing scheduled maintenance. We'll be back soon!"

# Disable maintenance mode
php artisan up
```

Allow specific IPs to access during maintenance:

```bash
php artisan down --secret="your-secret-token"
# Access via: https://your-domain.com/your-secret-token
```

---

## Performance Monitoring

### Key Metrics to Monitor

1. **Application Performance**
   - Average page load time (target: < 2 seconds)
   - Database query performance
   - API response times (OpenAI calls)

2. **Server Resources**
   - CPU usage
   - Memory usage
   - Disk space

3. **User Metrics**
   - Daily/weekly active users
   - Registration rate
   - Task completion rate
   - Error rate

### Optimization Tips

1. **Database Optimization**
   - Add indexes for frequently queried columns
   - Use eager loading to prevent N+1 queries
   - Consider database connection pooling

2. **Caching**
   - Use Redis for session and cache storage
   - Cache expensive computations
   - Use HTTP caching headers

3. **Asset Optimization**
   - Enable Gzip compression
   - Use CDN for static assets
   - Optimize images

---

## Security Best Practices

1. **Keep Dependencies Updated**

```bash
composer update
npm update
```

2. **Regular Security Audits**

```bash
# Check for vulnerabilities
composer audit
npm audit
```

3. **SSL/TLS Configuration**
   - Enforce HTTPS
   - Use strong TLS versions (1.2+)
   - Configure HSTS headers

4. **Rate Limiting**
   - Configured by default in Laravel
   - Login: 5 attempts per minute
   - Password reset: 6 attempts per minute

5. **File Upload Security**
   - Validate file types
   - Scan uploaded files (consider antivirus integration)
   - Store files outside public directory

6. **Database Security**
   - Use strong database passwords
   - Limit database user permissions
   - Enable SSL connections

---

## Support and Resources

### Laravel Documentation
- Laravel 12: https://laravel.com/docs/12.x
- Laravel Cloud: https://cloud.laravel.com/docs
- Livewire: https://livewire.laravel.com
- Flux UI: https://fluxui.dev

### Getting Help
- Check application logs: `storage/logs/laravel.log`
- Use Laravel Boost tools (in development): `list-artisan-commands`, `last-error`, `database-schema`
- Laravel community: https://laracasts.com/discuss

---

## Changelog

**Version 1.0** (2025-11-09)
- Initial deployment guide
- Laravel Cloud deployment instructions
- Environment configuration
- Database setup
- File storage configuration
- Email configuration
- Error monitoring setup
- Troubleshooting guide

---

**Last Updated:** 2025-11-09
**Version:** 1.0
**Status:** Ready for Production Deployment
