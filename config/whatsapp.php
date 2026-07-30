<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Cloud API Configuration
    |--------------------------------------------------------------------------
    |
    | Uses Meta's WhatsApp Cloud API (graph.facebook.com) to send messages
    | programmatically. Each tenant configures their own phone number and
    | access token via the settings UI; these are fallbacks for platform use.
    |
    */

    'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v19.0'),

    // Platform-level defaults (optional — tenants override via settings)
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),

    // Default template names (must be pre-approved in Meta Business Manager)
    'templates' => [
        'invoice' => env('WHATSAPP_TEMPLATE_INVOICE', 'invoice_notification'),
        'quotation' => env('WHATSAPP_TEMPLATE_QUOTATION', 'quotation_notification'),
        'payment_reminder' => env('WHATSAPP_TEMPLATE_REMINDER', 'payment_reminder'),
        'payment_receipt' => env('WHATSAPP_TEMPLATE_RECEIPT', 'payment_receipt'),
    ],

    // Default language for templates
    'language' => env('WHATSAPP_LANGUAGE', 'en'),

    // Max retry attempts for failed sends
    'max_retries' => 3,
];
