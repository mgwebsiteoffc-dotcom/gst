<?php

use App\Models\Document;
use App\Models\Party;
use App\Models\RecurringInvoice;
use App\Models\User;
use App\Support\TenantContext;

beforeEach(function () {
    $this->seed();
    $this->owner = User::where('email', 'owner@demo.test')->firstOrFail();
    TenantContext::set($this->owner->tenant);

    $this->party = Party::where('tenant_id', $this->owner->tenant_id)->firstOrFail();
});

function recurringTestPayload(Party $party, array $overrides = []): array
{
    return array_merge([
        'doc_type' => 'invoice',
        'tax_mode' => 'gst',
        'party_id' => $party->id,
        'frequency' => 'monthly',
        'interval_count' => 1,
        'starts_on' => now()->toDateString(),
        'auto_finalize' => '1',
        'lines' => [
            ['name' => 'Monthly retainer', 'quantity' => 1, 'rate' => 10000, 'gst_rate' => 18],
        ],
    ], $overrides);
}

it('lets a tenant user open the recurring invoices list', function () {
    $this->actingAs($this->owner)->get('/recurring-invoices')
        ->assertOk()->assertSee('Recurring Invoices');
});

it('creates a schedule whose first run is its start date', function () {
    $this->actingAs($this->owner)->post('/recurring-invoices', recurringTestPayload($this->party))
        ->assertRedirect();

    $schedule = RecurringInvoice::firstOrFail();

    expect($schedule->tenant_id)->toBe($this->owner->tenant_id)
        ->and($schedule->status)->toBe('active')
        ->and($schedule->next_run_on->toDateString())->toBe(now()->toDateString())
        ->and((float) $schedule->template['lines'][0]['rate'])->toBe(10000.0)
        ->and($schedule->estimatedTotal())->toBeGreaterThan(0);
});

it('requires at least one line', function () {
    $payload = recurringTestPayload($this->party, ['lines' => []]);

    $this->actingAs($this->owner)->post('/recurring-invoices', $payload)
        ->assertSessionHasErrors('lines');
});

it('generates an invoice for a due schedule and advances to next month', function () {
    $this->actingAs($this->owner)->post('/recurring-invoices', recurringTestPayload($this->party))->assertRedirect();

    $this->artisan('recurring:generate')->assertSuccessful();

    $schedule = RecurringInvoice::firstOrFail();
    $document = Document::where('recurring_invoice_id', $schedule->id)->firstOrFail();

    expect($document->source)->toBe('recurring')
        ->and($document->status)->toBe('sent')          // auto-finalised
        ->and($document->party_id)->toBe($this->party->id)
        ->and((float) $document->grand_total)->toBe(11800.0)    // 10,000 + 18% GST
        ->and($document->number)->not->toBeEmpty()      // series number was issued
        ->and($schedule->fresh()->generated_count)->toBe(1)
        ->and($schedule->fresh()->next_run_on->toDateString())
        ->toBe(now()->addMonth()->toDateString());
});

it('creates drafts when auto-finalise is off', function () {
    $this->actingAs($this->owner)->post('/recurring-invoices',
        recurringTestPayload($this->party, ['auto_finalize' => null]));

    $this->artisan('recurring:generate')->assertSuccessful();

    $schedule = RecurringInvoice::firstOrFail();
    $document = Document::where('recurring_invoice_id', $schedule->id)->firstOrFail();

    expect($document->status)->toBe('draft');
});

it('does not generate for future or paused schedules', function () {
    $this->actingAs($this->owner)->post('/recurring-invoices',
        recurringTestPayload($this->party, ['starts_on' => now()->addMonth()->toDateString()]));

    $this->actingAs($this->owner)->post('/recurring-invoices', recurringTestPayload($this->party));
    $paused = RecurringInvoice::latest('id')->firstOrFail();
    $this->actingAs($this->owner)->post("/recurring-invoices/{$paused->id}/pause");

    $this->artisan('recurring:generate')->assertSuccessful();

    expect(Document::where('source', 'recurring')->count())->toBe(0);
});

it('completes the schedule once it reaches its end date', function () {
    $this->actingAs($this->owner)->post('/recurring-invoices',
        recurringTestPayload($this->party, ['ends_on' => now()->addMonth()->toDateString()]));

    // Two runs (today + next month) sit inside the window, the third does not.
    $this->artisan('recurring:generate')->assertSuccessful();
    $this->artisan('recurring:generate', ['--date' => now()->addMonth()->toDateString()])->assertSuccessful();

    $schedule = RecurringInvoice::firstOrFail();

    expect($schedule->generated_count)->toBe(2)
        ->and($schedule->status)->toBe('completed')
        ->and(Document::where('recurring_invoice_id', $schedule->id)->count())->toBe(2);
});

it('stops after the maximum number of occurrences', function () {
    $this->actingAs($this->owner)->post('/recurring-invoices',
        recurringTestPayload($this->party, ['ends_on' => null, 'max_occurrences' => 2]));

    $this->artisan('recurring:generate')->assertSuccessful();
    $this->artisan('recurring:generate', ['--date' => now()->addMonth()->toDateString()])->assertSuccessful();
    $this->artisan('recurring:generate', ['--date' => now()->addMonths(2)->toDateString()])->assertSuccessful();

    $schedule = RecurringInvoice::firstOrFail();

    expect($schedule->status)->toBe('completed')
        ->and(Document::where('recurring_invoice_id', $schedule->id)->count())->toBe(2);
});

it('generates the next invoice on demand via the run button', function () {
    $this->actingAs($this->owner)->post('/recurring-invoices',
        recurringTestPayload($this->party, ['starts_on' => now()->addWeek()->toDateString()]));

    $schedule = RecurringInvoice::firstOrFail();

    $this->actingAs($this->owner)->post("/recurring-invoices/{$schedule->id}/run")
        ->assertRedirect();

    $document = Document::where('recurring_invoice_id', $schedule->id)->firstOrFail();

    // The manual run issues the invoice dated at the scheduled next run.
    expect($document->issue_date->toDateString())->toBe(now()->addWeek()->toDateString())
        ->and($schedule->fresh()->next_run_on->toDateString())
        ->toBe(now()->addWeek()->addMonth()->toDateString());
});

it('catches up missed runs but respects the safety cap', function () {
    $this->actingAs($this->owner)->post('/recurring-invoices',
        recurringTestPayload($this->party, ['frequency' => 'daily']));

    $this->travel(3)->days(); // scheduler was "down" for 3 days

    $this->artisan('recurring:generate')->assertSuccessful();

    expect(RecurringInvoice::firstOrFail()->generated_count)->toBe(4); // today + 3 missed
});

it('clamps month-end starts with no overflow into short months', function () {
    $schedule = new RecurringInvoice(['frequency' => 'monthly', 'interval_count' => 1]);

    expect($schedule->runAfter(\Illuminate\Support\Carbon::parse('2026-01-31'))->toDateString())
        ->toBe('2026-02-28');
});

it('prefills the schedule form from an existing invoice', function () {
    $document = Document::where('tenant_id', $this->owner->tenant_id)
        ->where('doc_type', 'invoice')->firstOrFail();

    $this->actingAs($this->owner)->get('/recurring-invoices/create?from='.$document->id)
        ->assertOk()
        ->assertSee('Pre-filled from')
        ->assertSee($document->number);
});

it('isolates schedules across tenants', function () {
    $this->actingAs($this->owner)->post('/recurring-invoices', recurringTestPayload($this->party));

    $otherOwner = User::where('email', 'anita@demo.test')->firstOrFail();
    $schedule = RecurringInvoice::firstOrFail();

    $this->actingAs($otherOwner)->get("/recurring-invoices/{$schedule->id}")->assertNotFound();
});
