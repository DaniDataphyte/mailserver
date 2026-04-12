# Queue Architecture

Redis-backed queues managed by Laravel's queue system with Supervisor on Cloudways.

---

## Queue Configuration

### `.env`
```
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

### Queues

| Queue | Purpose | Workers | Priority |
|---|---|---|---|
| `campaigns` | DispatchCampaignJob (fans out emails) | 1 | High |
| `emails` | SendNewsletterEmailJob (individual sends) | 3 | Default |
| `webhooks` | ProcessWebhookJob (Elastic Email events) | 1 | High |
| `tracking` | BatchTrackingUpdateJob (aggregate DB writes) | 1 | Low |

---

## Job Definitions

### DispatchCampaignJob
- **Queue:** `campaigns`
- **Timeout:** 600 seconds (10 min, large campaigns)
- **Retries:** 1 (critical failure = investigate manually)
- **Does:** Resolves audience, creates campaign_sends rows, dispatches individual SendNewsletterEmailJob

### SendNewsletterEmailJob
- **Queue:** `emails`
- **Timeout:** 30 seconds
- **Retries:** 3
- **Backoff:** 60 seconds between retries
- **Rate limit:** 15 concurrent (via spatie/laravel-rate-limited-job-middleware)
- **Does:** Sends one email, updates campaign_send status

### ProcessWebhookJob
- **Queue:** `webhooks`
- **Timeout:** 30 seconds
- **Retries:** 3
- **Does:** Parses Elastic Email webhook payload, updates campaign_sends and subscriber status

### BatchTrackingUpdateJob
- **Queue:** `tracking`
- **Timeout:** 60 seconds
- **Retries:** 3
- **Does:** Aggregates open/click events, batch-writes to DB to reduce load

---

## Supervisor Configuration (Cloudways)

Configure via Application > Supervisor Jobs in Cloudways dashboard.

### Worker 1: High Priority
```
php artisan queue:work redis --queue=campaigns,webhooks --sleep=3 --tries=3 --timeout=600
```

### Worker 2-4: Email Sending (3 workers)
```
php artisan queue:work redis --queue=emails --sleep=3 --tries=3 --timeout=30
```

### Worker 5: Low Priority
```
php artisan queue:work redis --queue=tracking --sleep=10 --tries=3 --timeout=60
```

---

## Laravel Horizon (Alternative)

If Cloudways allows custom Supervisor commands (may require Advanced support plan):

```
composer require laravel/horizon
php artisan horizon:install
```

### `config/horizon.php`
```php
'environments' => [
    'production' => [
        'supervisor-campaigns' => [
            'connection' => 'redis',
            'queue' => ['campaigns', 'webhooks'],
            'balance' => 'simple',
            'processes' => 2,
            'tries' => 3,
            'timeout' => 600,
        ],
        'supervisor-emails' => [
            'connection' => 'redis',
            'queue' => ['emails'],
            'balance' => 'auto',
            'minProcesses' => 2,
            'maxProcesses' => 5,
            'tries' => 3,
            'timeout' => 30,
        ],
        'supervisor-tracking' => [
            'connection' => 'redis',
            'queue' => ['tracking'],
            'balance' => 'simple',
            'processes' => 1,
            'tries' => 3,
            'timeout' => 60,
        ],
    ],
],
```

Benefits: Dashboard UI at `/horizon`, auto-balancing, metrics, failed job management.

---

## Failure Handling

### Retry Strategy
- Failed jobs go to `failed_jobs` table after exhausting retries
- Daily cleanup: `queue:prune-failed --hours=168` (7 days)
- Monitor via `php artisan queue:failed` or Horizon dashboard

### Dead Letter Alerting
Create a `FailedJobListener` that triggers on `Queue::failing`:
- Log the error
- Optionally notify admin (Slack, email) if failure count exceeds threshold

### Circuit Breaker
If Elastic Email returns 5xx errors consistently:
- Jobs back off with exponential delay
- After N consecutive failures, pause the `emails` queue
- Alert admin to investigate Elastic Email status
