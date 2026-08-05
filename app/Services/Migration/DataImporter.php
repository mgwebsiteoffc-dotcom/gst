<?php

namespace App\Services\Migration;

use App\Models\Document;
use App\Models\DocumentLine;
use App\Models\Expense;
use App\Models\ImportJob;
use App\Models\Item;
use App\Models\MigrationRawData;
use App\Models\Party;
use App\Models\Payment;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataImporter
{
    protected BackupParser $parser;
    protected MigrationMapper $mapper;
    protected array $idMap = [];
    protected array $stats = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
    protected array $errors = [];
    protected int $tenantId;
    protected ?ImportJob $currentJob = null;
    protected ?string $sourceFile = null;

    /** Common tax_id → GST rate mappings from legacy apps */
    protected array $taxIdToRate = [
        0 => 0, 1 => 1, 2 => 3, 3 => 5, 5 => 5, 6 => 6, 12 => 12,
        14 => 14, 18 => 18, 28 => 28,
    ];

    public function __construct(BackupParser $parser, MigrationMapper $mapper)
    {
        $this->parser = $parser;
        $this->mapper = $mapper;
    }

    public function import(ImportJob $job): array
    {
        $this->tenantId = TenantContext::id() ?: $job->tenant_id;
        $this->stats = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        $this->errors = [];
        $this->idMap = [];
        $this->currentJob = $job;
        $this->sourceFile = $job->filename;

        $job->markImporting();

        try {
            $data = $this->parser->loadData($job);
            $mapping = $job->mapping ?? [];
            $order = $this->mapper->getImportOrder($mapping);

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

                $job->updateProgress($mapping[$section]['label'] ?? $section, $processed, $totalRecords);

                $result = $this->importSection($section, $records, $action);

                $this->stats['imported'] += $result['imported'];
                $this->stats['updated'] += $result['updated'];
                $this->stats['skipped'] += $result['skipped'];
                $this->stats['failed'] += $result['failed'];

                $processed += count($records);
                $job->updateProgress($mapping[$section]['label'] ?? $section, $processed, $totalRecords);
            }

            $job->markCompleted($this->stats);
            $this->parser->cleanup($job);

            return ['success' => true, 'stats' => $this->stats, 'errors' => $this->errors];
        } catch (\Throwable $e) {
            Log::error('Import failed: ' . $e->getMessage(), ['job_id' => $job->id, 'trace' => $e->getTraceAsString()]);
            $this->errors[] = ['section' => 'general', 'message' => $e->getMessage(), 'severity' => 'error'];
            $job->markFailed($this->errors);
            return ['success' => false, 'stats' => $this->stats, 'errors' => $this->errors];
        }
    }

    protected function importSection(string $section, array $records, string $action): array
    {
        $stats = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];

        DB::beginTransaction();
        try {
            foreach ($records as $index => $record) {
                // Store raw data for every record (searchable backup)
                $this->storeRawData($section, $record);

                // Skip deleted records from old system
                if (($record['deleted'] ?? '') === 'y') {
                    $stats['skipped']++;
                    $this->updateRawStatus($section, $record['id'] ?? null, 'skipped', 'Marked as deleted in source');
                    continue;
                }

                try {
                    $result = $this->importRecord($section, $record, $action);
                    $stats[$result]++;
                    $this->updateRawStatus($section, $record['id'] ?? null,
                        $result === 'skipped' ? 'skipped' : 'mapped',
                        $result === 'imported' ? 'Created new record' : ($result === 'updated' ? 'Updated existing' : 'Duplicate skipped')
                    );
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    $this->errors[] = [
                        'section' => $section,
                        'record_index' => $index,
                        'record_id' => $record['id'] ?? $index,
                        'message' => $e->getMessage(),
                        'severity' => 'error',
                    ];
                    $this->updateRawStatus($section, $record['id'] ?? null, 'failed', $e->getMessage());
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $stats;
    }

    protected function importRecord(string $section, array $record, string $action): string
    {
        return match (true) {
            in_array($section, ['parties', 'clients', 'customers', 'suppliers', 'vendors', 'contacts'])
                => $this->importParty($record, $action, $section),
            in_array($section, ['items', 'products', 'services'])
                => $this->importItem($record, $action),
            in_array($section, ['documents', 'invoices', 'quotations', 'bills'])
                => $this->importDocument($record, $action, $section),
            in_array($section, ['document_lines', 'invoice_items', 'invoice_products', 'line_items', 'items_detail'])
                => $this->importDocumentLine($record, $action, $section),
            in_array($section, ['payments', 'transactions'])
                => $this->importPayment($record, $action),
            in_array($section, ['payment_documents', 'payment_receipts'])
                => $this->importPaymentDocument($record, $action),
            in_array($section, ['invoice_payments', 'payment_links'])
                => $this->importInvoicePayment($record, $action),
            in_array($section, ['expenses', 'bills_payable'])
                => $this->importExpense($record, $action),
            in_array($section, ['warehouses', 'locations'])
                => $this->importWarehouse($record, $action),
            in_array($section, ['stock_movements', 'inventory_logs'])
                => $this->importStockMovement($record, $action),
            in_array($section, ['settings', 'brands', 'config', 'preferences'])
                => $this->importSettings($record, $action),
            default => 'skipped',
        };
    }

    // =========================================================================
    // RAW DATA STORAGE
    // =========================================================================

    protected function storeRawData(string $section, array $record): void
    {
        try {
            MigrationRawData::create([
                'tenant_id' => $this->tenantId,
                'import_job_id' => $this->currentJob?->id,
                'section' => $section,
                'source_file' => $this->sourceFile,
                'legacy_id' => $record['id'] ?? null,
                'data' => $record,
                'status' => 'imported',
            ]);
        } catch (\Throwable $e) {
            // Don't fail the import if raw storage fails
            Log::warning('Failed to store raw data', ['section' => $section, 'error' => $e->getMessage()]);
        }
    }

    protected function updateRawStatus(string $section, $legacyId, string $status, string $notes = ''): void
    {
        try {
            MigrationRawData::where('tenant_id', $this->tenantId)
                ->where('import_job_id', $this->currentJob?->id)
                ->where('section', $section)
                ->where('legacy_id', $legacyId)
                ->update(['status' => $status, 'notes' => $notes]);
        } catch (\Throwable $e) {
            // Non-critical
        }
    }

    // =========================================================================
    // Party Import
    // =========================================================================

    protected function importParty(array $record, string $action, string $section): string
    {
        $legacyId = $record['id'] ?? null;
        $fieldMap = $this->mapper->getFieldMapping('parties');
        $data = $this->mapFields($record, $fieldMap);

        $typeMap = [
            'clients' => 'customer', 'customers' => 'customer',
            'suppliers' => 'vendor', 'vendors' => 'vendor',
            'parties' => $data['type'] ?? 'customer',
        ];
        $data['type'] = $typeMap[$section] ?? 'customer';

        if (empty($data['name'])) {
            throw new \RuntimeException('Party name is required.');
        }

        $existing = $this->findExistingParty($data, $legacyId);

        if ($existing && $action === 'import') {
            $this->storeIdMapping('parties', $legacyId, $existing->id);
            return 'skipped';
        }

        if ($existing && $action === 'merge') {
            $existing->update($this->cleanPartyData($data));
            $this->storeIdMapping('parties', $legacyId, $existing->id);
            return 'updated';
        }

        $party = Party::create(array_merge(
            $this->cleanPartyData($data),
            ['tenant_id' => $this->tenantId]
        ));

        $this->storeIdMapping('parties', $legacyId, $party->id);
        return 'imported';
    }

    protected function findExistingParty(array $data, $legacyId): ?Party
    {
        if (!empty($data['gstin'])) {
            $e = Party::where('tenant_id', $this->tenantId)->where('gstin', $data['gstin'])->first();
            if ($e) return $e;
        }
        if (!empty($data['email'])) {
            $e = Party::where('tenant_id', $this->tenantId)->where('email', $data['email'])->first();
            if ($e) return $e;
        }
        if (!empty($data['name'])) {
            $e = Party::where('tenant_id', $this->tenantId)->where('name', $data['name'])->first();
            if ($e) return $e;
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
        return $this->withoutNulls(array_intersect_key($data, array_flip($allowed)));
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

        // Handle tax_id → gst_rate (legacy apps use tax_id like 18 for 18%)
        if (isset($record['tax_id']) && !isset($data['gst_rate'])) {
            $data['gst_rate'] = $this->resolveTaxRate($record['tax_id']);
        }

        // Normalize type: 's' = service, 'g' = goods
        if (isset($data['type'])) {
            $data['type'] = match (strtolower((string)$data['type'])) {
                's', 'service', 'services' => 'service',
                default => 'goods',
            };
        }

        // HSN or SAC — use whichever is available
        if (empty($data['hsn_sac'])) {
            $data['hsn_sac'] = $record['hsn'] ?? $record['sac'] ?? $record['hsn_code'] ?? null;
        }

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
        if (!empty($data['sku'])) {
            $e = Item::where('tenant_id', $this->tenantId)->where('sku', $data['sku'])->first();
            if ($e) return $e;
        }
        if (!empty($data['name'])) {
            $e = Item::where('tenant_id', $this->tenantId)->where('name', $data['name'])->first();
            if ($e) return $e;
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
        return $this->withoutNulls(array_intersect_key($data, array_flip($allowed)));
    }

    // =========================================================================
    // Document Import
    // =========================================================================

    protected function importDocument(array $record, string $action, string $section): string
    {
        $legacyId = $record['id'] ?? null;
        $fieldMap = $this->mapper->getFieldMapping('documents');
        $data = $this->mapFields($record, $fieldMap);

        // Skip cancelled/deleted
        if (($record['cancelled'] ?? 0) == 1) {
            throw new \RuntimeException('Document was cancelled in source system.');
        }

        if (empty($data['number']) || empty($data['issue_date'])) {
            throw new \RuntimeException('Document number and issue_date are required.');
        }

        // Determine doc_type
        if (empty($data['doc_type'])) {
            $data['doc_type'] = match ($section) {
                'quotations' => 'quotation',
                'proformas' => 'proforma',
                default => 'invoice',
            };
        }

        // Resolve party_id from legacy mapping
        if (!empty($record['client_id'])) {
            $data['party_id'] = $this->resolveId('parties', $record['client_id']);
        }

        // If no party_id resolved but client info is embedded, auto-create party
        if (empty($data['party_id']) && !empty($record['client_name'])) {
            $party = $this->autoCreatePartyFromInvoice($record);
            if ($party) {
                $data['party_id'] = $party->id;
                $this->storeIdMapping('parties', $record['client_id'] ?? null, $party->id);
            }
        }

        // Tax mode from gst_type field
        $data['tax_mode'] = match ($record['gst_type'] ?? 'g') {
            'g', 'gst' => 'gst',
            default => 'non_gst',
        };

        // Reverse charge
        $data['reverse_charge'] = (bool)($record['reverse_charges'] ?? 0);

        // Status
        if (empty($data['status'])) {
            $data['status'] = match (true) {
                ($record['is_paid'] ?? 0) == 1 => 'paid',
                ($record['is_draft'] ?? 0) == 1 => 'draft',
                ($record['sent'] ?? 0) == 1 => 'sent',
                default => 'sent',
            };
        }

        // Amount paid / balance
        $grandTotal = (float)($data['grand_total'] ?? $record['total_all'] ?? 0);
        $amountPaid = (float)($data['amount_paid'] ?? 0);
        if (($record['is_paid'] ?? 0) == 1) {
            $amountPaid = $grandTotal;
        }
        $data['amount_paid'] = $amountPaid;
        $data['balance_due'] = $grandTotal - $amountPaid;

        // Existing check
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
            ['tenant_id' => $this->tenantId, 'source' => 'migration']
        ));

        $this->storeIdMapping('documents', $legacyId, $doc->id);
        return 'imported';
    }

    /**
     * Auto-create a party from embedded client info in an invoice.
     */
    protected function autoCreatePartyFromInvoice(array $record): ?Party
    {
        $name = $record['client_name'] ?? null;
        if (empty($name)) return null;

        $existing = Party::where('tenant_id', $this->tenantId)->where('name', $name)->first();
        if ($existing) return $existing;

        return Party::create([
            'tenant_id' => $this->tenantId,
            'type' => 'customer',
            'name' => $name,
            'contact_person' => $record['client_contact'] ?? null,
            'email' => $record['client_email'] ?? null,
            'phone' => $record['client_telephone'] ?? null,
            'billing_address' => $record['client_billing_address'] ?? null,
            'billing_city' => $record['client_billing_city'] ?? null,
            'billing_pincode' => $record['client_billing_zip'] ?? null,
            'billing_country' => 'IN',
            'gst_treatment' => !empty($record['client_gstin']) ? 'regular' : 'consumer',
            'gstin' => $record['client_gstin'] ?? null,
        ]);
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
        return $this->withoutNulls(array_intersect_key($data, array_flip($allowed)));
    }

    // =========================================================================
    // Document Line Import
    // =========================================================================

    protected function importDocumentLine(array $record, string $action, string $section): string
    {
        $legacyId = $record['id'] ?? null;
        $fieldMap = $this->mapper->getFieldMapping('document_lines');
        $data = $this->mapFields($record, $fieldMap);

        // Resolve document_id from invoice_id
        $invoiceId = $data['document_id'] ?? $record['invoice_id'] ?? null;
        $data['document_id'] = $this->resolveId('documents', $invoiceId);

        // Resolve item_id from product_id
        $productId = $data['item_id'] ?? $record['product_id'] ?? null;
        $data['item_id'] = $this->resolveId('items', $productId);

        if (empty($data['document_id'])) {
            throw new \RuntimeException('Document reference is required for line items.');
        }

        if (empty($data['name'])) {
            throw new \RuntimeException('Line item name is required.');
        }

        // Handle tax_id → gst_rate
        if (isset($record['tax_id']) && !isset($data['gst_rate'])) {
            $data['gst_rate'] = $this->resolveTaxRate($record['tax_id']);
        }

        // Use pre-calculated totals from legacy system
        $gstRate = (float)($data['gst_rate'] ?? 0);
        $taxableValue = (float)($data['taxable_value'] ?? $record['total_no_tax'] ?? 0);
        $totalTax = (float)($record['total_tax'] ?? 0);
        $lineTotal = (float)($data['line_total'] ?? $record['total_all'] ?? 0);

        // Split GST into CGST/SGST or IGST based on rate
        $data['taxable_value'] = $taxableValue;
        $data['line_total'] = $lineTotal;

        if ($gstRate > 0) {
            $halfRate = $gstRate / 2;
            $data['cgst_amount'] = round($taxableValue * $halfRate / 100, 2);
            $data['sgst_amount'] = round($taxableValue * $halfRate / 100, 2);
            $data['igst_amount'] = 0;
        }

        // Cess
        if (isset($record['cess_percentage'])) {
            $data['cess_rate'] = (float)$record['cess_percentage'];
            $data['cess_amount'] = (float)($record['cess_value'] ?? 0);
        }

        // HSN/SAC
        if (empty($data['hsn_sac'])) {
            $data['hsn_sac'] = $record['hsn'] ?? $record['sac'] ?? null;
        }

        // Idempotency
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
        return $this->withoutNulls(array_intersect_key($data, array_flip($allowed)));
    }

    // =========================================================================
    // Payment Import
    // =========================================================================

    protected function importPayment(array $record, string $action): string
    {
        $legacyId = $record['id'] ?? null;
        $fieldMap = $this->mapper->getFieldMapping('payments');
        $data = $this->mapFields($record, $fieldMap);

        // Handle amount field variations
        $data['amount'] = $data['amount'] ?? $record['amount_payment'] ?? $record['amount'] ?? null;

        // Handle date field variations
        $data['paid_on'] = $data['paid_on'] ?? $record['pay_date'] ?? $record['payment_date'] ?? null;

        // Direction: 'r' = receipt (in), 'p' = payment (out)
        if (empty($data['direction'])) {
            $type = $record['type'] ?? 'r';
            $data['direction'] = in_array($type, ['r', 'receipt', 'in'], true) ? 'in' : 'out';
        }

        // Method from payment_type
        if (empty($data['method']) && isset($record['payment_type'])) {
            $data['method'] = match (strtolower($record['payment_type'])) {
                'bank transfer', 'neft', 'rtgs', 'imps' => 'bank',
                'cash' => 'cash',
                'cheque', 'check' => 'cheque',
                'upi' => 'upi',
                'card', 'credit card', 'debit card' => 'card',
                default => 'bank',
            };
        }

        // Fall back to the legacy receipt number so distinct same-day,
        // same-amount payments (e.g. two invoices paid together) don't
        // collide in the idempotency check below.
        $data['reference'] = $data['reference'] ?? $record['number'] ?? null;

        $data['document_id'] = $this->resolveId('documents', $data['document_id'] ?? null);
        $data['party_id'] = $this->resolveId('parties', $data['party_id'] ?? $record['client_id'] ?? null);

        if (empty($data['amount']) || empty($data['paid_on'])) {
            throw new \RuntimeException('Payment amount and date are required.');
        }

        // Idempotency
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
        return $this->withoutNulls(array_intersect_key($data, array_flip($allowed)));
    }

    // =========================================================================
    // Payment Document Import (standalone receipts)
    // =========================================================================

    protected function importPaymentDocument(array $record, string $action): string
    {
        $legacyId = $record['id'] ?? null;

        $amount = (float)($record['amount_payment'] ?? $record['amount'] ?? 0);
        $paidOn = $record['pay_date'] ?? $record['payment_date'] ?? null;
        $partyId = $this->resolveId('parties', $record['client_id'] ?? $record['vendor_id'] ?? null);

        if (empty($amount) || empty($paidOn)) {
            throw new \RuntimeException('Payment document requires amount and date.');
        }

        $method = match (strtolower($record['payment_type'] ?? '')) {
            'bank transfer', 'neft', 'rtgs', 'imps' => 'bank',
            'cash' => 'cash',
            'cheque', 'check' => 'cheque',
            'upi' => 'upi',
            default => 'bank',
        };

        $direction = ($record['type'] ?? 'r') === 'r' ? 'in' : 'out';

        // Idempotency
        $existing = Payment::where('tenant_id', $this->tenantId)
            ->where('amount', $amount)
            ->where('paid_on', $paidOn)
            ->where('reference', $record['reference'] ?? null)
            ->first();

        if ($existing) {
            $this->storeIdMapping('payment_documents', $legacyId, $existing->id);
            return 'skipped';
        }

        $payment = Payment::create([
            'tenant_id' => $this->tenantId,
            'party_id' => $partyId,
            'direction' => $direction,
            'amount' => $amount,
            'paid_on' => $paidOn,
            'method' => $method,
            'reference' => $record['reference'] ?? $record['number'] ?? null,
            'note' => $record['note'] ?? null,
        ]);

        $this->storeIdMapping('payment_documents', $legacyId, $payment->id);
        return 'imported';
    }

    // =========================================================================
    // Invoice-Payment Link Import
    // =========================================================================

    protected function importInvoicePayment(array $record, string $action): string
    {
        $legacyId = $record['id'] ?? null;

        $documentId = $this->resolveId('documents', $record['invoice_id'] ?? null);
        $paymentId = $this->resolveId('payments', $record['payment_id'] ?? null);
        $amount = (float)($record['amount'] ?? 0);

        if (!$documentId || !$amount) {
            return 'skipped';
        }

        // If we have a payment_id, link it; otherwise create a new payment
        if ($paymentId) {
            Payment::where('id', $paymentId)
                ->where('tenant_id', $this->tenantId)
                ->update(['document_id' => $documentId]);
            return 'updated';
        }

        // Create a payment record for this link
        $doc = Document::find($documentId);
        if (!$doc) return 'skipped';

        $existing = Payment::where('tenant_id', $this->tenantId)
            ->where('document_id', $documentId)
            ->where('amount', $amount)
            ->first();

        if ($existing) return 'skipped';

        Payment::create([
            'tenant_id' => $this->tenantId,
            'document_id' => $documentId,
            'party_id' => $doc->party_id,
            'direction' => 'in',
            'amount' => $amount,
            'paid_on' => $doc->issue_date,
            'method' => 'bank',
            'note' => 'Imported from legacy invoice-payment link',
        ]);

        return 'imported';
    }

    // =========================================================================
    // Expense, Warehouse, Stock, Settings (same as before)
    // =========================================================================

    protected function importExpense(array $record, string $action): string
    {
        $legacyId = $record['id'] ?? null;
        $data = $this->mapFields($record, $this->mapper->getFieldMapping('expenses'));
        $data['party_id'] = $this->resolveId('parties', $data['party_id'] ?? null);

        if (empty($data['amount']) || empty($data['spent_on'])) {
            throw new \RuntimeException('Expense amount and date are required.');
        }

        $existing = Expense::where('tenant_id', $this->tenantId)
            ->where('amount', $data['amount'])->where('spent_on', $data['spent_on'])->first();

        if ($existing) {
            $this->storeIdMapping('expenses', $legacyId, $existing->id);
            return $action === 'merge' ? 'updated' : 'skipped';
        }

        $expense = Expense::create(array_merge($data, ['tenant_id' => $this->tenantId]));
        $this->storeIdMapping('expenses', $legacyId, $expense->id);
        return 'imported';
    }

    protected function importWarehouse(array $record, string $action): string
    {
        $legacyId = $record['id'] ?? null;
        if (empty($record['name'])) throw new \RuntimeException('Warehouse name is required.');

        $existing = Warehouse::where('tenant_id', $this->tenantId)->where('name', $record['name'])->first();
        if ($existing) {
            $this->storeIdMapping('warehouses', $legacyId, $existing->id);
            return $action === 'merge' ? 'updated' : 'skipped';
        }

        $w = Warehouse::create(['tenant_id' => $this->tenantId, 'name' => $record['name'],
            'state_code' => $record['state_code'] ?? null, 'address' => $record['address'] ?? null]);
        $this->storeIdMapping('warehouses', $legacyId, $w->id);
        return 'imported';
    }

    protected function importStockMovement(array $record, string $action): string
    {
        $legacyId = $record['id'] ?? null;
        $record['item_id'] = $this->resolveId('items', $record['item_id'] ?? null);
        $record['warehouse_id'] = $this->resolveId('warehouses', $record['warehouse_id'] ?? null);

        if (empty($record['item_id']) || empty($record['direction']) || empty($record['quantity'])) {
            throw new \RuntimeException('Item, direction, and quantity required.');
        }

        $existing = StockMovement::where('tenant_id', $this->tenantId)
            ->where('item_id', $record['item_id'])->where('direction', $record['direction'])
            ->where('quantity', $record['quantity'])->where('moved_on', $record['moved_on'] ?? now()->toDateString())->first();

        if ($existing) {
            $this->storeIdMapping('stock_movements', $legacyId, $existing->id);
            return 'skipped';
        }

        $m = StockMovement::create(['tenant_id' => $this->tenantId, 'item_id' => $record['item_id'],
            'warehouse_id' => $record['warehouse_id'], 'direction' => $record['direction'],
            'quantity' => $record['quantity'], 'rate' => $record['rate'] ?? 0,
            'moved_on' => $record['moved_on'] ?? now()->toDateString(), 'note' => $record['note'] ?? null]);
        $this->storeIdMapping('stock_movements', $legacyId, $m->id);
        return 'imported';
    }

    protected function importSettings(array $record, string $action): string
    {
        $tenant = \App\Models\Tenant::find($this->tenantId);
        if (!$tenant || $action === 'skip') return 'skipped';

        $currentSettings = $tenant->settings ?? [];
        $importedSettings = [];

        // Handle key-value config records (like the old app's config.json)
        if (isset($record['KEY']) || isset($record['key'])) {
            $key = $record['KEY'] ?? $record['key'];
            $value = $record['value'] ?? '';

            // Map old config keys to new settings
            $keyMap = [
                'default_invoice_note' => 'invoice_notes',
                'date_format' => 'date_format',
                'low_stock_value' => 'low_stock_threshold',
                'fiscal_year_setup' => 'fiscal_year_start',
                'page_results' => 'records_per_page',
            ];

            $mappedKey = $keyMap[$key] ?? $key;
            $importedSettings[$mappedKey] = $value;
        }

        // Handle regular settings records
        $settingKeys = ['invoice_terms', 'invoice_notes', 'default_currency', 'fiscal_year_start',
            'payment_terms', 'company_email', 'company_phone', 'website'];
        foreach ($settingKeys as $key) {
            if (isset($record[$key])) $importedSettings[$key] = $record[$key];
        }
        if (isset($record['settings']) && is_array($record['settings'])) {
            $importedSettings = array_merge($importedSettings, $record['settings']);
        }

        if (empty($importedSettings)) return 'skipped';

        $tenant->update(['settings' => $action === 'merge'
            ? array_merge($currentSettings, $importedSettings)
            : $importedSettings]);

        return $action === 'merge' ? 'updated' : 'imported';
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    protected function mapFields(array $record, array $fieldMap): array
    {
        $mapped = [];
        foreach ($record as $key => $value) {
            $canonicalKey = $fieldMap[$key] ?? $key;
            // Several legacy field names can alias to the same canonical key
            // (e.g. "price" and "mrp" both -> sale_price). Once a real value
            // has landed on that key, a later null alias must not blank it out.
            if ($value === null && array_key_exists($canonicalKey, $mapped)) {
                continue;
            }
            $mapped[$canonicalKey] = $value;
        }
        return $mapped;
    }

    /** Drop null entries so DB column defaults apply instead of NOT NULL errors. */
    protected function withoutNulls(array $data): array
    {
        return array_filter($data, fn ($v) => $v !== null);
    }

    protected function storeIdMapping(string $section, $legacyId, int $newId): void
    {
        if ($legacyId !== null) $this->idMap[$section][$legacyId] = $newId;
    }

    protected function resolveId(string $section, $legacyId): ?int
    {
        return $legacyId !== null ? ($this->idMap[$section][$legacyId] ?? null) : null;
    }

    /**
     * Resolve a tax_id (like 18, 5, 12) to a GST rate percentage.
     */
    protected function resolveTaxRate($taxId): float
    {
        if (is_numeric($taxId)) {
            $rate = (float)$taxId;
            // If it looks like a percentage already (0-40), use it directly
            if ($rate >= 0 && $rate <= 40) return $rate;
        }
        return $this->taxIdToRate[(int)$taxId] ?? 18.0;
    }
}