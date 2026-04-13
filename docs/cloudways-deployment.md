# Cloudways VPS Deployment

---

## Server Setup

### Required Packages (enable in Server > Settings & Packages > Packages)
- **Redis** - queue backend and caching
- **Supervisord** - queue worker process management

### PHP Configuration (Server > Settings & Packages > PHP)
- `max_execution_time` = 120
- `memory_limit` = 256M
- `upload_max_filesize` = 10M (for CSV imports)
- Enable `pcntl` extension if available (Horizon signal handling)

---

## Application Settings

### Environment Variables
Set in Application > Application Settings > Environment Variables, or edit `.env` directly via SSH.

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

MAIL_MAILER=elasticemail
ELASTIC_EMAIL_API_KEY=your-api-key

# Newsletter specific
NEWSLETTER_FROM_NAME="Your Organization"
NEWSLETTER_FROM_EMAIL=newsletter@yourdomain.com
NEWSLETTER_REPLY_TO=hello@yourdomain.com
NEWSLETTER_PHYSICAL_ADDRESS="123 Street, City, Country"
```

---

## Cron Job

Application > Cron Job Management > Advanced tab:

```
* * * * * php /home/master/applications/{app_folder}/public_html/artisan schedule:run >> /dev/null 2>&1
```

Replace `{app_folder}` with your Cloudways application folder name.

---

## Supervisor Jobs

Application > Application Settings > Supervisor Jobs:

### Job 1: High Priority Queues
- **Command:** `php artisan queue:work redis --queue=campaigns,webhooks --sleep=3 --tries=3 --timeout=600`
- **Processes:** 1
- **Auto-restart:** Yes

### Job 2: Email Sending
- **Command:** `php artisan queue:work redis --queue=emails --sleep=3 --tries=3 --timeout=30`
- **Processes:** 3
- **Auto-restart:** Yes

### Job 3: Low Priority
- **Command:** `php artisan queue:work redis --queue=tracking --sleep=10 --tries=3 --timeout=60`
- **Processes:** 1
- **Auto-restart:** Yes

---

## Deployment Checklist

### First Deploy
1. Push code to git repository
2. Connect Cloudways to git repo (Application > Deployment via Git)
3. SSH into server and run:
   ```bash
   cd /home/master/applications/{app_folder}/public_html
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate
   php artisan db:seed --class=SubscriberGroupSeeder
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan statamic:stache:warm
   ```
4. Set up SSL (Application > SSL Certificate > Let's Encrypt)
5. Configure DNS to point to Cloudways IP
6. Set up DNS records (SPF, DKIM, DMARC)
7. Verify domain in Elastic Email
8. Test webhook endpoint is accessible
9. Send test campaign

### Subsequent Deploys
```bash
cd /home/master/applications/{app_folder}/public_html
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

---

## SSL/HTTPS
- Use Cloudways built-in Let's Encrypt SSL
- Application > SSL Certificate > Let's Encrypt
- Enable "Force HTTPS Redirect"
- Required for webhook endpoint security

---

## Monitoring

### Webhook Endpoint
- Set up UptimeRobot or similar to monitor `https://yourdomain.com/webhooks/elastic-email`
- Alert if endpoint goes down (Elastic Email disables webhooks after 1000 consecutive failures)

### Queue Health
- Monitor via `php artisan queue:failed` (SSH)
- Or Horizon dashboard at `/horizon` (if using Horizon)
- Set up alerts for failed job count exceeding threshold

### Server Resources
- Cloudways provides built-in monitoring (Server > Monitoring)
- Watch: CPU, RAM, disk usage
- Scale vertically if queue processing is slow

---

## Backups

### Database
- Cloudways automated backups (Server > Backups)
- Set frequency: daily
- Retention: 7 days minimum

### Application Files
- Covered by Cloudways backup
- Additionally: `spatie/laravel-backup` for application-level backups
- Store off-site (S3, Google Cloud Storage)

### Elastic Email
- Periodically export subscriber lists from Elastic Email as backup
- Keep local DB as source of truth
