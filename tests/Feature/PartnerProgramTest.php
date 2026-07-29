<?php

use App\Models\Commission;
use App\Models\CommissionPlan;
use App\Models\Partner;
use App\Models\Payout;
use App\Models\Plan;
use App\Models\Referral;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Partner\PayoutService;
use App\Services\Partner\ReferralService;

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('is_super_admin', true)->firstOrFail();
    $this->owner = User::where('email', 'owner@demo.test')->firstOrFail();
    $this->partner = Partner::where('email', 'partner@demo.test')->firstOrFail();
});

/* ---------- commission maths ---------- */

it('calculates percentage commission on each payment', function () {
    $plan = CommissionPlan::where('slug', 'affiliate-standard')->firstOrFail();

    // 20% of ₹999 = ₹199.80
    expect($plan->commissionOn(99900, 1))->toBe(19980);
});

it('stops recurring commission after the plan window', function () {
    $plan = CommissionPlan::where('slug', 'affiliate-standard')->firstOrFail(); // 12 months

    expect($plan->commissionOn(99900, 12))->toBe(19980)
        ->and($plan->commissionOn(99900, 13))->toBe(0);
});

it('pays a flat bonus only on the first payment', function () {
    $plan = CommissionPlan::create([
        'name' => 'Flat test', 'slug' => 'flat-test', 'partner_type' => 'affiliate',
        'model' => 'flat', 'flat_paise' => 50000, 'recurring_months' => 12,
    ]);

    expect($plan->commissionOn(99900, 1))->toBe(50000)
        ->and($plan->commissionOn(99900, 2))->toBe(0);
});

it('combines percentage and bonus on a hybrid plan', function () {
    $plan = CommissionPlan::where('slug', 'channel-gold')->firstOrFail(); // 30% + ₹500

    expect($plan->commissionOn(99900, 1))->toBe(29970 + 50000)
        ->and($plan->commissionOn(99900, 2))->toBe(29970);
});

it('treats zero recurring months as lifetime', function () {
    $plan = CommissionPlan::where('slug', 'channel-bronze')->firstOrFail();

    expect($plan->commissionOn(99900, 99))->toBeGreaterThan(0);
});

/* ---------- attribution ---------- */

it('records a click and sets a referral cookie', function () {
    // The referral cookie is deliberately unencrypted, so assert it raw.
    $this->get('/?ref='.$this->partner->referral_code)
        ->assertOk()
        ->assertPlainCookie(ReferralService::COOKIE, $this->partner->referral_code);

    expect($this->partner->clicks()->count())->toBe(1);
});

it('ignores an unknown or unapproved referral code', function () {
    $pending = Partner::where('email', 'ca@demo.test')->firstOrFail();
    expect($pending->status)->toBe('pending');

    $this->get('/?ref=NOSUCHCODE')->assertOk()->assertCookieMissing(ReferralService::COOKIE);
    $this->get('/?ref='.$pending->referral_code)->assertOk()->assertCookieMissing(ReferralService::COOKIE);
});

it('attributes a signup to the partner whose link was used', function () {
    // Visit the referral link first so the cookie is set exactly as a real visitor would.
    $this->get('/?ref='.$this->partner->referral_code)->assertOk();

    $this->withUnencryptedCookie(ReferralService::COOKIE, $this->partner->referral_code)
        ->post('/register', [
            'name' => 'Referred Owner', 'email' => 'referred@test.test',
            'password' => 'secret1234', 'password_confirmation' => 'secret1234',
            'business_name' => 'Referred Traders', 'tax_mode' => 'non_gst',
            'state_code' => '09', 'terms' => '1',
        ])->assertRedirect(route('dashboard'));

    $tenant = User::where('email', 'referred@test.test')->firstOrFail()->tenant;
    $referral = Referral::where('tenant_id', $tenant->id)->firstOrFail();

    expect($referral->partner_id)->toBe($this->partner->id)
        ->and($referral->status)->toBe('trial');
});

it('never re-attributes a business to a second partner', function () {
    $tenant = Tenant::where('slug', 'demo')->firstOrFail();
    $service = app(ReferralService::class);

    $service->attribute($tenant, $this->partner->referral_code);
    $second = $service->attribute($tenant, $this->partner->referral_code);

    expect($second)->toBeNull()
        ->and(Referral::where('tenant_id', $tenant->id)->count())->toBe(1);
});

/* ---------- commission generation ---------- */

it('creates a commission when a referred business pays', function () {
    $tenant = Tenant::where('slug', 'demo')->firstOrFail();
    $service = app(ReferralService::class);
    $service->attribute($tenant, $this->partner->referral_code);

    $commission = $service->recordPayment($tenant, 99900);

    expect($commission)->not->toBeNull()
        ->and($commission->amount_paise)->toBe(19980)
        ->and($commission->month_number)->toBe(1)
        ->and($commission->status)->toBe('pending')
        // Held for the clawback window before it can be paid.
        ->and($commission->available_at->isFuture())->toBeTrue();

    expect(Referral::where('tenant_id', $tenant->id)->first()->status)->toBe('converted');
});

it('increments the month number across repeat payments', function () {
    $tenant = Tenant::where('slug', 'demo')->firstOrFail();
    $service = app(ReferralService::class);
    $service->attribute($tenant, $this->partner->referral_code);

    $service->recordPayment($tenant, 99900);
    $second = $service->recordPayment($tenant, 99900);

    expect($second->month_number)->toBe(2)
        ->and(Referral::where('tenant_id', $tenant->id)->first()->lifetime_value_paise)->toBe(199800);
});

it('earns nothing for a business with no referrer', function () {
    $tenant = Tenant::where('slug', 'freelance')->firstOrFail();

    expect(app(ReferralService::class)->recordPayment($tenant, 99900))->toBeNull();
});

it('reverses unpaid commissions on a refund', function () {
    $tenant = Tenant::where('slug', 'demo')->firstOrFail();
    $service = app(ReferralService::class);
    $service->attribute($tenant, $this->partner->referral_code);
    $service->recordPayment($tenant, 99900);

    expect($service->reverse($tenant, 'Customer refunded'))->toBe(1)
        ->and(Commission::first()->status)->toBe('reversed')
        ->and($this->partner->fresh()->availableBalance())->toBe(0);
});

/* ---------- payouts ---------- */

it('refuses a payout below the minimum threshold', function () {
    $tenant = Tenant::where('slug', 'demo')->firstOrFail();
    $service = app(ReferralService::class);
    $service->attribute($tenant, $this->partner->referral_code);
    $service->recordPayment($tenant, 99900);   // ₹199.80, under the ₹1,000 minimum

    Commission::query()->update(['status' => 'approved', 'available_at' => now()->subDay()]);

    expect(fn () => app(PayoutService::class)->create($this->partner->fresh()))
        ->toThrow(RuntimeException::class, 'below the');
});

it('bundles cleared commissions into one payout', function () {
    $tenant = Tenant::where('slug', 'demo')->firstOrFail();
    $service = app(ReferralService::class);
    $service->attribute($tenant, $this->partner->referral_code);

    foreach (range(1, 6) as $i) {
        $service->recordPayment($tenant, 99900);   // 6 x ₹199.80 = ₹1,198.80
    }

    Commission::query()->update(['status' => 'approved', 'available_at' => now()->subDay()]);

    $payout = app(PayoutService::class)->create($this->partner->fresh(), $this->admin->id);

    expect($payout->amount_paise)->toBe(6 * 19980)
        ->and($payout->commission_count)->toBe(6)
        ->and($payout->reference)->toStartWith('PO-');

    // Commissions are now locked to this payout.
    expect(Commission::whereNull('payout_id')->count())->toBe(0);
});

it('releases commissions again when a payout fails', function () {
    $tenant = Tenant::where('slug', 'demo')->firstOrFail();
    $service = app(ReferralService::class);
    $service->attribute($tenant, $this->partner->referral_code);
    foreach (range(1, 6) as $i) { $service->recordPayment($tenant, 99900); }
    Commission::query()->update(['status' => 'approved', 'available_at' => now()->subDay()]);

    $payout = app(PayoutService::class)->create($this->partner->fresh());
    app(PayoutService::class)->markFailed($payout, 'Wrong IFSC');

    expect($payout->fresh()->status)->toBe('failed')
        ->and(Commission::whereNull('payout_id')->count())->toBe(6);
});

it('marks commissions paid when the payout settles', function () {
    $tenant = Tenant::where('slug', 'demo')->firstOrFail();
    $service = app(ReferralService::class);
    $service->attribute($tenant, $this->partner->referral_code);
    foreach (range(1, 6) as $i) { $service->recordPayment($tenant, 99900); }
    Commission::query()->update(['status' => 'approved', 'available_at' => now()->subDay()]);

    $payout = app(PayoutService::class)->create($this->partner->fresh());
    app(PayoutService::class)->markPaid($payout, 'UTR123456');

    expect($payout->fresh()->status)->toBe('paid')
        ->and(Commission::where('status', 'paid')->count())->toBe(6)
        ->and($this->partner->fresh()->totalPaid())->toBe(6 * 19980);
});

it('only approves commissions past their hold date', function () {
    $tenant = Tenant::where('slug', 'demo')->firstOrFail();
    $service = app(ReferralService::class);
    $service->attribute($tenant, $this->partner->referral_code);
    $service->recordPayment($tenant, 99900);

    expect(app(PayoutService::class)->approveMatured())->toBe(0);

    Commission::query()->update(['available_at' => now()->subDay()]);

    expect(app(PayoutService::class)->approveMatured())->toBe(1);
});
