<?php

use Illuminate\Support\ServiceProvider;

return [
    'name' => env('APP_NAME', 'BikriBook'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),

    // India-first defaults: IST clock and rupee formatting throughout.
    'timezone' => env('APP_TIMEZONE', 'Asia/Kolkata'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_IN'),

    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    // Framework defaults plus the app providers listed in bootstrap/providers.php.
    'providers' => ServiceProvider::defaultProviders()->merge([
        // Package providers are auto-discovered.
    ])->replace([])->toArray(),
];
