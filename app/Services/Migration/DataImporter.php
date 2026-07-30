<?php

namespace App\Services\Migration;

use App\Models\Document;
use App\Models\DocumentLine;
use App\Models\Expense;
use App\Models\ImportJob;
use App\Models\Item;
use App\Models\Party;
use App\Models\Payment;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handles importing data from a parsed JSON backup into the database.
 *
 * Features:
 * - Imports in dependency order (parties before documents before lines)
 * - Uses database transactions
 * - Preserves legacy IDs via a mapping table
 * - Idempotent: running the same file twice won't create duplicates
 * - Handles field name translation from legacy formats
 */
class DataImporter
{
    protected BackupParser $parser;
    protected MigrationMapper $mapper;

    /** Legacy ID → new ID maps keyed by section */
    protected array $idMap = [];

    /** Running tally */
    protected array $stats = [
        'imported' => 0,
        'updated' => 0,
        'skipped' => 0,
        'failed' => 0,
    ];

    protected array $errors = [];
    protected int $tenantId;

    public function __construct(BackupParser $parser, MigrationMapper $mapper)
    {
        $this->parser = $parser;
        $this->mapper = $mapper;
    }

    /**
     * Run the full import process.
     */
    public function import(ImportJob $job): array
    {
        $this->tenantId = TenantContext::id() ?: $job->tenant_id;
        $this->stats = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        $this->errors = [];
        $this->idMap = [];

        $job->markImporting();

        try {
            $data = $this->parser->loadData($job);
            $mapping = $job->mapping ?? [];
            $order = $this->mapper->getImportOrder($mapping);

            // Calculate total records
            $totalRecords = 0;
            foreach ($order as $section) {
                $totalRecords += count($data[$section] ?? []);
            }

            $processed = 0;

            foreach ($order as $section) {
                if (!isset($data[$section]) || !is_array($data[$section])) {
                    continue;
                }

                $records = $data[$section];
                $action = $mapping[$section]['action'] ?? 'import';

                $job->updateProgress(
                    $mapping[$section]['label'] ?? $section,
                    $processed,
                    $totalRecords
                );

                $result = $this->importSection($section, $records, $action);

                $this->stats['imported'] += $result['imported'];
                $this->stats['updated'] += $result['updated'];
                $this->stats['skipped'] += $result['skipped'];
                $this->stats['failed'] += $result['failed'];

                $processed += count($records);
                $job->updateProgress(
                    $mapping[$section]['label'] ?? $section,
                    $processed,
                    $totalRecords
                );
            }

            $job->markCompleted($this->stats);

            // Clean up temp file
            $this->parser->cleanup($job);

            return [
                'success' => true,
                'stats' => $this->stats,
                'errors' => $this->errors,
            ];
        } catch (\Throwable $e) {
            Log::error('Import failed: ' . $e->getMessage(), [
                'job_id' => $job->id,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->errors[] = [
                'section' => 'general',
                'message' => $e->getMessage(),
                'severity' => 'error',
            ];

            $job->markFailed($this->errors);

            return [
                'success' => false,
                'stats' => $this->stats,
                'errors' => $this->errors,
            ];
        }
    }

    /**
     * Import a single section with transaction support.
     */
    protected function importSection(string $section, array $records, string $action): array
    {
        $stats = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];

        DB::beginTransaction();

        try {
            foreach ($records as $index => $record) {
                try {
                    $result = $this->importRecord($section, $record, $action);
                    $stats[$result]++;
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    $this->errors[] = [
                        'section' => $section,
                        'record_index' => $index,
                        'record_id' => $record['id'] ?? $index,
                        'message' => $e->getMessage(),
                        'severity' => 'error',
                    ];
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $stats;
    }

    /**
     * Import a single record.
     * Returns: 'imported', 'updated', or 'skipped'
     */
    protected function importRecord(string $section, array $record, string $action): string
    {
        return match (true) {
            in_array($section, ['parties', 'clients', 'customers', 'suppliers', 'vendors'])
                => $this->importParty($record, $action, $section),
            in_array($section, ['items', 'products', 'services'])
                => $this->importItem($record, $action),
            in_array($section, ['documents', 'invoices', 'quotations', 'bills'])
                => $this->importDocument($record, $action, $section),
            in_array($section, ['document_lines', 'invoice_items', 'line_items', 'items_detail'])
                => $this->importDocumentLine($record, $action, $section),
            in_array($section, ['payments', 'transactions'])
                => $this->importPayment($record, $action),
            in_array($section, ['expenses', 'bills_payable'])
                => $this->importExpense($record, $action),
            in_array($section, ['warehouses', 'locations'])
                => $this->importWarehouse($record, $action),
            in_array($section, ['stock_movements', 'inventory_logs'])
                => $this->importStockMovement($record, $action),
            in_array($section, ['settings', 'brands'])
                => $this->importSettings($record, $action),
            default => 'skipped',
        };
    }

    // =========================================================================
    // Party Import
    // =========================================================================

    protected function importParty(array $record, string $action, string $section): string
    {
        $legacyId = $record['id'] ?? null;
        $fieldMap = $this->mapper->getFieldMapping('parties');
        $data = $this->mapFields($record, $fieldMap);

        // Determine party type based on source section
        $typeMap = [
            'clients' => 'customer',
            'customers' => 'customer',
            'suppliers' => 'vendor',
            'vendors' => 'vendor',
            'parties' => $data['type'] ?? 'customer',
        ];
        $data['type'] = $typeMap[$section] ?? 'customer';

        // Required fields
        if (empty($data['name'])) {
            throw new \RuntimeException('Party name is required.');
        }

        // Check for existing party (idempotency)
        $existing = $this->findExistingParty($data, $legacyId);

        if ($existing && $action === 'import') {
            // Already exists and action is "import as new" — skip to avoid duplicates
            $this->storeIdMapping('parties', $legacyId, $existing->id);
            return 'skipped';
        }

        if ($existing && $action === 'merge') {
            $existing->update($this->cleanPartyData($data));
            $this->storeIdMapping('parties', $legacyId, $existing->id);
            return 'updated';
        }

        // Create new
        $party = Party::create(array_merge(
            $this->cleanPartyData($data),
            ['tenant_id' => $this->tenantId]
        ));

        $this->storeIdMapping('parties', $legacyId, $party->id);

        return 'imported';
    }

    protected function findExistingParty(array $data, $legacyId): ?Party
    {
        // First try by GSTIN (most unique)
        if (!empty($data['gstin'])) {
            $existing = Party::where('tenant_id', $this->tenantId)
                ->where('gstin', $data['gstin'])
                ->first();
            if ($existing) return $existing;
        }

        // Then try by email
        if (!empty($data['email'])) {
            $existing = Party::where('tenant_id', $this->tenantId)
                ->where('email', $data['email'])
                ->first();
            if ($existing) return $existing;
        }

        // Then try by exact name
        if (!empty($data['name'])) {
            $existing = Party::where('tenant_id', $this->tenantId)
                ->where('name', $data['name'])
                ->first();
            if ($existing) return $existing;
        }

        return null;
    }

    protected function cleanPartyData(array $data): array
    {
        $allowed = [
            'type', 'name', 'contact_person', 'email', 'phone', 'gstin', 'pan',
            'gst_treatment', 'billing_address', 'billing_city', 'billing_state',
            'billing_state_code', 'billing_pincode', 'billing_country',
            'shipping_address', 'opening_balance', 'credit_days',
        ];

        return array_intersect_key($data, array_flip($allowed));
    }

    // =========================================================================
    // Item Import
    // =========================================================================

    protected function importItem(array $record, string $action): string
    {
        $legacyId = $record['id'] ?? null;
        $fieldMap = $this->mapper->getFieldMapping('items');
        $data = $this->mapFields($record, $fieldMap);

        if (empty($data['name'])) {
            throw new \RuntimeException('Item name is required.');
        }

        // Normalize type
        if (isset($data['type'])) {
            $data['type'] = match (strtolower($data['type'])) {
                'service', 'services' => 'service',
                default => 'goods',
            };
        }

        // Check for existing (idempotency)
        $existing = $this->findExistingItem($data, $legacyId);

        if ($existing && $action === 'import') {
            $this->storeIdMapping('items', $legacyId, $existing->id);
            return 'skipped';
        }

        if ($existing && $action === 'merge') {
            $existing->update($this->cleanItemData($data));
            $this->storeIdMapping('items', $legacyId, $existing->id);
            return 'updated';
        }

        $item = Item::create(array_merge(
            $this->cleanItemData($data),
            ['tenant_id' => $this->tenantId]
        ));

        $this->storeIdMapping('items', $legacyId, $item->id);

        return 'imported';
    }

    protected function findExistingItem(array $data, $legacyId): ?Item
    {
        // By SKU
        if (!empty($data['sku'])) {
            $existing = Item::where('tenant_id', $this->tenantId)
                ->where('sku', $data['sku'])
                ->first();
            if ($existing) return $existing;
        }

        // By name
        if (!empty($data['name'])) {
            $existing = Item::where('tenant_id', $this->tenantId)
                ->where('name', $data['name'])
                ->first();
            if ($existing) return $existing;
        }

        return null;
    }

    protected function cleanItemData(array $data): array
    {
        $allowed = [
            'name', 'sku', 'barcode', 'description', 'type', 'hsn_sac', 'uqc',
            'sale_price', 'purchase_price', 'gst_rate', 'cess_rate',
            'price_inclusive_tax', 'track_inventory', 'opening_stock', 'reorder_level',
        ];

        return array_intersect_key($data, array_flip($allowed));
    }

    // =========================================================================
    // Document Import
    // =========================================================================

    protected function importDocument(array $record, string $action, string $section): string
    {
        $legacyId = $record['id'] ?? null;
        $fieldMap = $this->mapper->getFieldMapping('documents');
        $data = $this->mapFields($record, $fieldMap);

        if (empty($data['number']) || empty($data['issue_date'])) {
            throw new \RuntimeException('Document number and issue_date are required.');
        }

        // Map doc_type from section name if not set
        if (empty($data['doc_type'])) {
            $data['doc_type'] = match ($section) {
                'quotations' => 'quotation',
                'proformas' => 'proforma',
                'purchase_orders', 'purchase_bills' => $section,
                'credit_notes' => 'credit_note',
                'debit_notes' => 'debit_note',
                default => 'invoice',
            };
        }

        // Resolve party_id from legacy ID mapping
        $data['party_id'] = $this->resolveId('parties', $data['party_id'] ?? null);

        // Check for existing (idempotency) — unique on [tenant_id, doc_type, number]
        $existing = Document::where('tenant_id', $this->tenantId)
            ->where('doc_type', $data['doc_type'])
            ->where('number', $data['number'])
            ->first();

        if ($existing && $action === 'import') {
            $this->storeIdMapping('documents', $legacyId, $existing->id);
            return 'skipped';
        }

        if ($existing && $action === 'merge') {
            $existing->update($this->cleanDocumentData($data));
            $this->storeIdMapping('documents', $legacyId, $existing->id);
            return 'updated';
        }

        $doc = Document::create(array_merge(
            $this->cleanDocumentData($data),
            [
                'tenant_id' => $this->tenantId,
                'source' => 'migration',
            ]
        ));

        $this->storeIdMapping('documents', $legacyId, $doc->id);

        return 'imported';
    }

    protected function cleanDocumentData(array $data): array
    {
        $allowed = [
            'doc_type', 'number', 'issue_date', 'due_date', 'party_id', 'parent_id',
            'status', 'supply_type', 'place_of_supply_code', 'is_interstate',
            'reverse_charge', 'is_export', 'with_payment_of_tax', 'currency',
            'exchange_rate', 'round_off_enabled', 'subtotal', 'discount_total',
            'taxable_value', 'cgst_total', 'sgst_total', 'igst_total', 'cess_total',
            'tds_total', 'tcs_total', 'shipping_charge', 'round_off', 'grand_total',
            'amount_paid', 'balance_due', 'notes', 'terms', 'meta', 'tax_mode',
        ];

        return array_intersect_key($data, array_flip($allowed));
    }

    // =========================================================================
    // Document Line Import
    // =========================================================================

    protected function importDocumentLine(array $record, string $action, string $section): string
    {
        $legacyId = $record['id'] ?? null;
        $fieldMap = $this->mapper->getFieldMapping('document_lines');
        $data = $this->mapFields($record, $fieldMap);

        // Resolve relationships
        $data['document_id'] = $this->resolveId('documents', $data['document_id'] ?? null);
        $data['item_id'] = $this->resolveId('items', $data['item_id'] ?? null);

        if (empty($data['document_id'])) {
            throw new \RuntimeException('Document reference is required for line items.');
        }

        if (empty($data['name'])) {
            throw new \RuntimeException('Line item name is required.');
        }

        // For idempotency: check if this exact line already exists
        if ($action === 'import') {
            $existing = DocumentLine::where('document_id', $data['document_id'])
                ->where('name', $data['name'])
                ->where('rate', $data['rate'] ?? 0)
                ->where('quantity', $data['quantity'] ?? 1)
                ->first();

            if ($existing) {
                $this->storeIdMapping('document_lines', $legacyId, $existing->id);
                return 'skipped';
            }
        }

        $line = DocumentLine::create($this->cleanDocumentLineData($data));
        $this->storeIdMapping('document_lines', $legacyId, $line->id);

        return 'imported';
    }

    protected function cleanDocumentLineData(array $data): array
    {
        $allowed = [
            'document_id', 'item_id', 'position', 'name', 'description', 'hsn_sac',
            'uqc', 'quantity', 'rate', 'discount_percent', 'discount_amount',
            'taxable_value', 'gst_rate', 'cgst_amount', 'sgst_amount', 'igst_amount',
            'cess_rate', 'cess_amount', 'line_total',
        ];

        return array_intersect_key($data, array_flip($allowed));
    }

    // =========================================================================
    // Payment Import
    // =========================================================================

    protected function importPayment(array $record, string $action): string
    {
        $legacyId = $record['id'] ?? null;
        $fieldMap = $this->mapper->getFieldMapping('payments');
        $data = $this->mapFields($record, $fieldMap);

        // Resolve relationships
        $data['document_id'] = $this->resolveId('documents', $data['document_id'] ?? null);
        $data['party_id'] = $this->resolveId('parties', $data['party_id'] ?? null);

        if (empty($data['amount']) || empty($data['paid_on'])) {
            throw new \RuntimeException('Payment amount and date are required.');
        }

        // Idempotency: check by amount + date + reference + document
        $query = Payment::where('tenant_id', $this->tenantId)
            ->where('amount', $data['amount'])
            ->where('paid_on', $data['paid_on']);

        if (!empty($data['reference'])) {
            $query->where('reference', $data['reference']);
        }

        if (!empty($data['document_id'])) {
            $query->where('document_id', $data['document_id']);
        }

        $existing = $query->first();

        if ($existing) {
            $this->storeIdMapping('payments', $legacyId, $existing->id);
            return $action === 'merge' ? 'updated' : 'skipped';
        }

        $payment = Payment::create(array_merge(
            $this->cleanPaymentData($data),
            ['tenant_id' => $this->tenantId]
        ));

        $this->storeIdMapping('payments', $legacyId, $payment->id);

        return 'imported';
    }

    protected function cleanPaymentData(array $data): array
    {
        $allowed = [
            'document_id', 'party_id', 'direction', 'amount', 'paid_on',
            'method', 'reference', 'note',
        ];

        return array_intersect_key($data, array_flip($allowed));
    }

    // =========================================================================
    // Expense Import
    // =========================================================================

    protected function importExpense(array $record, string $action): string
    {
        $legacyId = $record['id'] ?? null;
        $fieldMap = $this->mapper->getFieldMapping('expenses');
        $data = $this->mapFields($record, $fieldMap);

        // Resolve relationships
        $data['party_id'] = $this->resolveId('parties', $data['party_id'] ?? null);

        if (empty($data['amount']) || empty($data['spent_on'])) {
            throw new \RuntimeException('Expense amount and date are required.');
        }

        // Idempotency
        $query = Expense::where('tenant_id', $this->tenantId)
            ->where('amount', $data['amount'])
            ->where('spent_on', $data['spent_on']);

        if (!empty($data['reference'])) {
            $query->where('reference', $data['reference']);
        }

        $existing = $query->first();

        if ($existing) {
            $this->storeIdMapping('expenses', $legacyId, $existing->id);
            return $action === 'merge' ? 'updated' : 'skipped';
        }

        $expense = Expense::create(array_merge(
            $this->cleanExpenseData($data),
            ['tenant_id' => $this->tenantId]
        ));

        $this->storeIdMapping('expenses', $legacyId, $expense->id);

        return 'imported';
    }

    protected function cleanExpenseData(array $data): array
    {
        $allowed = [
            'party_id', 'category', 'spent_on', 'amount', 'gst_amount',
            'itc_eligible', 'reference', 'note',
        ];

        return array_intersect_key($data, array_flip($allowed));
    }

    // =========================================================================
    // Warehouse Import
    // =========================================================================

    protected function importWarehouse(array $record, string $action): string
    {
        $legacyId = $record['id'] ?? null;
        $data = $record;

        if (empty($data['name'])) {
            throw new \RuntimeException('Warehouse name is required.');
        }

        $existing = Warehouse::where('tenant_id', $this->tenantId)
            ->where('name', $data['name'])
            ->first();

        if ($existing) {
            $this->storeIdMapping('warehouses', $legacyId, $existing->id);
            if ($action === 'merge') {
                $existing->update([
                    'state_code' => $data['state_code'] ?? $existing->state_code,
                    'address' => $data['address'] ?? $existing->address,
                ]);
                return 'updated';
            }
            return 'skipped';
        }

        $warehouse = Warehouse::create([
            'tenant_id' => $this->tenantId,
            'name' => $data['name'],
            'state_code' => $data['state_code'] ?? null,
            'address' => $data['address'] ?? null,
            'is_default' => $data['is_default'] ?? false,
        ]);

        $this->storeIdMapping('warehouses', $legacyId, $warehouse->id);

        return 'imported';
    }

    // =========================================================================
    // Stock Movement Import
    // =========================================================================

    protected function importStockMovement(array $record, string $action): string
    {
        $legacyId = $record['id'] ?? null;
        $data = $record;

        // Resolve relationships
        $data['item_id'] = $this->resolveId('items', $data['item_id'] ?? null);
        $data['warehouse_id'] = $this->resolveId('warehouses', $data['warehouse_id'] ?? null);

        if (empty($data['item_id']) || empty($data['direction']) || empty($data['quantity'])) {
            throw new \RuntimeException('Item, direction, and quantity are required for stock movements.');
        }

        // Idempotency: skip if same movement already exists
        $existing = StockMovement::where('tenant_id', $this->tenantId)
            ->where('item_id', $data['item_id'])
            ->where('direction', $data['direction'])
            ->where('quantity', $data['quantity'])
            ->where('moved_on', $data['moved_on'] ?? now()->toDateString())
            ->first();

        if ($existing) {
            $this->storeIdMapping('stock_movements', $legacyId, $existing->id);
            return 'skipped';
        }

        $movement = StockMovement::create([
            'tenant_id' => $this->tenantId,
            'item_id' => $data['item_id'],
            'warehouse_id' => $data['warehouse_id'],
            'direction' => $data['direction'],
            'quantity' => $data['quantity'],
            'rate' => $data['rate'] ?? 0,
            'moved_on' => $data['moved_on'] ?? now()->toDateString(),
            'note' => $data['note'] ?? null,
        ]);

        $this->storeIdMapping('stock_movements', $legacyId, $movement->id);

        return 'imported';
    }

    // =========================================================================
    // Settings Import (special — updates the tenant)
    // =========================================================================

    protected function importSettings(array $record, string $action): string
    {
        // Settings are merged into the tenant's settings array
        $tenant = \App\Models\Tenant::find($this->tenantId);

        if (!$tenant) {
            throw new \RuntimeException('Tenant not found.');
        }

        if ($action === 'skip') {
            return 'skipped';
        }

        $currentSettings = $tenant->settings ?? [];
        $importedSettings = [];

        // Extract meaningful settings from the backup
        $settingKeys = ['invoice_terms', 'invoice_notes', 'default_currency', 'fiscal_year_start',
            'payment_terms', 'company_email', 'company_phone', 'website'];

        foreach ($settingKeys as $key) {
            if (isset($record[$key])) {
                $importedSettings[$key] = $record[$key];
            }
        }

        // Also accept nested 'settings' key
        if (isset($record['settings']) && is_array($record['settings'])) {
            $importedSettings = array_merge($importedSettings, $record['settings']);
        }

        if (empty($importedSettings)) {
            return 'skipped';
        }

        if ($action === 'merge') {
            $tenant->update(['settings' => array_merge($currentSettings, $importedSettings)]);
            return 'updated';
        }

        // Import as new: overwrite
        $tenant->update(['settings' => $importedSettings]);
        return 'imported';
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Map legacy field names to canonical names.
     */
    protected function mapFields(array $record, array $fieldMap): array
    {
        $mapped = [];

        foreach ($record as $key => $value) {
            $canonicalKey = $fieldMap[$key] ?? $key;
            // Don't overwrite already-mapped values (canonical wins)
            if (!isset($mapped[$canonicalKey]) || !isset($record[$canonicalKey])) {
                $mapped[$canonicalKey] = $value;
            }
        }

        return $mapped;
    }

    /**
     * Store a legacy-to-new ID mapping.
     */
    protected function storeIdMapping(string $section, $legacyId, int $newId): void
    {
        if ($legacyId !== null) {
            $this->idMap[$section][$legacyId] = $newId;
        }
    }

    /**
     * Resolve a legacy ID to a new ID using the mapping table.
     */
    protected function resolveId(string $section, $legacyId): ?int
    {
        if ($legacyId === null) {
            return null;
        }

        return $this->idMap[$section][$legacyId] ?? null;
    }
}
