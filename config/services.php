<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as GST APIs, MCA APIs, and other integrations.
    |
    */

    'razorpay' => [
        'key' => env('RAZORPAY_KEY'),
        'secret' => env('RAZORPAY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],

    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'ap-south-1'),
    ],

    // GST API services (Masters India, ClearTax, Karza, etc.)
    'gstin_api' => [
        'key' => env('GSTIN_API_KEY'),
        'provider' => env('GSTIN_API_PROVIDER', 'masters_india'),
    ],

    // MCA (Ministry of Corporate Affairs) API
    'mca_api' => [
        'key' => env('MCA_API_KEY'),
    ],

    // WhatsApp Business API
    'whatsapp' => [
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
    ],

];