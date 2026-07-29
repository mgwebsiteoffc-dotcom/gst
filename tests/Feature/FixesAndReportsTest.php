<?php

use App\Models\Document;
use App\Models\Party;
use App\Models\User;
use App\Services\Reports\BusinessReportService;
use App\Services\Share\ShareService;
use App\Support\TenantContext;

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('is_super_admin', true)->firstOrFail();
    $this->owner = User::where('email', 'owner@demo.test')->firstOrFail();
});

/* ---------- reported crashes ---------- */

it('does not crash tenant screens for a super admin with no business', function () {
    // Previously: "Call to a member function defaultTaxMode() on null"
    foreach (['/dashboard', '/settings', '/documents/invoice/create', '/shopify'] as $url) {
        $this->actingAs($this->admin)->get($url)
            ->assertRedirect(route('admin.dashboard'));
    }
});

it('explains why a platform account has no business', function () {
    $this->actingAs($this->admin)->get('/dashboard')
        ->assertRedirect(route('admin.dashboard'));

    $this->followingRedirects()->actingAs($this->admin)->get('/dashboard')
        ->assertOk()->assertSee('no business of their own', false);
});

it('still lets a normal tenant user reach those screens', function () {
    foreach (['/dashboard', '/settings', '/documents/invoice/create', '/shopify'] as $url) {
        $this->actingAs($this->owner)->get($url)->assertOk();
    }
});

it('shows a connect form on the empty shopify page instead of a blank screen', function () {
    $this->actingAs($this->owner)->get('/shopify')->assertOk()
        ->assertSee('Connect your store', false)
        ->assertSee('myshopify.com', false);
});

/* ---------- sharing ---------- */

it('builds a whatsapp link with the country code and invoice details', function () {
    TenantContext::set($this->owner->tenant);
    $doc = Document::sales()->with('party', 'tenant')->firstOrFail();
    $doc->party->update(['phone' => '9876543210']);

    $url = app(ShareService::class)->whatsappUrl($doc->fresh('party'));

    expect($url)->toStartWith('https://wa.me/919876543210?text=')
        ->and(rawurldecode($url))->toContain($doc->number);
});

it('builds a valid UPI intent only when a UPI id is configured', function () {
    TenantContext::set($tenant = $this->owner->tenant);
    $doc = Document::sales()->with('tenant')->firstOrFail();

    expect(app(ShareService::class)->upiUri($doc))->toBeNull();

    $tenant->update(['settings' => array_merge($tenant->settings ?? [], ['bank' => ['upi' => 'demo@upi']])]);

    $uri = app(ShareService::class)->upiUri($doc->fresh('tenant'));

    expect($uri)->toStartWith('upi://pay?')
        ->and($uri)->toContain('pa=demo%40upi')
        ->and($uri)->toContain('cu=INR');
});

it('serves a signed public invoice link and rejects an unsigned one', function () {
    TenantContext::set($this->owner->tenant);
    $doc = Document::sales()->firstOrFail();

    $this->get(app(ShareService::class)->publicUrl($doc))->assertOk()->assertSee($doc->number);
    $this->get("/i/{$doc->id}")->assertForbidden();
});

/* ---------- reports ---------- */

it('renders the new business report screens', function () {
    // Scope to the acting user's own tenant — Party::first() would otherwise
    // return another business's record and 404 (correct isolation, wrong test).
    TenantContext::set($this->owner->tenant);
    $party = Party::firstOrFail();

    foreach (['/reports/profit-loss', '/reports/item-wise', '/reports/party-wise',
              "/reports/ledger/{$party->id}"] as $url) {
        $this->actingAs($this->owner)->get($url)->assertOk();
    }
});

it('computes profit and loss net of GST', function () {
    TenantContext::set($this->owner->tenant);

    $pl = app(BusinessReportService::class)
        ->profitAndLoss(now()->subMonths(2)->startOfMonth(), now()->endOfMonth());

    // Revenue must exclude tax, so it is below the gross invoice total.
    $grossTotal = (float) Document::whereIn('doc_type', ['invoice', 'bill_of_supply'])
        ->whereNotIn('status', ['draft', 'cancelled'])->sum('grand_total');

    expect($pl['revenue'])->toBeLessThan($grossTotal)
        ->and($pl['net_profit'])->toBe(round($pl['gross_profit'] - $pl['expenses'], 2));
});

it('builds a party ledger with a correct running balance', function () {
    TenantContext::set($this->owner->tenant);
    $party = Party::has('documents')->firstOrFail();

    $ledger = app(BusinessReportService::class)
        ->partyLedger($party, now()->subMonths(2), now()->addDay());

    expect($ledger['rows'])->not->toBeEmpty();

    $expected = $ledger['opening'] + $ledger['total_debit'] - $ledger['total_credit'];
    expect($ledger['closing'])->toBe(round($expected, 2));
});

it('ranks item-wise sales highest first', function () {
    TenantContext::set($this->owner->tenant);

    $rows = app(BusinessReportService::class)
        ->itemWiseSales(now()->subMonths(2), now()->addDay());

    expect($rows)->not->toBeEmpty();

    $totals = array_column($rows, 'total');
    $sorted = $totals;
    rsort($sorted);
    expect($totals)->toBe($sorted);
});

it('zero-fills days with no sales', function () {
    TenantContext::set($this->owner->tenant);

    $rows = app(BusinessReportService::class)->dailySales(now()->subDays(6), now());

    expect($rows)->toHaveCount(7)
        ->and(collect($rows)->every(fn ($r) => isset($r['total'])))->toBeTrue();
});

it('exports a report as downloadable CSV', function () {
    $res = $this->actingAs($this->owner)->get('/reports/export/item-wise')
        ->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    expect($res->streamedContent())->toContain('Item,HSN/SAC,Quantity');
});
