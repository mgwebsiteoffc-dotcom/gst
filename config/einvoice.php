<?php

return [
    // Default driver when a tenant has not configured its own.
    //   sandbox => offline fake, no network (local dev, CI, demos)
    //   nic     => live NIC IRP / GSP endpoints
    'driver' => env('EINVOICE_DRIVER', 'sandbox'),

    'sandbox_url' => env('EINVOICE_SANDBOX_URL', 'https://einv-apisandbox.nic.in'),
    'production_url' => env('EINVOICE_PRODUCTION_URL', 'https://api.einvoice1.gst.gov.in'),
    'base_url' => env('EINVOICE_BASE_URL', 'https://einv-apisandbox.nic.in'),

    'client_id' => env('EINVOICE_CLIENT_ID'),
    'client_secret' => env('EINVOICE_CLIENT_SECRET'),
    'username' => env('EINVOICE_USERNAME'),
    'password' => env('EINVOICE_PASSWORD'),

    'ewaybill_base_url' => env('EWAYBILL_BASE_URL'),

    // NIC API paths (v1.03/1.04 as published on the e-invoice portal).
    'endpoints' => [
        'auth' => '/eivital/v1.04/auth',
        'generate' => '/eicore/v1.03/Invoice',
        'cancel' => '/eicore/v1.03/Invoice/Cancel',
        'get_by_irn' => '/eicore/v1.03/Invoice/irn',
        'gstin_details' => '/eivital/v1.04/Master/gstin',
        'ewb_by_irn' => '/ewaybillapi/v1.03/ewayapi/GenEwayBillByIRN',
        'ewb_direct' => '/ewaybillapi/v1.03/ewayapi',
        'ewb_cancel' => '/ewaybillapi/v1.03/ewayapi/CANEWB',
    ],

    // Auth tokens are valid ~6h; refresh a little early.
    'token_ttl_minutes' => 300,

    // Mandatory e-invoicing turnover threshold (AATO) in rupees.
    'aato_threshold' => 50000000,
    // Documents older than this many days cannot be reported to the IRP.
    'reporting_window_days' => 30,
];
