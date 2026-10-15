<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Raises the GST invoice for a subscription payment (or a platform-granted
 * period) so the business has a bill and a transaction history entry.
 */
class SubscriptionInvoiceService
{
    /** Split a tax-inclusive or pre-tax amount into base + 18% GST. */
    public function breakdown(int $amountPaise, ?float $rate = null): array
    {
        $rate ??= (float) config('billing.gst_rate', 18);

        $gst = (int) round($amountPaise * $rate / 100);

        return ['amount' => $amountPaise, 'gst' => $gst, 'total' => $amountPaise + $gst];
    }

    /**
     * Record a paid period for a business.
     *
     * @param  int  $amountPaise  plan price before tax
     */
    public function record(
        Tenant $tenant,
        Plan $plan,
        string $interval,
        int $amountPaise,
        string $gateway = 'manual',
        ?string $gatewayPaymentId = null,
        ?Subscription $subscription = null,
        string $status = 'paid',
    ): SubscriptionPayment {
        $parts = $this->breakdown($amountPaise);
        $months = $interval === 'yearly' ? 12 : 1;

        return DB::transaction(function () use ($tenant, $plan, $interval, $parts, $gateway, $gatewayPaymentId, $subscription, $status, $months) {
            return SubscriptionPayment::withoutGlobalScope('tenant')->create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription?->id ?? $tenant->subscriptionRecord()?->id,
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'interval' => $interval,
                'invoice_number' => SubscriptionPayment::nextInvoiceNumber($tenant->id),
                'amount_paise' => $parts['amount'],
                'gst_paise' => $parts['gst'],
                'total_paise' => $parts['total'],
                'currency' => $tenant->currency ?: 'INR',
                'status' => $status,
                'gateway' => $gateway,
                'gateway_payment_id' => $gatewayPaymentId,
                'period_start' => now()->toDateString(),
                'period_end' => now()->addMonths($months)->toDateString(),
                'paid_at' => $status === 'paid' ? now() : null,
            ]);
        });
    }
}
