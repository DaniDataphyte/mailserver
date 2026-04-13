# Elastic Email Integration

Two integration layers: a Laravel mail driver for sending, and the SDK for tracking/management.

---

## Layer 1: Mail Driver (Sending)

### Package
```
composer require flexflux/laravel-elastic-email
```

### Configuration

**`.env`:**
```
MAIL_MAILER=elasticemail
ELASTIC_EMAIL_API_KEY=your-api-key-here
```

**`config/mail.php`:**
```php
'mailers' => [
    'elasticemail' => [
        'transport' => 'elasticemail',
        'key' => env('ELASTIC_EMAIL_API_KEY'),
    ],
],
```

This registers as a standard Laravel mailer transport. Use `Mail::send()` and `Mailable` classes as normal.

---

## Layer 2: Direct API SDK (Tracking, Stats, Management)

### Package
```
composer require elasticemail/elasticemail-php
```

### Use Cases
- Register/manage webhook notification URLs
- Pull campaign statistics (delivered, opened, clicked, bounced)
- Manage suppression lists
- Verify email addresses

### Service Class

Create `app/Services/ElasticEmailService.php` to wrap SDK calls:
- `getMessageStatus(string $messageId): array`
- `getCampaignStats(string $campaignId): array`
- `addToSuppressionList(string $email): void`
- `verifyEmail(string $email): array`

---

## Rate Limiting

Elastic Email enforces **max 20 concurrent connections** (both SMTP and API).

### Implementation
```
composer require spatie/laravel-rate-limited-job-middleware
```

Apply to `SendNewsletterEmailJob`:
```php
public function middleware(): array
{
    return [
        (new RateLimited('elastic-email'))
            ->allow(15) // Leave headroom below 20
            ->everySecond()
            ->releaseAfterSeconds(5),
    ];
}
```

---

## Webhook Configuration

### Elastic Email Dashboard Setup
Settings > Notifications > Add Notification:
- **URL:** `https://yourdomain.com/webhooks/elastic-email`
- **Events:** Sent, Opened, Clicked, Bounced, Unsubscribed, Complained

### Critical Notes
- Endpoint must return `200 OK` for GET requests (validation)
- Endpoint must process POST requests with event payloads
- **Elastic Email disables notifications after 1000 consecutive failures** - monitor this endpoint
- Use UptimeRobot or similar to ensure availability

### Webhook Payload Events

| Event | Action |
|---|---|
| Sent | Update campaign_send status to `sent` |
| Delivered | Update to `delivered`, set `delivered_at` |
| Opened | Update to `opened`, set `opened_at` (first open only) |
| Clicked | Update to `clicked`, set `clicked_at`, log to campaign_link_clicks |
| Bounced | Update to `bounced`, set subscriber status to `bounced` |
| Unsubscribed | Set subscriber status to `unsubscribed` |
| Complained | Set subscriber status to `complained` |

---

## Domain Setup in Elastic Email

1. Go to Settings > Domains > Add Domain
2. Add your sending domain
3. Complete verification (TXT record)
4. Set as default sending domain (blue star icon)
5. Configure bounce domain (return-path)
