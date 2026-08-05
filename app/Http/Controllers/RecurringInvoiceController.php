<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecurringInvoiceRequest;
use App\Models\Document;
use App\Models\Item;
use App\Models\Party;
use App\Models\RecurringInvoice;
use App\Services\Gst\RecurringInvoiceService;
use App\Services\Gst\TaxCalculator;
use App\Support\Gstin;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class RecurringInvoiceController extends Controller
{
    public function __construct(protected RecurringInvoiceService $service) {}

    public function index(Request $request)
    {
        $schedules = RecurringInvoice::with('party')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->q, fn ($q, $s) => $q->whereHas('party', fn ($p) => $p->where('name', 'like', "%$s%")))
            ->orderByRaw("case when status = 'active' then 0 when status = 'paused' then 1 when status = 'completed' then 2 else 3 end")
            ->orderBy('next_run_on')
            ->paginate(25)->withQueryString();

        $activeCount = RecurringInvoice::active()->count();
        $dueToday = RecurringInvoice::active()->due()->count();

        return view('recurring.index', compact('schedules', 'activeCount', 'dueToday'));
    }

    public function create(Request $request)
    {
        $tenant = TenantContext::get();
        $from = null;

        // "Make recurring" from an existing invoice pre-fills the whole template.
        if ($request->filled('from')) {
            $from = Document::whereIn('doc_type', ['invoice', 'bill_of_supply'])
                ->with('lines')
                ->findOrFail($request->integer('from'));
        }

        return view('recurring.form', [
            'schedule' => null,
            'from' => $from,
            'template' => $from ? $this->service->templateFromDocument($from) : null,
            'tenant' => $tenant,
            'parties' => Party::customers()->orderBy('name')->get(),
            'items' => Item::orderBy('name')->get(['id', 'name', 'sku', 'hsn_sac', 'uqc', 'sale_price', 'purchase_price', 'gst_rate', 'cess_rate', 'price_inclusive_tax', 'type']),
            'states' => Gstin::STATES,
        ]);
    }

    public function store(RecurringInvoiceRequest $request)
    {
        $data = $request->validated();
        $data['status'] = 'active';

        $schedule = RecurringInvoice::create($this->attributes($data));

        return redirect()->route('recurring.show', $schedule)
            ->with('success', 'Recurring invoice created. First run is scheduled for '.$schedule->next_run_on->format('d M Y').'.');
    }

    public function show(RecurringInvoice $recurringInvoice)
    {
        $recurringInvoice->load('party', 'creator');
        $documents = $recurringInvoice->documents()->with('party')->latest('issue_date')->latest('id')->paginate(15);

        return view('recurring.show', [
            'schedule' => $recurringInvoice,
            'documents' => $documents,
        ]);
    }

    public function edit(RecurringInvoice $recurringInvoice)
    {
        $tenant = TenantContext::get();

        return view('recurring.form', [
            'schedule' => $recurringInvoice,
            'from' => null,
            'template' => $recurringInvoice->template,
            'tenant' => $tenant,
            'parties' => Party::customers()->orderBy('name')->get(),
            'items' => Item::orderBy('name')->get(['id', 'name', 'sku', 'hsn_sac', 'uqc', 'sale_price', 'purchase_price', 'gst_rate', 'cess_rate', 'price_inclusive_tax', 'type']),
            'states' => Gstin::STATES,
        ]);
    }

    public function update(RecurringInvoiceRequest $request, RecurringInvoice $recurringInvoice)
    {
        $recurringInvoice->update($this->attributes($request->validated(), $recurringInvoice));

        return redirect()->route('recurring.show', $recurringInvoice)
            ->with('success', 'Recurring invoice updated.');
    }

    public function pause(RecurringInvoice $recurringInvoice)
    {
        abort_unless($recurringInvoice->status === 'active', 422, 'Only an active schedule can be paused.');
        $recurringInvoice->update(['status' => 'paused']);

        return back()->with('success', 'Schedule paused. No invoices will be generated until you resume it.');
    }

    public function resume(RecurringInvoice $recurringInvoice)
    {
        abort_unless($recurringInvoice->status === 'paused', 422, 'Only a paused schedule can be resumed.');

        // If runs were missed while paused, continue from today instead of
        // back-filling a pile of old invoices the customer never expected.
        if ($recurringInvoice->next_run_on->isPast()) {
            $recurringInvoice->next_run_on = now()->startOfDay();
        }

        $recurringInvoice->status = 'active';
        $recurringInvoice->save();

        return back()->with('success', 'Schedule resumed. Next invoice: '.$recurringInvoice->next_run_on->format('d M Y').'.');
    }

    /** Generate the next invoice immediately instead of waiting for the scheduler. */
    public function run(RecurringInvoice $recurringInvoice)
    {
        abort_if($recurringInvoice->status === 'cancelled', 422, 'A cancelled schedule cannot generate invoices.');
        abort_if($recurringInvoice->isExhausted(), 422, 'This schedule has reached its end. Edit it to extend.');

        if ($recurringInvoice->status === 'paused') {
            return back()->with('error', 'Resume the schedule first, then run it.');
        }

        $document = $this->service->generate($recurringInvoice, $recurringInvoice->next_run_on->copy());

        if (! $document) {
            return back()->with('error', 'Could not generate the invoice. Check that the schedule has at least one line.');
        }

        return redirect()->route('documents.show', $document)
            ->with('success', $document->title.' '.$document->number.' generated.');
    }

    public function destroy(RecurringInvoice $recurringInvoice)
    {
        $recurringInvoice->update(['status' => 'cancelled']);
        $recurringInvoice->delete();

        return redirect()->route('recurring.index')->with('success', 'Recurring invoice cancelled.');
    }

    /**
     * Split validated input into table columns + the JSON template consumed by
     * the generator service.
     */
    protected function attributes(array $data, ?RecurringInvoice $existing = null): array
    {
        $template = [
            'place_of_supply_code' => $data['place_of_supply_code'] ?? null,
            'reverse_charge' => (bool) ($data['reverse_charge'] ?? false),
            'is_export' => (bool) ($data['is_export'] ?? false),
            'with_payment_of_tax' => (bool) ($data['with_payment_of_tax'] ?? false),
            'shipping_charge' => (float) ($data['shipping_charge'] ?? 0),
            'round_off_enabled' => true,
            'notes' => $data['notes'] ?? null,
            'terms' => $data['terms'] ?? null,
            'estimated_total' => $this->estimateTotal($data),
            'lines' => collect($data['lines'] ?? [])->map(fn ($l) => [
                'item_id' => $l['item_id'] ?? null,
                'name' => $l['name'],
                'description' => $l['description'] ?? null,
                'hsn_sac' => $l['hsn_sac'] ?? null,
                'uqc' => $l['uqc'] ?? 'NOS',
                'quantity' => (float) $l['quantity'],
                'rate' => (float) $l['rate'],
                'discount_percent' => (float) ($l['discount_percent'] ?? 0),
                'gst_rate' => (float) ($l['gst_rate'] ?? 0),
                'cess_rate' => (float) ($l['cess_rate'] ?? 0),
            ])->all(),
        ];

        $startsOn = \Illuminate\Support\Carbon::parse($data['starts_on']);

        return [
            'party_id' => $data['party_id'],
            'doc_type' => $data['doc_type'],
            'tax_mode' => $data['tax_mode'] ?? TenantContext::get()?->defaultTaxMode() ?? 'gst',
            'frequency' => $data['frequency'],
            'interval_count' => (int) $data['interval_count'],
            'starts_on' => $startsOn,
            'next_run_on' => $this->initialNextRun($data, $startsOn, $existing),
            'ends_on' => $data['ends_on'] ?? null,
            'max_occurrences' => $data['max_occurrences'] ?? null,
            'due_days' => $data['due_days'] ?? null,
            'auto_finalize' => (bool) ($data['auto_finalize'] ?? false),
            'template' => $template,
            'status' => $data['status'] ?? $existing?->status ?? 'active',
            'created_by' => $existing?->created_by ?? auth()->id(),
        ];
    }

    /**
     * On create, the first run is simply the start date. On edit, keep the
     * current next run unless the user moved the start into the future.
     */
    protected function initialNextRun(array $data, \Illuminate\Support\Carbon $startsOn, ?RecurringInvoice $existing): \Illuminate\Support\Carbon
    {
        if (! $existing) {
            return $startsOn->copy();
        }

        if ($startsOn->isFuture() && $existing->next_run_on->lt($startsOn)) {
            return $startsOn->copy();
        }

        return $existing->next_run_on instanceof \Illuminate\Support\Carbon
            ? $existing->next_run_on
            : \Illuminate\Support\Carbon::parse($existing->next_run_on);
    }

    /** Display-only estimate shown in the list; actual totals are recalculated per run. */
    protected function estimateTotal(array $data): float
    {
        $tenant = TenantContext::get();

        $computed = app(TaxCalculator::class)->calculate([
            'tax_mode' => $data['tax_mode'] ?? 'gst',
            'seller_state_code' => $tenant?->state_code,
            'place_of_supply_code' => $data['place_of_supply_code'] ?? $tenant?->state_code,
            'is_export' => (bool) ($data['is_export'] ?? false),
            'composition' => (bool) $tenant?->isComposition(),
            'reverse_charge' => (bool) ($data['reverse_charge'] ?? false),
            'with_payment_of_tax' => (bool) ($data['with_payment_of_tax'] ?? false),
            'supply_type' => 'B2B',
            'shipping_charge' => (float) ($data['shipping_charge'] ?? 0),
            'round_off' => true,
        ], $data['lines'] ?? []);

        return (float) ($computed['totals']['grand_total'] ?? 0);
    }
}
