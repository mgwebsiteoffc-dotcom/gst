<?php

namespace App\Services\Billing;

/**
 * Thin descriptor for the Razorpay Checkout integration. Order creation and
 * signature verification live in BillingController because they need the
 * request context.
 */
class RazorpayGateway
{
    public function __construct(
        protected ?string $key,
        protected ?string $secret,
        protected bool $sandbox = true,
    ) {}

    public function name(): string
    {
        return $this->sandbox ? 'Razorpay (test mode)' : 'Razorpay';
    }

    /** True when real money can be collected. */
    public function isLive(): bool
    {
        return (bool) ($this->key && $this->secret);
    }

    public function keyId(): ?string
    {
        return $this->key;
    }
}
