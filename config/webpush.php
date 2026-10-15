<?php

return [
    /*
     * Generate a pair on the server with: php artisan push:vapid
     * then paste the two values into .env (keep the quotes on the private key).
     */
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@bikribook.com'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    'ttl' => (int) env('WEBPUSH_TTL', 86400),
];
