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

];
