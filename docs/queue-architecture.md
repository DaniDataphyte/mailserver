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

Cloudways' Supervisor UI accepts a single queue name per job, so configure separate jobs via
**Application → Supervisor Jobs** instead of relying on comma-separated queue lists.

### Recommended lean setup for a 1 GB server

### Job 1 — Campaign Dispatch
- Queue: `campaigns`
- Processes: `1`
- Timeout: `660`
- Sleep Time: `3`
- Tries: `3`

### Job 2 — Email Sending
- Queue: `emails`
- Processes: `2`
- Timeout: `90`
- Sleep Time: `3`
- Tries: `3`

### Job 3 — Webhook Processing
- Queue: `webhooks`
- Processes: `1`
- Timeout: `60`
- Sleep Time: `3`
- Tries: `3`

### Job 4 — Default Queue
- Queue: `default`
- Processes: `1`
- Timeout: `60`
- Sleep Time: `3`
- Tries: `3`

If the server is under pressure, reduce `emails` to `1` process temporarily. If a large campaign
needs more throughput, increase only the `emails` job and scale it back down afterward.

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
        'supervisor-campaigns' => [
            'connection' => 'redis',
            'queue'      => ['campaigns'],
            'balance'    => 'simple',
            'processes'  => 1,
            'tries'      => 3,
            'timeout'    => 660,
        ],
        'supervisor-emails' => [
            'connection'   => 'redis',
            'queue'        => ['emails'],
            'balance'      => 'auto',
            'minProcesses' => 1,
            'maxProcesses' => 2,
            'tries'        => 3,
            'timeout'      => 90,
        ],
        'supervisor-tracking' => [
            'connection' => 'redis',
            'queue'      => ['webhooks', 'tracking', 'default'],
            'balance'    => 'simple',
            'processes'  => 1,
            'tries'      => 3,
            'timeout'    => 60,
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
