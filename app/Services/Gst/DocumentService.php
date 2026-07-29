<?php

namespace App\Services\Gst;

use App\Models\Document;
use App\Models\Item;
use App\Models\Party;
use App\Models\StockMovement;
use App\Support\Gstin;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentService
{
    public function __construct(
        protected TaxCalculator $calculator,
        protected DocumentNumberGenerator $numbers,
    ) {}

    public function create(array $data): Document
    {
        return DB::transaction(function () use ($data) {
            $tenant = TenantContext::get();
            $party = isset($data['party_id']) ? Party::find($data['party_id']) : null;
            $docType = $data['doc_type'] ?? 'invoice';
            $taxMode = $this->resolveTaxMode($data);
            $nonGst = $taxMode === 'non_gst';

            // Composition dealers must issue a Bill of Supply, never a tax invoice.
            if (! $nonGst && $tenant?->isComposition() && $docType === 'invoice') {
                $docType = 'bill_of_supply';
            }

            $issueDate = Carbon::parse($data['issue_date'] ?? now());
            $pos = $nonGst ? null : ($data['place_of_supply_code'] ?? $party?->resolvedStateCode() ?? $tenant?->state_code);
            $isExport = ! $nonGst && (bool) ($data['is_export'] ?? in_array($party?->gst_treatment, ['overseas'], true));

            $computed = $this->calculator->calculate([
                'tax_mode' => $taxMode,
                'seller_state_code' => $tenant?->state_code,
                'place_of_supply_code' => $pos,
                'is_export' => $isExport,
                'composition' => (bool) $tenant?->isComposition(),
                'reverse_charge' => (bool) ($data['reverse_charge'] ?? false),
                'with_payment_of_tax' => (bool) ($data['with_payment_of_tax'] ?? false),
                'supply_type' => $data['supply_type'] ?? $this->supplyType($party, $isExport),
                'shipping_charge' => $data['shipping_charge'] ?? 0,
                'tds_percent' => $data['tds_percent'] ?? 0,
                'tcs_percent' => $data['tcs_percent'] ?? 0,
                'round_off' => $data['round_off_enabled'] ?? true,
            ], $data['lines'] ?? []);

            $document = Document::create(array_merge($computed['totals'], [
                'doc_type' => $docType,
                'tax_mode' => $taxMode,
                'number' => $data['number'] ?? $this->numbers->next($docType, $issueDate, $taxMode),
                'issue_date' => $issueDate,
                'due_date' => $data['due_date'] ?? ($party?->credit_days ? $issueDate->copy()->addDays($party->credit_days) : null),
                'party_id' => $party?->id,
                'parent_id' => $data['parent_id'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'supply_type' => $computed['flags']['supply_type'],
                'place_of_supply_code' => $pos,
                'is_interstate' => $computed['flags']['is_interstate'],
                'reverse_charge' => (bool) ($data['reverse_charge'] ?? false),
                'is_export' => $isExport,
                'with_payment_of_tax' => (bool) ($data['with_payment_of_tax'] ?? false),
                'currency' => $data['currency'] ?? $tenant?->currency ?? 'INR',
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? $tenant?->setting('default_terms'),
                'meta' => $data['meta'] ?? null,
                'source' => $data['source'] ?? 'manual',
                'external_id' => $data['external_id'] ?? null,
                'created_by' => auth()->id(),
                'balance_due' => $computed['totals']['grand_total'],
            ]));

            $document->lines()->createMany($computed['lines']);

            if ($document->affectsStock() && ($data['status'] ?? 'draft') !== 'draft') {
                $this->applyStock($document);
            }

            return $document->load('lines.item', 'party');
        });
    }

    public function update(Document $document, array $data): Document
    {
        return DB::transaction(function () use ($document, $data) {
            $this->reverseStock($document);
            $document->lines()->delete();

            $tenant = TenantContext::get();
            $party = isset($data['party_id']) ? Party::find($data['party_id']) : $document->party;
            $taxMode = $data['tax_mode'] ?? $document->tax_mode ?? 'gst';
            $nonGst = $taxMode === 'non_gst';
            $pos = $nonGst ? null : ($data['place_of_supply_code'] ?? $party?->resolvedStateCode() ?? $tenant?->state_code);
            $isExport = ! $nonGst && (bool) ($data['is_export'] ?? $document->is_export);

            $computed = $this->calculator->calculate([
                'tax_mode' => $taxMode,
                'seller_state_code' => $tenant?->state_code,
                'place_of_supply_code' => $pos,
                'is_export' => $isExport,
                'composition' => (bool) $tenant?->isComposition(),
                'reverse_charge' => (bool) ($data['reverse_charge'] ?? $document->reverse_charge),
                'with_payment_of_tax' => (bool) ($data['with_payment_of_tax'] ?? $document->with_payment_of_tax),
                'supply_type' => $data['supply_type'] ?? $this->supplyType($party, $isExport),
                'shipping_charge' => $data['shipping_charge'] ?? $document->shipping_charge,
                'tds_percent' => $data['tds_percent'] ?? 0,
                'tcs_percent' => $data['tcs_percent'] ?? 0,
                'round_off' => $data['round_off_enabled'] ?? $document->round_off_enabled,
            ], $data['lines'] ?? []);

            $document->fill(array_merge($computed['totals'], [
                'party_id' => $party?->id,
                'tax_mode' => $taxMode,
                'issue_date' => $data['issue_date'] ?? $document->issue_date,
                'due_date' => $data['due_date'] ?? $document->due_date,
                'place_of_supply_code' => $pos,
                'is_interstate' => $computed['flags']['is_interstate'],
                'supply_type' => $computed['flags']['supply_type'],
                'notes' => $data['notes'] ?? $document->notes,
                'terms' => $data['terms'] ?? $document->terms,
                'status' => $data['status'] ?? $document->status,
            ]));
            $document->balance_due = max(0, $computed['totals']['grand_total'] - (float) $document->amount_paid);
            $document->save();

            $document->lines()->createMany($computed['lines']);

            if ($document->affectsStock() && $document->status !== 'draft') {
                $this->applyStock($document);
            }

            return $document->fresh(['lines.item', 'party']);
        });
    }

    /** Convert quote/proforma/PO into the next document in the chain. */
    public function convert(Document $source, string $targetType): Document
    {
        $lines = $source->lines->map(fn ($l) => [
            'item_id' => $l->item_id,
            'name' => $l->name,
            'description' => $l->description,
            'hsn_sac' => $l->hsn_sac,
            'uqc' => $l->uqc,
            'quantity' => (float) $l->quantity,
            'rate' => (float) $l->rate,
            'discount_percent' => (float) $l->discount_percent,
            'gst_rate' => (float) $l->gst_rate,
            'cess_rate' => (float) $l->cess_rate,
        ])->all();

        return $this->create([
            'doc_type' => $targetType,
            'tax_mode' => $source->tax_mode,
            'party_id' => $source->party_id,
            'parent_id' => $source->id,
            'issue_date' => now(),
            'place_of_supply_code' => $source->place_of_supply_code,
            'reverse_charge' => $source->reverse_charge,
            'is_export' => $source->is_export,
            'shipping_charge' => $source->shipping_charge,
            'notes' => $source->notes,
            'terms' => $source->terms,
            'lines' => $lines,
            'status' => 'sent',
        ]);
    }

    public function recordPayment(Document $document, float $amount, array $attrs = []): void
    {
        DB::transaction(function () use ($document, $amount, $attrs) {
            $document->payments()->create([
                'tenant_id' => $document->tenant_id,
                'party_id' => $document->party_id,
                'direction' => in_array($document->doc_type, ['purchase_bill'], true) ? 'out' : 'in',
                'amount' => $amount,
                'paid_on' => $attrs['paid_on'] ?? now(),
                'method' => $attrs['method'] ?? 'cash',
                'reference' => $attrs['reference'] ?? null,
                'note' => $attrs['note'] ?? null,
            ]);

            $paid = (float) $document->payments()->sum('amount');
            $document->amount_paid = $paid;
            $document->balance_due = max(0, (float) $document->grand_total - $paid);
            $document->status = $document->balance_due <= 0.009
                ? 'paid'
                : ($paid > 0 ? 'partially_paid' : $document->status);
            $document->save();
        });
    }

    public function cancel(Document $document, ?string $reason = null): void
    {
        DB::transaction(function () use ($document, $reason) {
            $this->reverseStock($document);
            $document->status = 'cancelled';
            $document->meta = array_merge($document->meta ?? [], ['cancel_reason' => $reason, 'cancelled_at' => now()->toIso8601String()]);
            $document->save();
        });
    }

    /**
     * Explicit request value wins; otherwise fall back to the tenant default.
     * A tenant without a GSTIN can only ever issue plain (non-GST) invoices.
     */
    protected function resolveTaxMode(array $data): string
    {
        $tenant = TenantContext::get();

        if (! $tenant?->gstin) {
            return 'non_gst';
        }

        $default = $tenant->tax_mode ?: 'gst';
        $mode = $data['tax_mode'] ?? $default;
        $allowBoth = $tenant->allow_both_modes ?? true;

        if ($mode !== $default && ! $allowBoth) {
            $mode = $default;
        }

        return in_array($mode, ['gst', 'non_gst'], true) ? $mode : 'gst';
    }

    protected function supplyType(?Party $party, bool $isExport): string
    {
        if ($isExport) return 'EXPORT';
        return match ($party?->gst_treatment) {
            'sez' => 'SEZ',
            'regular', 'composition' => 'B2B',
            default => $party?->gstin ? 'B2B' : 'B2C',
        };
    }

    protected function applyStock(Document $document): void
    {
        $direction = in_array($document->doc_type, ['purchase_bill', 'credit_note'], true) ? 'in' : 'out';

        foreach ($document->lines as $line) {
            if (! $line->item_id) continue;
            $item = Item::find($line->item_id);
            if (! $item?->track_inventory) continue;

            StockMovement::create([
                'tenant_id' => $document->tenant_id,
                'item_id' => $line->item_id,
                'direction' => $direction,
                'quantity' => $line->quantity,
                'rate' => $line->rate,
                'source_type' => Document::class,
                'source_id' => $document->id,
                'moved_on' => $document->issue_date,
                'note' => $document->title.' '.$document->number,
            ]);
        }
    }

    protected function reverseStock(Document $document): void
    {
        StockMovement::where('source_type', Document::class)->where('source_id', $document->id)->delete();
    }
}
