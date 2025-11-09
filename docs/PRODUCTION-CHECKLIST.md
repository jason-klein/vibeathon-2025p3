# ReferHarmony Production Deployment Checklist

Use this checklist to ensure a smooth production deployment.

## Pre-Deployment

### Code Quality
- [ ] All tests passing (`php artisan test`)
  - Expected: 142 tests, 408 assertions
- [ ] Code formatted with Pint (`vendor/bin/pint`)
- [ ] Frontend assets built (`npm run build`)
- [ ] No console errors in browser (check with browser dev tools)
- [ ] Git repository clean, all changes committed

### Security Review
- [ ] `APP_DEBUG=false` in production `.env`
- [ ] `APP_ENV=production` in production `.env`
- [ ] Strong, unique `APP_KEY` generated
- [ ] CSRF protection working on all forms
- [ ] XSS protection verified (Blade escaping)
- [ ] File upload validation in place
- [ ] SQL injection protection verified (using Eloquent)
- [ ] Authorization policies implemented and tested
- [ ] Rate limiting configured for authentication

### Environment Configuration
- [ ] All required environment variables documented in `.env.example`
- [ ] Production `.env` file prepared (DO NOT commit to version control)
- [ ] `APP_URL` set to production domain
- [ ] Database credentials configured
- [ ] Mail service configured and tested
- [ ] OpenAI API key configured (for AI features)
- [ ] Log level set appropriately (`LOG_LEVEL=error` or `warning`)
- [ ] File storage configured (local or S3)

### Testing
- [ ] User registration flow tested
- [ ] User login flow tested
- [ ] Password reset flow tested
- [ ] Appointment creation tested
- [ ] Document upload tested
- [ ] Task management tested
- [ ] Scheduling workflow tested
- [ ] Timeline page tested
- [ ] Mobile responsiveness verified
- [ ] Cross-browser testing completed (Chrome, Firefox, Safari)

## Deployment to Laravel Cloud

### Initial Setup
- [ ] Laravel Cloud account created
- [ ] Project created in Laravel Cloud
- [ ] Git repository connected
- [ ] Production branch selected (typically `main`)
- [ ] Build command configured: `npm ci && npm run build`
- [ ] Deploy command configured: `php artisan migrate --force && php artisan storage:link`

### Environment Variables
Copy these variables to Laravel Cloud Environment settings:

#### Critical Settings
- [ ] `APP_NAME=ReferHarmony`
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL=https://your-domain.com`
- [ ] `APP_KEY` (generate in Laravel Cloud or locally with `php artisan key:generate`)

#### Logging
- [ ] `LOG_CHANNEL=production`
- [ ] `LOG_LEVEL=error`
- [ ] `LOG_DAILY_DAYS=14`
- [ ] `LOG_SLACK_WEBHOOK_URL` (optional, for alerts)

#### Database
**For SQLite (MVP):**
- [ ] `DB_CONNECTION=sqlite`

**For MySQL/PostgreSQL (Recommended):**
- [ ] `DB_CONNECTION=mysql` (or `pgsql`)
- [ ] `DB_HOST=`
- [ ] `DB_PORT=`
- [ ] `DB_DATABASE=`
- [ ] `DB_USERNAME=`
- [ ] `DB_PASSWORD=`

#### Email Service
Choose one:

**SMTP:**
- [ ] `MAIL_MAILER=smtp`
- [ ] `MAIL_HOST=`
- [ ] `MAIL_PORT=`
- [ ] `MAIL_USERNAME=`
- [ ] `MAIL_PASSWORD=`
- [ ] `MAIL_ENCRYPTION=tls`

**Mailgun:**
- [ ] `MAIL_MAILER=mailgun`
- [ ] `MAILGUN_DOMAIN=`
- [ ] `MAILGUN_SECRET=`

**AWS SES:**
- [ ] `MAIL_MAILER=ses`
- [ ] `AWS_ACCESS_KEY_ID=`
- [ ] `AWS_SECRET_ACCESS_KEY=`
- [ ] `AWS_DEFAULT_REGION=`

**Postmark:**
- [ ] `MAIL_MAILER=postmark`
- [ ] `POSTMARK_TOKEN=`

**Always set:**
- [ ] `MAIL_FROM_ADDRESS=noreply@referharmony.com`
- [ ] `MAIL_FROM_NAME=ReferHarmony`

#### File Storage
**For S3 (Recommended):**
- [ ] `FILESYSTEM_DISK=s3`
- [ ] `AWS_ACCESS_KEY_ID=`
- [ ] `AWS_SECRET_ACCESS_KEY=`
- [ ] `AWS_DEFAULT_REGION=`
- [ ] `AWS_BUCKET=`

**For Local (Simple):**
- [ ] `FILESYSTEM_DISK=local`

#### Third-Party Services
- [ ] `OPENAI_API_KEY=` (required for AI features)

#### Performance (Optional but Recommended)
- [ ] `CACHE_STORE=redis`
- [ ] `SESSION_DRIVER=redis`
- [ ] `QUEUE_CONNECTION=redis`
- [ ] `REDIS_HOST=`
- [ ] `REDIS_PASSWORD=`
- [ ] `REDIS_PORT=6379`

### Database Setup
- [ ] Database provisioned (if using MySQL/PostgreSQL)
- [ ] Migrations will run automatically on deploy
- [ ] Seeders planned (optional):
  - `php artisan db:seed --class=CommunityPartnerSeeder`
  - `php artisan db:seed --class=HealthcareSystemSeeder`
  - `php artisan db:seed --class=HealthcareProviderSeeder`

### File Storage Setup
**If using S3:**
- [ ] S3 bucket created
- [ ] IAM user created with S3 access
- [ ] Access key and secret key generated
- [ ] Bucket policy configured
- [ ] CORS configured (if needed)

### Deploy
- [ ] Click "Deploy" in Laravel Cloud dashboard
- [ ] Monitor deployment logs for errors
- [ ] Wait for deployment to complete

## Post-Deployment Verification

### Immediate Checks
- [ ] Application URL loads successfully
- [ ] HTTPS working correctly
- [ ] Homepage displays correctly
- [ ] No visible errors on homepage

### Authentication Flow
- [ ] Can access registration page
- [ ] Can register new user
- [ ] Patient record auto-created on registration
- [ ] Can log in with new account
- [ ] Can access dashboard
- [ ] Can log out
- [ ] Password reset email received
- [ ] Password reset flow works

### Core Features
- [ ] Can create future appointment
- [ ] Can create past appointment
- [ ] Can upload document to appointment
- [ ] Document can be downloaded
- [ ] Can create task
- [ ] Can mark task complete/incomplete
- [ ] Can link task to appointment
- [ ] Dashboard displays appointments
- [ ] Dashboard displays tasks
- [ ] Dashboard displays events
- [ ] Timeline page loads
- [ ] Scheduling workflow works

### Performance
- [ ] Page load times < 2 seconds
- [ ] No slow database queries
- [ ] Images load quickly
- [ ] CSS/JS assets loading correctly

### Mobile Testing
- [ ] Responsive design works on mobile (320px width)
- [ ] All features accessible on mobile
- [ ] Touch interactions work correctly
- [ ] Forms usable on mobile

### Error Handling
- [ ] 404 page displays correctly
- [ ] 500 errors logged properly
- [ ] Form validation errors display correctly
- [ ] Authorization errors handled gracefully

## Post-Deployment Configuration

### Monitoring Setup
- [ ] Uptime monitoring configured (UptimeRobot, Pingdom, etc.)
- [ ] Error alerting configured (Slack, email, Sentry, etc.)
- [ ] Performance monitoring configured (optional)
- [ ] Log monitoring in place

### Backups
- [ ] Database backup automation configured
- [ ] Backup restore procedure tested
- [ ] File storage backup configured (if not using S3)

### Optimization
- [ ] Configuration cached: `php artisan config:cache`
- [ ] Routes cached: `php artisan route:cache`
- [ ] Views cached: `php artisan view:cache`
- [ ] Composer autoloader optimized: `composer install --optimize-autoloader --no-dev`

### Security
- [ ] SSL/TLS certificate installed and working
- [ ] HSTS headers configured (optional but recommended)
- [ ] Security headers reviewed
- [ ] Rate limiting verified
- [ ] CORS configured (if needed)

### DNS & Domain
- [ ] Domain DNS pointed to Laravel Cloud
- [ ] SSL certificate issued
- [ ] WWW redirect configured (if desired)
- [ ] Email SPF/DKIM records configured

## Documentation

- [ ] Production URL documented
- [ ] Admin credentials stored securely
- [ ] Deployment procedure documented
- [ ] Rollback procedure documented
- [ ] Support contacts documented

## Maintenance

### Regular Tasks
- [ ] Monitor error logs weekly
- [ ] Review application performance monthly
- [ ] Update dependencies quarterly
- [ ] Security audit annually
- [ ] Backup verification monthly
- [ ] Database cleanup as needed (old logs, etc.)

### Emergency Contacts
- [ ] Laravel Cloud support access
- [ ] Email service support contact
- [ ] DNS provider support contact
- [ ] Database provider support contact

## Rollback Plan

In case of deployment issues:

### Immediate Rollback (Laravel Cloud)
1. Navigate to Deployments in Laravel Cloud dashboard
2. Click "Rollback" on previous successful deployment
3. Confirm rollback

### Database Rollback (if needed)
- [ ] Backup procedure documented
- [ ] Restore procedure documented
- [ ] Tested rollback procedure

### Manual Rollback Commands
```bash
# Rollback last migration
php artisan migrate:rollback

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Launch Announcement

- [ ] Internal stakeholders notified
- [ ] Users invited (if applicable)
- [ ] Marketing materials prepared (if applicable)
- [ ] Support documentation available
- [ ] FAQ prepared

## Success Criteria

### Technical
- [x] All tests passing (142 tests)
- [ ] Zero critical errors in first 24 hours
- [ ] 99%+ uptime in first week
- [ ] Page loads < 2 seconds
- [ ] No security vulnerabilities

### User Experience
- [ ] User registration working smoothly
- [ ] Password reset working
- [ ] All core features accessible
- [ ] Mobile experience satisfactory
- [ ] No user-facing errors

---

## Notes

Date deployed: _______________

Deployed by: _______________

Production URL: _______________

Any issues encountered:
_______________________________________
_______________________________________
_______________________________________

Resolutions:
_______________________________________
_______________________________________
_______________________________________

---

**Version:** 1.0
**Last Updated:** 2025-11-09
