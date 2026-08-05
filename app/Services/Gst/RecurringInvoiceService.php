<?php

namespace App\Services\Gst;

use App\Models\Document;
use App\Models\RecurringInvoice;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Turns a recurring schedule into real documents.
 *
 * A schedule stores a *template* — the raw header flags and lines a user would
 * otherwise type into the invoice form. Every run re-feeds that template
 * through DocumentService, so numbering, stock, GST splits and FY series all
 * behave exactly like a manually created invoice.
 */
class RecurringInvoiceService
{
    /** Safety valve so a broken schedule can never loop forever. */
    protected const MAX_CATCHUP_RUNS = 24;

    public function __construct(protected DocumentService $documents) {}

    /**
     * Build the template snapshot from an already issued document — powers the
     * "Make recurring" shortcut on the invoice screen.
     */
    public function templateFromDocument(Document $document): array
    {
        $document->loadMissing('lines');

        return [
            'place_of_supply_code' => $document->place_of_supply_code,
            'reverse_charge' => (bool) $document->reverse_charge,
            'is_export' => (bool) $document->is_export,
            'with_payment_of_tax' => (bool) $document->with_payment_of_tax,
            'shipping_charge' => (float) $document->shipping_charge,
            'round_off_enabled' => (bool) $document->round_off_enabled,
            'notes' => $document->notes,
            'terms' => $document->terms,
            'estimated_total' => (float) $document->grand_total,
            'lines' => $document->lines->map(fn ($l) => [
                'item_id' => $l->item_id,
                'name' => $l->name,
                'description' => $l->description,
                'hsn_sac' => $l->hsn_sac,
                'uqc' => $l->uqc ?? 'NOS',
                'quantity' => (float) $l->quantity,
                'rate' => (float) $l->rate,
                'discount_percent' => (float) $l->discount_percent,
                'gst_rate' => (float) $l->gst_rate,
                'cess_rate' => (float) $l->cess_rate,
            ])->all(),
        ];
    }

    /**
     * Generate every schedule due on or before $asOf across all tenants.
     * Returns the number of documents created.
     */
    public function generateDue(?Carbon $asOf = null): int
    {
        $asOf ??= now();
        $created = 0;

        $schedules = RecurringInvoice::withoutGlobalScope('tenant')
            ->with('tenant')
            ->active()
            ->due($asOf)
            ->orderBy('next_run_on')
            ->get();

        foreach ($schedules as $schedule) {
            if (! $schedule->tenant) {
                continue;
            }

            $created += TenantContext::run($schedule->tenant,
                fn () => $this->catchUp($schedule, $asOf));
        }

        return $created;
    }

    /**
     * Generate one document per missed run until the schedule is caught up
     * with $asOf, pauses, or completes. Returns documents created count.
     * Must be called with the owning tenant active in TenantContext.
     */
    public function catchUp(RecurringInvoice $schedule, ?Carbon $asOf = null): int
    {
        $asOf ??= now();
        $runs = 0;

        while ($schedule->status === 'active'
            && $schedule->next_run_on->lte($asOf)
            && $runs < self::MAX_CATCHUP_RUNS) {
            // An end date moved into the past after the next run was set:
            // stop cleanly instead of issuing one invoice too many.
            if ($schedule->isExhausted()) {
                $schedule->update(['status' => 'completed']);
                break;
            }

            if (! $this->generate($schedule, $schedule->next_run_on->copy())) {
                break;
            }
            $runs++;
        }

        return $runs;
    }

    /**
     * Create the document for a single run dated $issueDate and advance the
     * schedule. Returns the document, or null when the run must not happen.
     */
    public function generate(RecurringInvoice $schedule, ?Carbon $issueDate = null): ?Document
    {
        if (! in_array($schedule->status, ['active'], true)) {
            return null;
        }

        if ($schedule->isExhausted()) {
            $schedule->update(['status' => 'completed']);

            return null;
        }

        // Console / queue context has no tenant — re-run inside the owning one.
        if (TenantContext::id() !== $schedule->tenant_id) {
            return TenantContext::run($schedule->tenant,
                fn () => $this->generate($schedule, $issueDate));
        }

        $issueDate ??= $schedule->next_run_on->copy();
        $template = $schedule->template ?? [];

        if (empty($template['lines'])) {
            // Nothing meaningful to bill — pause so someone reviews it.
            $schedule->update(['status' => 'paused']);

            return null;
        }

        return DB::transaction(function () use ($schedule, $issueDate, $template) {
            $dueDate = $schedule->due_days !== null
                ? $issueDate->copy()->addDays((int) $schedule->due_days)
                : null; // DocumentService falls back to the party's credit days.

            $document = $this->documents->create([
                'doc_type' => in_array($schedule->doc_type, ['invoice', 'bill_of_supply'], true)
                    ? $schedule->doc_type
                    : 'invoice',
                'tax_mode' => $schedule->tax_mode,
                'party_id' => $schedule->party_id,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'place_of_supply_code' => $template['place_of_supply_code'] ?? null,
                'reverse_charge' => (bool) ($template['reverse_charge'] ?? false),
                'is_export' => (bool) ($template['is_export'] ?? false),
                'with_payment_of_tax' => (bool) ($template['with_payment_of_tax'] ?? false),
                'shipping_charge' => (float) ($template['shipping_charge'] ?? 0),
                'round_off_enabled' => (bool) ($template['round_off_enabled'] ?? true),
                'notes' => $template['notes'] ?? null,
                'terms' => $template['terms'] ?? null,
                'lines' => $template['lines'] ?? [],
                'status' => $schedule->auto_finalize ? 'sent' : 'draft',
                'source' => 'recurring',
            ]);

            $document->recurring_invoice_id = $schedule->id;
            $document->meta = array_merge($document->meta ?? [], ['generated_by' => 'recurring:'.$schedule->id]);
            $document->save();

            $schedule->generated_count = (int) $schedule->generated_count + 1;
            $schedule->last_generated_at = now();
            $schedule->next_run_on = $schedule->runAfter($issueDate);

            if ($schedule->isExhausted()) {
                $schedule->status = 'completed';
            }

            $schedule->save();

            return $document;
        });
    }
}
