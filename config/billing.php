<?php

return [
    /*
     * Who is selling the subscription. These details print on the GST invoice
     * raised for each subscription payment — set them for your own business,
     * not the customer's.
     */
    'seller' => [
        'name' => env('BILLING_SELLER_NAME', 'BikriBook'),
        'legal_name' => env('BILLING_SELLER_LEGAL_NAME', env('BILLING_SELLER_NAME', 'BikriBook')),
        'gstin' => env('BILLING_SELLER_GSTIN'),
        'state_code' => env('BILLING_SELLER_STATE_CODE', '09'),
        'address' => env('BILLING_SELLER_ADDRESS'),
        'email' => env('BILLING_SELLER_EMAIL'),
        'phone' => env('BILLING_SELLER_PHONE'),
        'sac' => env('BILLING_SAC', '998314'), // IT software services
    ],

    // GST charged on SaaS subscriptions in India.
    'gst_rate' => (float) env('BILLING_GST_RATE', 18),

    // Where the payment terms are described on the invoice.
    'terms' => env('BILLING_TERMS', 'Subscription fees are non-refundable. Renewals are not automatic.'),
];
