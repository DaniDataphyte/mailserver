# Tracking & Webhooks

---

## Elastic Email Webhooks

### Setup in Elastic Email Dashboard
Settings > Notifications > Add Notification:
- URL: `https://yourdomain.com/webhooks/elastic-email`
- Events: Sent, Opened, Clicked, Bounced, Unsubscribed, Complained
- Content type: JSON

### Route Definition
```php
// routes/web.php
Route::match(['get', 'post'], '/webhooks/elastic-email', [WebhookController::class, 'receive'])
    ->name('webhooks.elastic-email');
```

GET requests must return `200 OK` (Elastic Email validation check).

### Webhook Security
- Verify requests originate from Elastic Email (IP allowlist or shared secret in URL)
- Exempt route from CSRF middleware
- Rate limit: `throttle:1000,1` (high throughput for webhook events)

### Event Processing

```php
// WebhookController@receive
public function handle(Request $request)
{
    if ($request->isMethod('get')) {
        return response('OK', 200);
    }

    ProcessWebhookJob::dispatch($request->all())
        ->onQueue('webhooks');

    return response('OK', 200);
}
```

### ProcessWebhookJob Event Handling

| Event | Update campaign_sends | Update subscriber |
|---|---|---|
| Sent | status = sent, sent_at = now | - |
| Delivered | status = delivered, delivered_at = now | - |
| Opened | status = opened, opened_at = now (first only) | - |
| Clicked | status = clicked, clicked_at = now + log to campaign_link_clicks | - |
| Bounced (hard) | status = bounced, bounced_at = now, bounce_reason | status = bounced |
| Bounced (soft) | Log only, don't change status | - |
| Unsubscribed | - | status = unsubscribed, unsubscribed_at = now |
| Complained | status = complained | status = complained |

### Matching Webhooks to Sends
- When sending, store the `elastic_email_message_id` returned by Elastic Email
- Webhook payloads include this message ID
- Look up `campaign_sends` by `elastic_email_message_id`

---

## Stats Sync (Fallback)

Webhooks can be missed. Hourly scheduled command reconciles:

### `stats:sync-elastic-email` Command
1. Find campaigns sent in the last 48 hours
2. For each campaign, pull stats from Elastic Email API
3. Compare with local `campaign_sends` data
4. Update any discrepancies
5. Log sync results

---

## Campaign Analytics Dashboard

### Metrics per Campaign
- **Sent:** count of campaign_sends with status != queued/failed
- **Delivered:** count where status in (delivered, opened, clicked)
- **Open rate:** opened / delivered * 100
- **Click rate:** clicked / delivered * 100
- **Bounce rate:** bounced / sent * 100
- **Complaint rate:** complained / sent * 100
- **Unsubscribe rate:** unsubscribed from campaign / delivered * 100

### Aggregate Metrics
- Total subscribers (active)
- Subscriber growth (new per day/week/month)
- Average open rate across campaigns
- Average click rate across campaigns
- Top clicked links

### Display
Custom Statamic CP section with:
- Campaign list with key metrics columns
- Individual campaign detail view with full stats
- Charts (open/click trends over time)
- Top performing campaigns ranking
- Dashboard widget showing recent campaign performance

---

## Bounce Management

### Hard Bounces
- Automatically set subscriber status to `bounced`
- Subscriber excluded from all future sends
- Admin can review and manually reactivate if needed

### Soft Bounces
- Logged but subscriber stays active
- After 3 soft bounces on consecutive campaigns, auto-set to `bounced`

### Suppression List
- Bounced and complained subscribers form the suppression list
- Checked before every campaign send
- Synced with Elastic Email's suppression list via API
