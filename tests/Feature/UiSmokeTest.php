<?php

use App\Models\{Document, Item, Party, Plan, Subscription, Tenant, User};
use App\Services\Gst\DocumentService;
use App\Support\TenantContext;

beforeEach(function () {
    $this->seed();
    $this->user = User::where('email', 'owner@demo.test')->firstOrFail();
    $this->tenant = $this->user->tenant;
    TenantContext::set($this->tenant);
});

it('renders every screen for a signed-in user', function () {
    $party = Party::first();
    $item = Item::first();
    $invoice = Document::where('doc_type', 'invoice')->firstOrFail();

    $screens = [
        '/dashboard',
        '/documents/invoice',
        '/documents/invoice/create',
        '/documents/quotation/create',
        '/documents/purchase_bill/create',
        "/document/{$invoice->id}",
        "/document/{$invoice->id}/edit",
        '/contacts',
        '/contacts/create',
        "/contacts/{$party->id}",
        "/contacts/{$party->id}/edit",
        '/items',
        '/items/create',
        "/items/{$item->id}/edit",
        '/settings',
        '/settings?tab=business',
        '/settings?tab=branding',
        '/settings?tab=invoicing',
        '/settings?tab=einvoice',
        '/settings?tab=team',
    ];

    foreach ($screens as $url) {
        $this->actingAs($this->user)->get($url)->assertOk();
    }

    // Branding was merged into Settings and should redirect there.
    $this->actingAs($this->user)->get('/branding')
        ->assertRedirect(route('settings.index', ['tab' => 'business']));
})->group('ui');

it('creates a GST invoice through the form endpoint', function () {
    $party = Party::whereNotNull('gstin')->first();
    $item = Item::first();

    $response = $this->actingAs($this->user)->post('/documents', [
        'doc_type' => 'invoice',
        'tax_mode' => 'gst',
        'party_id' => $party->id,
        'issue_date' => now()->toDateString(),
        'status' => 'sent',
        'shipping_charge' => 0,
        'lines' => [
            ['item_id' => $item->id, 'name' => $item->name, 'hsn_sac' => $item->hsn_sac,
             'quantity' => 3, 'rate' => 1000, 'gst_rate' => 18, 'discount_percent' => 10],
        ],
    ]);

    $doc = Document::latest('id')->first();
    $response->assertRedirect(route('documents.show', $doc));

    // 3 x 1000 less 10% = 2700 taxable, +18% = 3186
    expect((float) $doc->taxable_value)->toBe(2700.0)
        ->and((float) $doc->grand_total)->toBe(3186.0)
        ->and($doc->tax_mode)->toBe('gst');
});

it('creates a non-GST invoice with no tax', function () {
    $party = Party::first();

    $this->actingAs($this->user)->post('/documents', [
        'doc_type' => 'invoice',
        'tax_mode' => 'non_gst',
        'party_id' => $party->id,
        'issue_date' => now()->toDateString(),
        'status' => 'sent',
        'lines' => [['name' => 'Consulting', 'quantity' => 2, 'rate' => 5000]],
    ])->assertRedirect();

    $doc = Document::latest('id')->first();

    expect($doc->tax_mode)->toBe('non_gst')
        ->and((float) $doc->grand_total)->toBe(10000.0)
        ->and((float) $doc->cgst_total)->toBe(0.0)
        ->and($doc->number)->toStartWith('BILL/');
});

it('creates and updates a client, deriving state from the GSTIN', function () {
    $this->actingAs($this->user)->post('/contacts', [
        'type' => 'customer',
        'name' => 'Chennai Steels',
        'gst_treatment' => 'regular',
        'gstin' => '33AABCU9603R1ZU',
    ])->assertRedirect(route('parties.index'));

    $party = Party::where('name', 'Chennai Steels')->firstOrFail();

    // 33 = Tamil Nadu, derived automatically from the GSTIN.
    expect($party->billing_state_code)->toBe('33')
        ->and($party->billing_state)->toBe('Tamil Nadu')
        ->and($party->pan)->toBe('AABCU9603R');

    $this->actingAs($this->user)->put("/contacts/{$party->id}", [
        'type' => 'customer', 'name' => 'Chennai Steels Pvt Ltd',
        'gst_treatment' => 'regular', 'gstin' => '33AABCU9603R1ZU',
    ])->assertRedirect();

    expect($party->fresh()->name)->toBe('Chennai Steels Pvt Ltd');
});

it('rejects an invalid GSTIN on a client', function () {
    $this->actingAs($this->user)
        ->post('/contacts', ['type' => 'customer', 'name' => 'Bad', 'gst_treatment' => 'regular', 'gstin' => '33AABCU9603R1ZM'])
        ->assertSessionHasErrors('gstin');
});

it('creates a product and forces services to be non-stocked', function () {
    $this->actingAs($this->user)->post('/items', [
        'name' => 'SEO Retainer', 'type' => 'service', 'uqc' => 'NOS',
        'hsn_sac' => '998365', 'sale_price' => 25000, 'gst_rate' => 18,
        'track_inventory' => 1,
    ])->assertRedirect(route('items.index'));

    $item = Item::where('name', 'SEO Retainer')->firstOrFail();

    expect($item->track_inventory)->toBeFalse()
        ->and((float) $item->sale_price)->toBe(25000.0);
});

it('blocks duplicate SKUs within a tenant', function () {
    $existing = Item::whereNotNull('sku')->first();

    $this->actingAs($this->user)->post('/items', [
        'name' => 'Clone', 'type' => 'goods', 'uqc' => 'NOS',
        'sale_price' => 10, 'gst_rate' => 18, 'sku' => $existing->sku,
    ])->assertSessionHasErrors('sku');
});

it('saves the business profile and derives PAN from the GSTIN', function () {
    $this->actingAs($this->user)->post('/branding', [
        'name' => 'Demo Traders',
        'legal_name' => 'Demo Traders Private Limited',
        'gstin' => '09AAACD1234F1Z5',
        'registration_type' => 'regular',
        'pdf_template' => 'classic',
        'address_city' => 'Noida',
        'bank_name' => 'HDFC Bank',
        'bank_ifsc' => 'HDFC0000123',
        'default_terms' => 'Payment within 15 days.',
    ])->assertRedirect();

    $t = $this->tenant->fresh();

    expect($t->pan)->toBe('AAACD1234F')
        ->and($t->setting('bank.name'))->toBe('HDFC Bank')
        ->and($t->setting('default_terms'))->toBe('Payment within 15 days.');
});
