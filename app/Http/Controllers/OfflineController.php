<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentRequest;
use App\Models\Document;
use App\Models\Item;
use App\Models\Party;
use App\Services\Gst\DocumentService;
use App\Support\TenantContext;
use Illuminate\Http\Request;

/**
 * Offline billing: a cached page for billing without internet, a catalog
 * snapshot for the page, and an idempotent sync endpoint for the outbox.
 */
class OfflineController extends Controller
{
    /** Lightweight billing page (runtime-cached by the service worker). */
    public function create()
    {
        $tenant = TenantContext::get();

        return view('offline.create', [
            'tenant' => $tenant,
            'catalog' => $this->catalogPayload($tenant),
        ]);
    }

    /**
     * A fresh CSRF token.
     *
     * The offline billing page may be served from the service-worker cache long
     * after the session was regenerated, so its embedded token can be stale.
     * The client calls this before syncing and retries once.
     */
    public function token()
    {
        return response()->json(['token' => csrf_token()]);
    }

    /** Catalog snapshot for offline use: parties + items + tax profile. */
    public function catalog()
    {
        return response()->json(
            $this->catalogPayload(TenantContext::get()) + ['synced_at' => now()->toIso8601String()]
        );
    }

    /** Sync one or many offline-created invoices. Idempotent via client_uuid. */
    public function sync(Request $request, DocumentService $service)
    {
        $data = $request->validate([
            'invoices' => ['required', 'array', 'min:1', 'max:50'],
            'invoices.*.client_uuid' => ['required', 'uuid'],
        ]);

        $tenant = TenantContext::get();
        $results = [];

        foreach ($data['invoices'] as $inv) {
            $results[] = $this->syncOne($tenant, $inv, $service);
        }

        return response()->json(['results' => $results]);
    }

    protected function syncOne($tenant, array $inv, DocumentService $service): array
    {
        $uuid = $inv['client_uuid'];

        // Already synced (e.g. retried) → return the existing number.
        $existing = Document::where('tenant_id', $tenant->id)->where('client_uuid', $uuid)->first();
        if ($existing) {
            return ['client_uuid' => $uuid, 'ok' => true, 'number' => $existing->number, 'duplicate' => true];
        }

        // Walk-in cash sale: create the customer on the fly.
        if (empty($inv['party_id']) && ! empty($inv['party_name'])) {
            $party = Party::firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => trim($inv['party_name'])],
                ['type' => 'customer', 'gst_treatment' => 'consumer']
            );
            $inv['party_id'] = $party->id;
        }

        // Reuse the exact online validation rules (merged so closures see input).
        $probe = tap(new DocumentRequest, fn ($r) => $r->merge($inv));
        $validator = validator($inv, $probe->rules());
        if ($validator->fails()) {
            return ['client_uuid' => $uuid, 'ok' => false, 'errors' => $validator->errors()->toArray()];
        }
        $clean = $validator->validated();

        // Cross-tenant guards: party/items must belong to this business.
        if (! empty($clean['party_id'])
            && ! Party::where('tenant_id', $tenant->id)->where('id', $clean['party_id'])->exists()) {
            return ['client_uuid' => $uuid, 'ok' => false, 'errors' => ['party_id' => ['Invalid customer.']]];
        }
        foreach ($clean['lines'] as $line) {
            if (! empty($line['item_id'])
                && ! Item::where('tenant_id', $tenant->id)->where('id', $line['item_id'])->exists()) {
                return ['client_uuid' => $uuid, 'ok' => false, 'errors' => ['lines' => ['Invalid item.']]];
            }
        }

        try {
            $document = $service->create($clean + [
                'doc_type' => 'invoice',
                'status' => 'sent',
                'source' => 'offline',
                'client_uuid' => $uuid,
            ]);

            $paid = (float) ($inv['payment']['amount'] ?? 0);
            if ($paid > 0) {
                $service->recordPayment($document, min($paid, (float) $document->grand_total), [
                    'method' => $inv['payment']['method'] ?? 'cash',
                    'paid_on' => $inv['payment']['paid_on'] ?? $document->issue_date->toDateString(),
                ]);
            }

            return [
                'client_uuid' => $uuid, 'ok' => true, 'number' => $document->number,
                'total' => (float) $document->grand_total,
            ];
        } catch (\Throwable $e) {
            report($e);

            // The most common real cause on a fresh deploy: the client_uuid
            // column (offline idempotency key) has not been migrated yet.
            $message = str_contains($e->getMessage(), 'client_uuid')
                ? 'Setup incomplete: run "php artisan migrate" to enable offline sync.'
                : (app()->isProduction() ? 'Could not save this bill. It stays queued and will retry.' : $e->getMessage());

            return ['client_uuid' => $uuid, 'ok' => false, 'errors' => ['_server' => [$message]]];
        }
    }

    protected function catalogPayload($tenant): array
    {
        return [
            'tenant' => [
                'id' => $tenant->id,
                'tax_mode' => $tenant->defaultTaxMode(),
                'state_code' => $tenant->state_code,
                'gstin' => $tenant->gstin,
            ],
            'slabs' => config('gst.slabs'),
            'parties' => Party::where('tenant_id', $tenant->id)->orderBy('name')
                ->get(['id', 'name', 'gstin', 'billing_state_code'])->toArray(),
            'items' => Item::where('tenant_id', $tenant->id)->orderBy('name')
                ->get(['id', 'name', 'sku', 'hsn_sac', 'uqc', 'sale_price', 'gst_rate', 'type'])->toArray(),
        ];
    }
}
