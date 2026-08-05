<?php

use App\Models\Document;
use App\Models\Payment;
use App\Models\User;
use App\Services\Bulk\BulkExcelService;
use App\Services\Gst\DocumentService;
use App\Support\TenantContext;

beforeEach(function () {
    $this->seed();
    $this->owner = User::where('email', 'owner@demo.test')->firstOrFail();
    $this->tenant = $this->owner->tenant;
    TenantContext::set($this->tenant);
});

/* ------------------------------------------------------------------ */
/* Settings merge semantics                                            */
/* ------------------------------------------------------------------ */

it('saves business details and preserves them when other tabs are saved', function () {
    // 1. Save the "Business details" tab (address + bank + GSTIN).
    $this->actingAs($this->owner)->post('/branding', [
        'name' => 'Demo Traders',
        'registration_type' => 'regular',
        'gstin' => '09AAACD1234F1Z5',
        'state_code' => '09',
        'pdf_template' => 'classic',
        'address_line1' => 'B-12 Sector 63',
        'address_city' => 'Noida',
        'address_pincode' => '201301',
        'phone' => '9810012345',
        'email' => 'billing@demo.test',
        'bank_name' => 'HDFC Bank',
        'bank_account' => '50200011223344',
        'bank_ifsc' => 'HDFC0000123',
        'bank_upi' => 'demo@hdfc',
    ])->assertRedirect();

    $t = $this->tenant->fresh();
    expect($t->setting('address.line1'))->toBe('B-12 Sector 63')
        ->and($t->setting('bank.name'))->toBe('HDFC Bank')
        ->and($t->gstin)->toBe('09AAACD1234F1Z5');

    // 2. Save the "Logo & signature" tab — this form knows nothing about the
    //    address or bank fields, and must not wipe them (this was the bug).
    $this->actingAs($this->owner)->post('/branding', [
        'name' => 'Demo Traders',
        'registration_type' => 'regular',
        'accent_color' => '#ff0000',
        'pdf_template' => 'modern',
        'show_ship_to' => '1',
    ])->assertRedirect();

    $t = $this->tenant->fresh();
    expect($t->setting('address.line1'))->toBe('B-12 Sector 63')   // ← preserved
        ->and($t->setting('address.city'))->toBe('Noida')
        ->and($t->setting('bank.upi'))->toBe('demo@hdfc')          // ← preserved
        ->and($t->gstin)->toBe('09AAACD1234F1Z5')                  // ← GSTIN preserved
        ->and($t->setting('pdf_template'))->toBe('modern')         // ← changed value stuck
        ->and($t->setting('show_ship_to'))->toBeTrue()
        ->and($t->branding['accent_color'])->toBe('#ff0000');

    // 3. Save the "terms" tab — still nothing about address/bank in it.
    $this->actingAs($this->owner)->post('/branding', [
        'name' => 'Demo Traders',
        'registration_type' => 'regular',
        'pdf_template' => 'modern',
        'default_terms' => 'Payment in 10 days.',
    ])->assertRedirect();

    $t = $this->tenant->fresh();
    expect($t->setting('address.line1'))->toBe('B-12 Sector 63')
        ->and($t->setting('bank.name'))->toBe('HDFC Bank')
        ->and($t->branding['accent_color'])->toBe('#ff0000')
        ->and($t->setting('show_ship_to'))->toBeTrue()
        ->and($t->setting('default_terms'))->toBe('Payment in 10 days.');
});

it('does not reset the accent colour when a tab without it is saved', function () {
    $this->tenant->update(['branding' => ['accent_color' => '#123456']]);

    $this->actingAs($this->owner)->post('/branding', [
        'name' => $this->tenant->name,
        'registration_type' => 'regular',
        'pdf_template' => 'classic',
        'address_line1' => 'Some street',
    ])->assertRedirect();

    expect($this->tenant->fresh()->branding['accent_color'])->toBe('#123456');
});

it('can switch off allow_both_modes via the sentinel checkbox', function () {
    expect((bool) $this->tenant->allow_both_modes)->toBeTrue();

    $this->actingAs($this->owner)->put('/settings/tax-mode', [
        'tax_mode' => 'gst',
        'allow_both_modes' => '0',
    ])->assertRedirect();

    expect((bool) $this->tenant->fresh()->allow_both_modes)->toBeFalse();
});

/* ------------------------------------------------------------------ */
/* Payments in bulk import / export                                    */
/* ------------------------------------------------------------------ */

it('shows the payments card on the bulk page and exports payments', function () {
    $this->actingAs($this->owner)->get('/bulk')
        ->assertOk()->assertSee('Payments Received');

    $response = $this->actingAs($this->owner)->get('/bulk/payments/export');
    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('payments_export');
});

it('imports a payment row and recalculates the invoice balance', function () {
    $doc = app(DocumentService::class)->create([
        'doc_type' => 'invoice', 'tax_mode' => 'gst',
        'party_id' => \App\Models\Party::firstOrFail()->id,
        'issue_date' => now(), 'status' => 'sent',
        'lines' => [['name' => 'Service', 'quantity' => 1, 'rate' => 1000, 'gst_rate' => 18]],
    ]);

    $rows = [[
        'line' => 2, 'status' => 'valid',
        'data' => [
            'document_number' => $doc->number,
            'amount' => '180',
            'paid_on' => now()->toDateString(),
            'method' => 'upi',
            'reference' => 'UTR123',
            'note' => '',
        ],
    ]];

    $result = app(BulkExcelService::class)->executeImport($rows, 'payments', 'import');

    expect($result['stats']['imported'])->toBe(1)
        ->and((float) $doc->fresh()->amount_paid)->toBe(180.0)
        ->and((float) $doc->fresh()->balance_due)->toBe(1000.0)
        ->and($doc->fresh()->status)->toBe('partially_paid');

    // Importing the same file again must not double-count the payment.
    $again = app(BulkExcelService::class)->executeImport($rows, 'payments', 'import');
    expect($again['stats']['skipped'])->toBe(1)
        ->and((float) $doc->fresh()->amount_paid)->toBe(180.0);
});

/* ------------------------------------------------------------------ */
/* Edit invoice/quotation + activity log                               */
/* ------------------------------------------------------------------ */

it('lets a user reach the edit page from the invoice screen and logs changes', function () {
    $doc = Document::where('tenant_id', $this->tenant->id)->where('doc_type', 'invoice')->firstOrFail();

    $this->actingAs($this->owner)->get("/document/{$doc->id}")
        ->assertOk()
        ->assertSee('Change history');

    $this->actingAs($this->owner)->put("/document/{$doc->id}", [
        'doc_type' => 'invoice',
        'tax_mode' => $doc->tax_mode,
        'party_id' => $doc->party_id,
        'issue_date' => $doc->issue_date->toDateString(),
        'lines' => [['name' => 'Corrected item', 'quantity' => 2, 'rate' => 500, 'gst_rate' => 18, 'hsn_sac' => '998314']],
    ])->assertRedirect();

    $activity = $doc->fresh()->activities()->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toContain('Edited')
        ->and($activity->causer?->id)->toBe($this->owner->id)
        ->and($activity->changes['attributes'])->toHaveKey('grand_total');
});

it('logs document creation with the creator as causer', function () {
    $this->actingAs($this->owner)->post('/documents', [
        'doc_type' => 'quotation',
        'tax_mode' => 'gst',
        'party_id' => \App\Models\Party::firstOrFail()->id,
        'issue_date' => now()->toDateString(),
        'status' => 'sent',
        'lines' => [['name' => 'Quote line', 'quantity' => 1, 'rate' => 2000, 'gst_rate' => 18]],
    ])->assertRedirect();

    $doc = Document::latest('id')->firstOrFail();
    $created = $doc->activities()->where('event', 'created')->latest('id')->first();

    expect($created)->not->toBeNull()
        ->and($created->description)->toContain('Created Quotation')
        ->and($created->causer?->id)->toBe($this->owner->id);
});

/* ------------------------------------------------------------------ */
/* Team invite & management                                            */
/* ------------------------------------------------------------------ */

it('lets an owner invite a teammate, creating their login account', function () {
    // Demo tenant is at its Growth plan seat limit (5) — lift it for this test.
    $this->tenant->subscription->plan->update(['user_limit' => 10]);

    $this->actingAs($this->owner)->post('/settings/team', [
        'name' => 'New Team Member',
        'email' => 'newbie@demo.test',
        'role' => 'accountant',
        'password' => 'welcome123',
    ])->assertRedirect(route('settings.index', ['tab' => 'team']));

    $user = User::where('email', 'newbie@demo.test')->firstOrFail();

    expect($user->tenant_id)->toBe($this->tenant->id)
        ->and($user->is_active)->toBeTrue()
        ->and(\Illuminate\Support\Facades\Hash::check('welcome123', $user->password))->toBeTrue();

    setPermissionsTeamId($this->tenant->id);
    expect($user->fresh()->getRoleNames()->all())->toContain('accountant');
});

it('generates a password and shows it once when none is typed', function () {
    $this->tenant->subscription->plan->update(['user_limit' => 10]);

    $this->actingAs($this->owner)
        ->followingRedirects()
        ->post('/settings/team', [
            'name' => 'Gen Pass',
            'email' => 'genpass@demo.test',
            'role' => 'operator',
        ])
        ->assertSee('Temporary password');

    expect(User::where('email', 'genpass@demo.test')->first()->password)->not->toBeNull();
});

it('blocks invites once the plan seat limit is reached', function () {
    $anita = User::where('email', 'anita@demo.test')->firstOrFail(); // starter plan, 1 seat, 1 user

    $this->actingAs($anita)->post('/settings/team', [
        'name' => 'Extra',
        'email' => 'extra@freelance.test',
        'role' => 'viewer',
        'password' => 'welcome123',
    ])->assertSessionHas('error');

    expect(User::where('email', 'extra@freelance.test')->exists())->toBeFalse();
});

it('forbids non-admin roles from inviting', function () {
    $viewer = User::where('email', 'viewer@demo.test')->firstOrFail();

    $this->actingAs($viewer)->post('/settings/team', [
        'name' => 'Nope', 'email' => 'nope@demo.test', 'role' => 'operator',
    ])->assertForbidden();
});

it('changes a member role and toggles their access — but never the owner\'s', function () {
    $viewer = User::where('email', 'viewer@demo.test')->firstOrFail();

    $this->actingAs($this->owner)->put("/settings/team/{$viewer->id}/role", ['role' => 'operator'])
        ->assertRedirect();

    setPermissionsTeamId($this->tenant->id);
    expect($viewer->fresh()->getRoleNames()->all())->toContain('operator');

    $this->actingAs($this->owner)->put("/settings/team/{$viewer->id}/toggle")->assertRedirect();
    expect($viewer->fresh()->is_active)->toBeFalse();

    $this->actingAs($this->owner)->put("/settings/team/{$viewer->id}/toggle")->assertRedirect();
    expect($viewer->fresh()->is_active)->toBeTrue();

    // Owner protections: can't change owner role, can't deactivate yourself.
    $this->actingAs($this->owner)->put("/settings/team/{$this->owner->id}/role", ['role' => 'viewer']);
    setPermissionsTeamId($this->tenant->id);
    expect($this->owner->fresh()->getRoleNames()->all())->toContain('owner');
});
