# Queue Architecture

---

## Dev vs Production

### Development (Herd)

```env
QUEUE_CONNECTION=sync
```

With `sync`, all jobs run **immediately in-process** — no worker needed. The "Send Now" button waits while emails are sent, then redirects. This is the correct setting for local development and Mailtrap testing.

### Production (Cloudways)

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

With `redis`, jobs are queued in Redis and processed by a Supervisor-managed worker (see below). Emails send asynchronously — the "Send Now" button returns immediately while sending happens in the background.

---

## Queues

| Queue | Job | Priority |
|---|---|---|
| `campaigns` | `DispatchCampaignJob` — fans out to individual send jobs | High |
| `emails` | `SendNewsletterEmailJob` — sends one email per subscriber | Default |
| `webhooks` | `ProcessWebhookJob` — processes Elastic Email event callbacks | High |

---

## Job Definitions

### DispatchCampaignJob — `queue: campaigns`
- **Timeout:** 600 s (10 min — allows large campaigns)
- **Tries:** 1 (failure = investigate manually)
- **Does:** Resolves audience, creates `campaign_sends` rows, dispatches `SendNewsletterEmailJob` per subscriber, sets campaign `status = sent`

### SendNewsletterEmailJob — `queue: emails`
- **Timeout:** 60 s
- **Tries:** 3
- **Backoff:** 60 s, 120 s, 300 s
- **Rate limiting:** `RateLimited('newsletter-emails')` middleware — 15 concurrent max
- **Does:** Sends one email to one subscriber via `NewsletterMailable`, stores Elastic Email transaction ID

### ProcessWebhookJob — `queue: webhooks`
- **Timeout:** 30 s
- **Tries:** 3
- **Does:** Parses Elastic Email webhook events (delivered, opened, clicked, bounced, unsubscribed, complained), updates `campaign_sends` and `subscribers.status`

---

## Stuck Campaign Recovery

If a campaign is stuck in `sending` status (e.g. queue worker stopped, `sync` mode with a crash):

1. Go to Newsletter → Campaigns → open the stuck campaign
2. Click **"Reset to Draft"** in the yellow "Stuck?" card
3. Resend the campaign

This resets `status = draft` and `sent_at = null`. Already-sent `campaign_sends` rows are preserved so duplicate sends don't occur (the job skips `CampaignSend` records that aren't `pending`/`failed`).

---

## Supervisor Configuration (Cloudways Production)

Configure via **Application → Supervisor Jobs** in the Cloudways dashboard, or `supervisord.conf`:

### Worker 1 — High Priority (campaigns + webhooks)
```ini
[program:mailserver-high]
command=php /path/to/artisan queue:work redis --queue=campaigns,webhooks --sleep=3 --tries=3 --timeout=600
autostart=true
autorestart=true
numprocs=1
```

### Workers 2–4 — Email Sending (3 parallel workers)
```ini
[program:mailserver-emails]
command=php /path/to/artisan queue:work redis --queue=emails --sleep=3 --tries=3 --timeout=60
autostart=true
autorestart=true
numprocs=3
```

> On Cloudways, set the **"Processes"** count to 3 for the emails worker to get 3 parallel senders.

---

## Laravel Horizon (Optional Enhancement)

For a dashboard UI with queue metrics:

```bash
composer require laravel/horizon
php artisan horizon:install
```

Dashboard available at `/horizon`. Auto-balances workers based on queue depth.

```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-high' => [
            'connection' => 'redis',
            'queue'      => ['campaigns', 'webhooks'],
            'balance'    => 'simple',
            'processes'  => 2,
            'tries'      => 3,
            'timeout'    => 600,
        ],
        'supervisor-emails' => [
            'connection'   => 'redis',
            'queue'        => ['emails'],
            'balance'      => 'auto',
            'minProcesses' => 2,
            'maxProcesses' => 5,
            'tries'        => 3,
            'timeout'      => 60,
        ],
    ],
],
```

---

## Failure Handling

### Failed Jobs
Failed jobs (after all retries exhausted) go to the `failed_jobs` table.

```bash
# View failed jobs
php artisan queue:failed

# Retry a specific failed job
php artisan queue:retry {id}

# Retry all failed jobs
php artisan queue:retry all

# Prune old failures (keep 7 days)
php artisan queue:prune-failed --hours=168
```

### Scheduled Cleanup
Add to `app/Console/Kernel.php` scheduler:
```php
$schedule->command('queue:prune-failed --hours=168')->weekly();
```

---

## Rate Limiter Registration

The `newsletter-emails` rate limiter is registered in `AppServiceProvider` (or `NewsletterServiceProvider`):

```php
RateLimiter::for('newsletter-emails', function ($job) {
    return Limit::perMinute(15);
});
```

This caps `SendNewsletterEmailJob` at 15 concurrent executions, staying within Elastic Email's fair-use limit.
