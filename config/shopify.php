<?php

return [
    'api_key' => env('SHOPIFY_API_KEY'),
    'api_secret' => env('SHOPIFY_API_SECRET'),
    'api_version' => env('SHOPIFY_API_VERSION', '2026-01'),
    'scopes' => env('SHOPIFY_SCOPES', 'read_orders,read_products,read_customers,read_fulfillments'),
    'webhook_secret' => env('SHOPIFY_WEBHOOK_SECRET'),
    'embedded' => true,
];
