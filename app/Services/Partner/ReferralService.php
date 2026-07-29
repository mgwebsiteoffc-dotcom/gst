<?php

namespace App\Services\Partner;

use App\Models\Commission;
use App\Models\Partner;
use App\Models\Referral;
use App\Models\ReferralClick;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Referral attribution and commission generation.
 *
 * Flow: visitor arrives with ?ref=CODE → click recorded, cookie set →
 * they sign up → tenant attributed to the partner → each payment the tenant
 * makes generates a commission until the recurring window closes.
 */
class ReferralService
{
    public const COOKIE = 'bb_ref';

    /** Record a click and return the cookie lifetime in minutes. */
    public function track(Request $request, string $code): ?int
    {
        $partner = Partner::approved()->where('referral_code', strtoupper($code))->first();

        if (! $partner) {
            return null;
        }

        $token = $request->cookie('bb_visitor') ?: (string) Str::uuid();

        ReferralClick::create([
            'partner_id' => $partner->id,
            'landing_page' => Str::limit($request->path(), 190, ''),
            'utm_source' => $request->query('utm_source'),
            'utm_campaign' => $request->query('utm_campaign'),
            'referrer' => Str::limit((string) $request->headers->get('referer'), 190, ''),
            // Hashed so we can de-duplicate without storing personal data.
            'ip_hash' => hash('sha256', $request->ip().config('app.key')),
            'visitor_token' => $token,
        ]);

        $days = $partner->commissionPlan?->cookie_days ?? 60;

        return $days * 24 * 60;
    }

    /**
     * Attribute a newly created business to whoever referred it.
     * First touch wins, and attribution is permanent.
     */
    public function attribute(Tenant $tenant, ?string $code): ?Referral
    {
        if (! $code) {
            return null;
        }

        $partner = Partner::approved()->where('referral_code', strtoupper($code))->first();

        if (! $partner || Referral::where('tenant_id', $tenant->id)->exists()) {
            return null;
        }

        return Referral::create([
            'partner_id' => $partner->id,
            'tenant_id' => $tenant->id,
            'status' => 'trial',
        ]);
    }

    /**
     * Called when a referred business pays. Creates the commission row.
     *
     * Idempotent per (referral, month), so a webhook retry cannot pay twice.
     */
    public function recordPayment(Tenant $tenant, int $amountPaise): ?Commission
    {
        $referral = Referral::with('partner.commissionPlan')->where('tenant_id', $tenant->id)->first();

        if (! $referral || ! $referral->partner?->isApproved()) {
            return null;
        }

        $plan = $referral->partner->commissionPlan;

        if (! $plan || ! $plan->active) {
            return null;
        }

        return DB::transaction(function () use ($referral, $plan, $amountPaise) {
            $monthNumber = $referral->commissions()->whereNot('status', 'reversed')->count() + 1;
            $commissionPaise = $plan->commissionOn($amountPaise, $monthNumber);

            if ($commissionPaise <= 0) {
                return null;
            }

            $referral->increment('lifetime_value_paise', $amountPaise);

            if ($referral->status === 'trial') {
                $referral->update(['status' => 'converted', 'converted_at' => now()]);
            }

            return Commission::create([
                'partner_id' => $referral->partner_id,
                'referral_id' => $referral->id,
                'base_paise' => $amountPaise,
                'rate' => $plan->model === 'flat' ? 0 : $plan->percentage,
                'amount_paise' => $commissionPaise,
                'month_number' => $monthNumber,
                'status' => 'pending',
                // Held so a refund inside the window can be clawed back.
                'available_at' => now()->addDays($plan->hold_days),
            ]);
        });
    }

    /** Reverse commissions when a customer refunds or charges back. */
    public function reverse(Tenant $tenant, ?string $reason = null): int
    {
        $referral = Referral::where('tenant_id', $tenant->id)->first();

        if (! $referral) {
            return 0;
        }

        return $referral->commissions()
            ->whereIn('status', ['pending', 'approved'])
            ->whereNull('payout_id')
            ->update(['status' => 'reversed', 'note' => $reason ?? 'Payment reversed']);
    }
}
