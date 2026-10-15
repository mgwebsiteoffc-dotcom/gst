<?php

namespace App\Services\Billing;

use App\Support\TenantContext;

/**
 * Which payment gateway a business pays with. Platform staff can set a
 * Razorpay account for one business only; otherwise the server .env account
 * is used. Kept in one place so a second gateway can be added later.
 */
class GatewayResolver
{
    public function driver(): RazorpayGateway
    {
        $tenant = TenantContext::get()?->billingTenant();

        $key = $tenant?->setting('billing.razorpay_key') ?: config('services.razorpay.key');
        $secret = $tenant?->setting('billing.razorpay_secret') ?: config('services.razorpay.secret');
        $sandbox = (bool) ($tenant?->setting('billing.razorpay_sandbox') ?? true);

        return new RazorpayGateway($key, $secret, $sandbox);
    }
}
