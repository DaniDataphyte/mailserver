# Campaign Engine

---

## Campaign Lifecycle

```
Draft -> Scheduled -> Sending -> Sent
  |                      |
  +-> Cancelled          +-> Cancelled (partial)
```

### Statuses
- **Draft** - being created/edited, not yet scheduled
- **Scheduled** - has a `scheduled_at` datetime, waiting to be dispatched
- **Sending** - DispatchCampaignJob is running, emails being queued
- **Sent** - all emails dispatched
- **Cancelled** - manually cancelled (may have partial sends)

---

## Campaign Creation (Statamic CP)

### Fields
- **Name** (internal reference)
- **Subject** line
- **From Name** / **From Email** / **Reply-To**
- **Template** (select from email_templates)
- **Content** (rich text editor for newsletter body)
- **Audience** (multi-select: groups, sub-groups, or "All Subscribers")
- **Schedule** (send now or pick date/time)

### Preview
- Render the email template with content
- Show recipient count based on audience selection
- Send test email to admin address

---

## Audience Resolution

The campaign audience resolver determines which subscribers receive the campaign.

### Algorithm
```
1. Collect all campaign_audiences rows for this campaign
2. If any row has send_to_all = true:
     -> Return all subscribers where status = 'active'
3. For each audience row:
     - If targetable_type = SubscriberGroup:
         -> Get all sub_groups in that group
         -> Get all active subscribers in those sub_groups
     - If targetable_type = SubscriberSubGroup:
         -> Get all active subscribers in that sub_group
4. Merge all subscriber sets
5. Deduplicate by subscriber.id
6. Return unique subscriber collection
```

### Key Rule
A subscriber in multiple targeted sub-groups receives **exactly one email** per campaign.

---

## Job Architecture

### DispatchCampaignJob (queue: `campaigns`)

Runs when a scheduled campaign's time arrives or when "Send Now" is clicked.

1. Set campaign status to `sending`
2. Resolve audience (see above)
3. Update `total_recipients` count
4. Create `campaign_sends` rows with status `queued` for each subscriber
5. Chunk subscribers into batches of 100
6. Dispatch `SendNewsletterEmailJob` for each subscriber
7. Set campaign status to `sent` when all jobs dispatched

### SendNewsletterEmailJob (queue: `emails`)

Sends one email to one subscriber.

1. Load campaign and subscriber
2. Check subscriber is still `active` (may have unsubscribed since queued)
3. Render email template with subscriber-specific data (name, unsubscribe link)
4. Send via Elastic Email mail driver
5. Update campaign_send: status = `sent`, `sent_at` = now, store `elastic_email_message_id`
6. On failure: update campaign_send status = `failed`, log error

**Middleware:**
- Rate limiting: max 15 concurrent (Elastic Email limit is 20)
- Retries: 3 attempts with 60-second backoff

---

## Email Template System

### Storage
Templates are Blade files in `resources/views/emails/newsletters/`.

### Available Variables in Templates
```php
$subscriber->email
$subscriber->first_name
$subscriber->last_name
$campaign->subject
$campaign->name
$unsubscribeUrl       // Signed URL
$preferencesUrl       // Signed URL
$webVersionUrl        // View in browser link
$physicalAddress      // CAN-SPAM required
```

### CSS Inlining
Tailwind CSS classes are converted to inline styles at send time using `tijsverkoyen/css-to-inline-styles` (already included with Laravel).

### Base Template Structure
```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    {{-- Preheader text --}}
    <div style="display:none;">{{ $preheader ?? '' }}</div>

    {{-- Main content --}}
    @yield('content')

    {{-- Footer (required) --}}
    <footer>
        <p>{{ $physicalAddress }}</p>
        <a href="{{ $unsubscribeUrl }}">Unsubscribe</a>
        <a href="{{ $preferencesUrl }}">Manage Preferences</a>
    </footer>
</body>
</html>
```

### Maizzle (Optional Enhancement)
For production-quality responsive email templates, consider using Maizzle:
- Tailwind-CSS-first email framework
- Compiles to inline-styled HTML
- Export as Blade views
- Install separately: `npx create-maizzle`

---

## Scheduling

### Immediate Send
1. Admin clicks "Send Now"
2. `DispatchCampaignJob` is dispatched immediately to the `campaigns` queue

### Scheduled Send
1. Admin sets `scheduled_at` datetime
2. Campaign status set to `scheduled`
3. Every 5 minutes, `campaigns:dispatch-scheduled` artisan command runs
4. Command finds campaigns where `scheduled_at <= now()` and `status = scheduled`
5. Dispatches `DispatchCampaignJob` for each

---

## Cancellation

- **Draft/Scheduled:** Set status to `cancelled`, no emails sent
- **Sending:** Set status to `cancelled`, remaining queued `campaign_sends` are skipped (the SendNewsletterEmailJob checks campaign status before sending)
