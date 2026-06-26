<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VAPID Keys
    |--------------------------------------------------------------------------
    |
    | You need to generate VAPID keys for push notifications.
    | Run `php artisan webpush:vapid` to generate them.
    |
    */

    'vapid' => [
        'public_key'  => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject'     => env('VAPID_SUBJECT', 'mailto:admin@example.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | GCM API Key (For Firebase Cloud Messaging - Optional)
    |--------------------------------------------------------------------------
    |
    | If you are using Firebase Cloud Messaging, you can set your server API key here.
    |
    */

    'gcm' => [
        'key' => env('GCM_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Worker Settings
    |--------------------------------------------------------------------------
    |
    | The service worker file should be accessible via the public directory.
    |
    */

    'service_worker_url' => '/service-worker.js',

    /*
    |--------------------------------------------------------------------------
    | TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | How long the push notifications should be valid (in seconds).
    |
    */

    'ttl' => 2419200, // 4 weeks
];
