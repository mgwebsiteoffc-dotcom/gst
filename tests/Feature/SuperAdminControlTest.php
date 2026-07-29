<?php

use App\Models\Partner;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('is_super_admin', true)->firstOrFail();
    $this->owner = User::where('email', 'owner@demo.test')->firstOrFail();
});

it('renders every super admin screen', function () {
    $tenant = Tenant::where('slug', 'demo')->firstOrFail();
    $partner = Partner::first();

    foreach (['/admin', '/admin/tenants', "/admin/tenants/{$tenant->id}", '/admin/plans',
              '/admin/partners', "/admin/partners/{$partner->id}", '/admin/payouts',
              '/admin/commission-plans'] as $url) {
        $this->actingAs($this->admin)->get($url)->assertOk();
    }
});

it('keeps tenant users out of every admin screen', function () {
    foreach (['/admin/tenants', '/admin/plans', '/admin/partners', '/admin/payouts',
              '/admin/commission-plans'] as $url) {
        $this->actingAs($this->owner)->get($url)->assertForbidden();
    }
});

/* ---------- plan management ---------- */

it('lets the owner create a subscription plan that appears on the pricing page', function () {
    $this->actingAs($this->admin)->post('/admin/plans', [
        'name' => 'Ultimate', 'price_rupees' => 4999, 'yearly_price_rupees' => 49990,
        'user_limit' => 50, 'einvoice' => '1', 'shopify' => '1', 'active' => '1',
    ])->assertRedirect();

    $plan = Plan::where('name', 'Ultimate')->firstOrFail();

    // Rupees in the UI, paise in storage.
    expect($plan->price_paise)->toBe(499900)
        ->and($plan->einvoice)->toBeTrue();

    auth()->logout();
    $this->get('/pricing')->assertOk()->assertSee('Ultimate');
});

it('refuses to delete a plan that has subscribers', function () {
    $plan = Plan::has('subscriptions')->firstOrFail();

    $this->actingAs($this->admin)->delete("/admin/plans/{$plan->id}")
        ->assertRedirect()->assertSessionHas('error');

    expect(Plan::find($plan->id))->not->toBeNull();
});

/* ---------- tenant + user control ---------- */

it('suspends a business and locks its whole team out', function () {
    $tenant = Tenant::where('slug', 'demo')->firstOrFail();

    $this->actingAs($this->admin)->put("/admin/tenants/{$tenant->id}", [
        'account_status' => 'suspended',
        'suspension_reason' => 'Non-payment',
    ])->assertRedirect();

    expect($tenant->fresh()->account_status)->toBe('suspended');

    auth()->logout();
    $this->post('/login', ['email' => 'owner@demo.test', 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('disables a single user without touching the rest of the team', function () {
    $staff = User::where('email', 'operator@demo.test')->firstOrFail();

    $this->actingAs($this->admin)->put("/admin/users/{$staff->id}/toggle")->assertRedirect();

    expect($staff->fresh()->is_active)->toBeFalse();

    auth()->logout();
    $this->post('/login', ['email' => 'operator@demo.test', 'password' => 'password'])
        ->assertSessionHasErrors('email');

    // A colleague is unaffected.
    $this->post('/login', ['email' => 'owner@demo.test', 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
});

it('will not let a super admin disable another platform account', function () {
    $this->actingAs($this->admin)->put("/admin/users/{$this->admin->id}/toggle")->assertForbidden();
});

it('moves a business onto a different plan', function () {
    $tenant = Tenant::where('slug', 'demo')->firstOrFail();
    $target = Plan::where('slug', 'enterprise')->firstOrFail();

    $this->actingAs($this->admin)->put("/admin/tenants/{$tenant->id}/subscription", [
        'plan_id' => $target->id, 'status' => 'active', 'trial_days' => 60,
    ])->assertRedirect();

    expect($tenant->fresh()->subscription->plan_id)->toBe($target->id)
        ->and($tenant->fresh()->trial_ends_at->isFuture())->toBeTrue();
});

/* ---------- partner administration ---------- */

it('approves a partner application and stamps who approved it', function () {
    $pending = Partner::where('email', 'ca@demo.test')->firstOrFail();
    $plan = \App\Models\CommissionPlan::where('slug', 'channel-gold')->firstOrFail();

    $this->actingAs($this->admin)->put("/admin/partners/{$pending->id}", [
        'status' => 'approved', 'commission_plan_id' => $plan->id, 'tier' => 'gold',
    ])->assertRedirect();

    $pending->refresh();

    expect($pending->status)->toBe('approved')
        ->and($pending->approved_by)->toBe($this->admin->id)
        ->and($pending->approved_at)->not->toBeNull();
});

/* ---------- public application + portal ---------- */

it('accepts a partner application from the public page', function () {
    $this->get('/partners/channel')->assertOk()->assertSee('Channel Partner Program');
    $this->get('/partners/affiliate')->assertOk();

    $this->post('/partners/apply', [
        'type' => 'affiliate', 'name' => 'Ravi Reels', 'email' => 'ravi@reels.test',
        'phone' => '9876543210', 'pitch' => 'I make GST reels for shopkeepers.', 'terms' => '1',
    ])->assertRedirect();

    $partner = Partner::where('email', 'ravi@reels.test')->firstOrFail();

    expect($partner->status)->toBe('pending')
        // A unique code is generated automatically, avoiding lookalike characters.
        ->and($partner->referral_code)->toHaveLength(8)
        ->and($partner->referral_code)->not->toContain('O');
});

it('blocks a spam application caught by the honeypot', function () {
    $this->post('/partners/apply', [
        'type' => 'affiliate', 'name' => 'Bot', 'email' => 'bot@spam.test',
        'phone' => '1', 'pitch' => 'spam', 'terms' => '1', 'website_url' => 'http://spam.test',
    ])->assertSessionHasErrors('website_url');

    expect(Partner::where('email', 'bot@spam.test')->exists())->toBeFalse();
});

it('shows an approved partner their own dashboard', function () {
    $partnerUser = User::where('email', 'partner@demo.test')->firstOrFail();

    $this->actingAs($partnerUser)->get('/partner')->assertOk()
        ->assertSee('NEHA2026')            // their referral code
        ->assertSee('Your referral link');
});

it('refuses the portal to a user with no partner profile', function () {
    $this->actingAs($this->owner)->get('/partner')->assertForbidden();
});

it('refuses the portal while an application is still pending', function () {
    $pending = Partner::where('email', 'ca@demo.test')->firstOrFail();
    $user = User::create([
        'name' => 'Pending CA', 'email' => 'pending@ca.test',
        'password' => 'secret1234', 'email_verified_at' => now(),
    ]);
    $pending->update(['user_id' => $user->id]);

    $this->actingAs($user)->get('/partner')->assertForbidden();
});

it('sends a partner to their portal after login', function () {
    $this->post('/login', ['email' => 'partner@demo.test', 'password' => 'password'])
        ->assertRedirect(route('partner.dashboard'));
});
