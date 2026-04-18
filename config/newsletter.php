<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Physical Address (CAN-SPAM required)
    |--------------------------------------------------------------------------
    | Appears in the footer of every newsletter email.
    */

    'physical_address' => env('NEWSLETTER_PHYSICAL_ADDRESS', ''),

    /*
    |--------------------------------------------------------------------------
    | Collection Senders
    |--------------------------------------------------------------------------
    | Maps each Statamic collection handle to its from address and name.
    | The Mailable resolves the correct sender based on $campaign->collection.
    |
    | Add a new entry here whenever a new newsletter collection is created.
    */

    'collections' => [

        'insight_newsletters' => [
            'from_email'  => env('NEWSLETTER_INSIGHT_FROM_EMAIL', 'newsletter@dataphyte.com'),
            'from_name'   => env('NEWSLETTER_INSIGHT_FROM_NAME', 'Dataphyte Insight'),
            'reply_to'    => env('NEWSLETTER_INSIGHT_REPLY_TO', ''),
            'brand_color' => '#0d1b2a',
        ],

        'foundation_newsletters' => [
            'from_email'  => env('NEWSLETTER_FOUNDATION_FROM_EMAIL', 'newsletter@dataphyte.org'),
            'from_name'   => env('NEWSLETTER_FOUNDATION_FROM_NAME', 'Dataphyte Foundation'),
            'reply_to'    => env('NEWSLETTER_FOUNDATION_REPLY_TO', ''),
            'brand_color' => '#1b4332',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Sender
    |--------------------------------------------------------------------------
    | Used when a campaign's collection does not match any entry above.
    */

    'fallback' => [
        'from_email' => env('MAIL_FROM_ADDRESS', 'newsletter@dataphyte.com'),
        'from_name'  => env('MAIL_FROM_NAME', 'Dataphyte'),
        'reply_to'   => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Security
    |--------------------------------------------------------------------------
    | Optional shared secret appended to the Elastic Email webhook URL:
    |   https://yourdomain.com/webhooks/elastic-email?secret=YOUR_SECRET
    |
    | Configure in Elastic Email dashboard under Settings > Notifications.
    | Leave blank to skip verification (not recommended in production).
    */

    'webhook_secret' => env('ELASTIC_EMAIL_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Email Send Rate (per minute)
    |--------------------------------------------------------------------------
    | Controls how many emails the queue worker sends per minute.
    | Set this based on your Elastic Email plan's daily sending limit.
    |
    | Quick reference (assumes a 16-hour sending window per day):
    |   100/day    →   1
    |   1,000/day  →   1
    |   5,000/day  →   5
    |   10,000/day →  10
    |   50,000/day →  52
    |  100,000/day → 104
    |
    | Default: 50 — conservative starting point. Increase once you confirm
    | your Elastic Email plan limit.
    */

    'send_rate' => (int) env('ELASTIC_EMAIL_SEND_RATE', 50),

    /*
    |--------------------------------------------------------------------------
    | Analytics Sync — Job 1: Recent (runs hourly)
    |--------------------------------------------------------------------------
    | Scans sends from the last N hours. Catches deliveries, opens, and clicks
    | shortly after a campaign is sent. Keep the limit modest — this runs every
    | hour so unchecked sends roll into the next run automatically.
    |
    |  SYNC_RECENT_HOURS  — how far back to look (default: 8 hours)
    |  SYNC_RECENT_LIMIT  — max sends to check per run (default: 500)
    */

    'sync_recent_hours' => (int) env('SYNC_RECENT_HOURS', 8),
    'sync_recent_limit' => (int) env('SYNC_RECENT_LIMIT', 500),

    /*
    |--------------------------------------------------------------------------
    | Analytics Sync — Job 2: Deep Scan (runs daily at 2 AM)
    |--------------------------------------------------------------------------
    | Scans all unresolved sends from the last N days. Catches late opens and
    | clicks from subscribers who engage days after the campaign was sent.
    | Runs off-peak so a higher limit is safe.
    |
    |  SYNC_DEEP_DAYS   — how far back to look (default: 30 days)
    |  SYNC_DEEP_LIMIT  — max sends to check per run (default: 2000)
    */

    'sync_deep_days'  => (int) env('SYNC_DEEP_DAYS',  30),
    'sync_deep_limit' => (int) env('SYNC_DEEP_LIMIT', 2000),

];
