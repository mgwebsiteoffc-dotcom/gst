<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Billing\GatewayResolver;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Subscription self-service for the main business: pick a plan, pay, cancel
 * or downgrade. Auto-renew is not enabled — the vendor renews when the period
 * ends (no silent charges on a UPI mandate they forgot about).
 */
class BillingController extends Controller
{
    /** The plan chooser. Reachable even when the subscription lapsed. */
    public function plans(Request $request, GatewayResolver $gateways)
    {
        $tenant = TenantContext::get()->billingTenant();
        $subscription = $tenant->subscription;
        $interval = $request->get('interval') === 'yearly' ? 'yearly' : 'monthly';

        return view('billing.plans-page', [
            'tenant' => $tenant,
            'subscription' => $subscription,
            'plans' => Plan::where('active', true)->orderBy('price_paise')->get(),
            'interval' => $interval,
            'gstin' => $tenant->gstin,
            'gatewayName' => $gateways->driver()->name(),
            'gatewayLive' => $gateways->driver()->isLive(),
        ]);
    }

    /** Start (or renew) the subscription on the chosen plan. */
    public function checkout(Request $request, GatewayResolver $gateways)
    {
        $tenant = TenantContext::get()->billingTenant();

        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'interval' => ['required', 'in:monthly,yearly'],
        ]);

        $plan = Plan::where('active', true)->findOrFail($data['plan_id']);
        $interval = $data['interval'];
        $months = $interval === 'yearly' ? 12 : 1;
        $years = $interval === 'yearly' ? 1 : 0;

        $amountPaise = $interval === 'yearly'
            ? (int) ($plan->yearly_price_paise ?: $plan->price_paise * 12)
            : (int) $plan->price_paise;

        // Gateway config wins; the server env value is only a fallback.
        $key = $tenant->setting('billing.razorpay_key') ?: config('services.razorpay.key');
        $secret = $tenant->setting('billing.razorpay_secret') ?: config('services.razorpay.secret');
        $live = $key && $secret;

        return DB::transaction(function () use ($tenant, $plan, $interval, $months, $amountPaise, $key, $secret, $live) {
            $subscription = $tenant->subscription()->firstOrNew(['tenant_id' => $tenant->id]);

            $subscription->fill(Subscription::existingAttributes([
                'plan_id' => $plan->id,
                'interval' => $interval,
                'gateway' => 'razorpay',
                'status' => $live ? 'active' : 'trialing',
                'current_period_end' => now()->addMonths($months),
                'started_at' => $subscription->started_at ?: now(),
                'canceled_at' => null,
            ]));
            $subscription->save();

            // A paykit order so the browser can open Razorpay Checkout.
            $order = $live ? $this->createOrder($key, $secret, $amountPaise, $plan, $interval) : null;

            return view('billing.checkout', [
                'tenant' => $tenant,
                'plan' => $plan,
                'subscription' => $subscription,
                'interval' => $interval,
                'amountPaise' => $amountPaise,
                'amountLabel' => '₹'.number_format($amountPaise / 100, 2),
                'keyId' => $key,
                'order' => $order,
                'live' => (bool) $order,
            ]);
        });
    }

    /** Confirm the payment that Razorpay Checkout handed back. */
    public function payment(Request $request, GatewayResolver $gateways)
    {
        $tenant = TenantContext::get()->billingTenant();

        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'interval' => ['required', 'in:monthly,yearly'],
            'razorpay_payment_id' => ['nullable', 'string', 'max:100'],
            'razorpay_order_id' => ['nullable', 'string', 'max:100'],
            'razorpay_signature' => ['nullable', 'string', 'max:255'],
            'simulated' => ['nullable', 'boolean'],
        ]);

        $plan = Plan::where('active', true)->findOrFail($data['plan_id']);
        $months = $data['interval'] === 'yearly' ? 12 : 1;

        $key = $tenant->setting('billing.razorpay_key') ?: config('services.razorpay.key');
        $secret = $tenant->setting('billing.razorpay_secret') ?: config('services.razorpay.secret');

        // Verify the gateway signature whenever a real payment was made.
        if ($key && $secret && ! empty($data['razorpay_payment_id'])) {
            $expected = hash_hmac('sha256', $data['razorpay_order_id'].'|'.$data['razorpay_payment_id'], $secret);

            if (! hash_equals($expected, (string) $data['razorpay_signature'])) {
                return redirect()->route('billing.plans')
                    ->with('error', __('Payment could not be verified. If money was deducted, contact support.'));
            }
        }

        $subscription = $tenant->subscription()->firstOrNew(['tenant_id' => $tenant->id]);
        $subscription->fill(Subscription::existingAttributes([
            'plan_id' => $plan->id,
            'interval' => $data['interval'],
            'status' => 'active',
            'current_period_end' => now()->addMonths($months),
            'started_at' => $subscription->started_at ?: now(),
            'canceled_at' => null,
        ]))->save();

        // Keep the vendor-side reference for support and refunds.
        $tenant->update([
            'settings' => array_replace_recursive($tenant->settings ?? [], [
                'billing' => [
                    'last_payment_id' => $data['razorpay_payment_id'] ?? null,
                    'last_order_id' => $data['razorpay_order_id'] ?? null,
                    'paid_at' => now()->toIso8601String(),
                ],
            ]),
        ]);

        return redirect()->route('dashboard')->with('success', __(
            ':plan is active for :business until :date.',
            ['plan' => $plan->name, 'business' => $tenant->name, 'date' => $subscription->current_period_end->format('d M Y')]
        ));
    }

    /** Create a Razorpay order (amount in paise). */
    protected function createOrder(string $key, string $secret, int $amountPaise, Plan $plan, string $interval): ?array
    {
        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERPWD => $key.':'.$secret,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'amount' => $amountPaise,
                'currency' => 'INR',
                'receipt' => 'bb-'.now()->timestamp,
                'notes' => ['plan' => $plan->slug, 'interval' => $interval],
            ]),
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = json_decode((string) $response, true);

        if ($status >= 400 || empty($body['id'])) {
            report(new \RuntimeException('Razorpay order failed: '.substr((string) $response, 0, 300)));

            return null;
        }

        return $body;
    }

    /** Stop the subscription at the end of the paid period. */
    public function cancel(Request $request)
    {
        $tenant = TenantContext::get()->billingTenant();
        $subscription = $tenant->subscription;

        if (! $subscription) {
            return back()->with('error', __('No subscription to cancel.'));
        }

        // Blocked while branches still exist — they all run on this plan.
        if ($tenant->branches()->exists()) {
            return back()->with('error', __('Remove your branches before cancelling — they all run on this business’s plan.'));
        }

        $subscription->update(Subscription::existingAttributes([
            'status' => 'canceled',
            'canceled_at' => now(),
        ]));

        return back()->with('success', __('Subscription cancelled. Access continues until :date.', [
            'date' => $subscription->current_period_end?->format('d M Y') ?? now()->format('d M Y'),
        ]));
    }

    /** Move to a lower plan / switch billing interval. */
    public function downgrade(Request $request)
    {
        $tenant = TenantContext::get()->billingTenant();

        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'interval' => ['nullable', 'in:monthly,yearly'],
        ]);

        $plan = Plan::where('active', true)->findOrFail($data['plan_id']);
        $subscription = $tenant->subscription;

        if (! $subscription) {
            return back()->with('error', __('Choose a plan first.'));
        }

        if (! $plan->includes('multi_branch') && $tenant->branches()->exists()) {
            return back()->with('error', __('This plan does not include multi-branch. Remove your branches first.'));
        }

        $months = ($data['interval'] ?? $subscription->interval) === 'yearly' ? 12 : 1;

        $subscription->update([
            'plan_id' => $plan->id,
            'interval' => $data['interval'] ?? $subscription->interval,
            'status' => 'active',
            'current_period_end' => Carbon::parse($subscription->current_period_end ?? now())->max(now())->addMonths($months),
        ]);

        return back()->with('success', __('Switched to :plan.', ['plan' => $plan->name]));
    }
}
