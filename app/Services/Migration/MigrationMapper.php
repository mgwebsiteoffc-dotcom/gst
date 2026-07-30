<?php

namespace App\Services\Migration;

/**
 * Maps JSON backup sections to destination database tables
 * and defines import ordering and field mappings.
 */
class MigrationMapper
{
    /**
     * All known section definitions with their mappings.
     * Order defines default dependency ordering.
     */
    public function sectionDefinitions(): array
    {
        return [
            'settings' => [
                'key' => 'settings',
                'label' => 'Settings',
                'table' => 'tenants',
                'model' => null, // Special handling - updates tenant
                'order' => 10,
                'aliases' => ['settings', 'preferences', 'config'],
            ],
            'brands' => [
                'key' => 'brands',
                'label' => 'Brands',
                'table' => 'tenants',
                'model' => null,
                'order' => 10,
                'aliases' => ['brands', 'brand'],
            ],
            'categories' => [
                'key' => 'categories',
                'label' => 'Categories',
                'table' => 'items',
                'model' => null, // Merged into items if needed
                'order' => 20,
                'aliases' => ['categories', 'item_categories'],
            ],
            'warehouses' => [
                'key' => 'warehouses',
                'label' => 'Warehouses',
                'table' => 'warehouses',
                'model' => \App\Models\Warehouse::class,
                'order' => 20,
                'aliases' => ['warehouses', 'locations'],
            ],
            'parties' => [
                'key' => 'parties',
                'label' => 'Clients & Vendors',
                'table' => 'parties',
                'model' => \App\Models\Party::class,
                'order' => 30,
                'aliases' => ['parties', 'clients', 'customers', 'suppliers', 'vendors'],
            ],
            'items' => [
                'key' => 'items',
                'label' => 'Products & Services',
                'table' => 'items',
                'model' => \App\Models\Item::class,
                'order' => 40,
                'aliases' => ['items', 'products', 'services'],
            ],
            'documents' => [
                'key' => 'documents',
                'label' => 'Documents',
                'table' => 'documents',
                'model' => \App\Models\Document::class,
                'order' => 50,
                'aliases' => ['documents', 'invoices', 'quotations', 'bills'],
            ],
            'document_lines' => [
                'key' => 'document_lines',
                'label' => 'Document Lines',
                'table' => 'document_lines',
                'model' => \App\Models\DocumentLine::class,
                'order' => 60,
                'aliases' => ['document_lines', 'invoice_items', 'line_items', 'items_detail'],
            ],
            'payments' => [
                'key' => 'payments',
                'label' => 'Payments',
                'table' => 'payments',
                'model' => \App\Models\Payment::class,
                'order' => 70,
                'aliases' => ['payments', 'transactions'],
            ],
            'expenses' => [
                'key' => 'expenses',
                'label' => 'Expenses',
                'table' => 'expenses',
                'model' => \App\Models\Expense::class,
                'order' => 70,
                'aliases' => ['expenses', 'bills_payable'],
            ],
            'stock_movements' => [
                'key' => 'stock_movements',
                'label' => 'Stock Movements',
                'table' => 'stock_movements',
                'model' => \App\Models\StockMovement::class,
                'order' => 80,
                'aliases' => ['stock_movements', 'inventory_logs'],
            ],
        ];
    }

    /**
     * Build the mapping for the detected sections from a backup.
     * Returns a list of detected sections with their destination table
     * and default action.
     */
    public function buildMapping(array $detectedSections): array
    {
        $definitions = $this->sectionDefinitions();
        $mapping = [];

        foreach ($detectedSections as $sectionKey => $info) {
            $def = $this->resolveDefinition($sectionKey, $definitions);

            $mapping[$sectionKey] = [
                'key' => $sectionKey,
                'label' => $info['label'] ?? $def['label'] ?? ucfirst(str_replace('_', ' ', $sectionKey)),
                'count' => $info['count'] ?? 0,
                'destination_table' => $def['table'] ?? 'unknown',
                'destination_label' => ucfirst(str_replace('_', ' ', $def['table'] ?? $sectionKey)),
                'action' => $this->defaultAction($sectionKey),
                'order' => $def['order'] ?? 99,
            ];
        }

        // Sort by dependency order
        uasort($mapping, fn ($a, $b) => $a['order'] <=> $b['order']);

        return $mapping;
    }

    /**
     * Resolve a section definition from a key (checking aliases).
     */
    protected function resolveDefinition(string $key, array $definitions): array
    {
        // Direct match
        if (isset($definitions[$key])) {
            return $definitions[$key];
        }

        // Check aliases
        foreach ($definitions as $def) {
            if (in_array($key, $def['aliases'] ?? [], true)) {
                return $def;
            }
        }

        return ['key' => $key, 'table' => 'unknown', 'order' => 99];
    }

    /**
     * Determine the default import action for a section.
     */
    protected function defaultAction(string $section): string
    {
        // Settings/brands should merge by default to preserve existing config
        if (in_array($section, ['settings', 'brands'], true)) {
            return 'merge';
        }

        // Everything else defaults to import as new
        return 'import';
    }

    /**
     * Get the ordered list of sections for import.
     */
    public function getImportOrder(array $mapping): array
    {
        $toImport = [];

        foreach ($mapping as $key => $info) {
            if ($info['action'] !== 'skip') {
                $toImport[$key] = $info;
            }
        }

        uasort($toImport, fn ($a, $b) => $a['order'] <=> $b['order']);

        return array_keys($toImport);
    }

    /**
     * Available actions for the user to choose.
     */
    public function availableActions(): array
    {
        return [
            'import' => 'Import as New',
            'merge' => 'Merge with Existing',
            'skip' => 'Skip This Section',
        ];
    }

    /**
     * Get field mapping from legacy format to new format.
     * Maps common alternative field names to the canonical field names.
     */
    public function getFieldMapping(string $section): array
    {
        $mappings = [
            'parties' => [
                'client_name' => 'name',
                'customer_name' => 'name',
                'supplier_name' => 'name',
                'vendor_name' => 'name',
                'company_name' => 'name',
                'contact_name' => 'contact_person',
                'mobile' => 'phone',
                'phone_number' => 'phone',
                'email_address' => 'email',
                'address' => 'billing_address',
                'city' => 'billing_city',
                'state' => 'billing_state',
                'state_code' => 'billing_state_code',
                'pincode' => 'billing_pincode',
                'zip' => 'billing_pincode',
                'postal_code' => 'billing_pincode',
                'country' => 'billing_country',
                'gst_number' => 'gstin',
                'gst_treatment_type' => 'gst_treatment',
                'balance' => 'opening_balance',
            ],
            'items' => [
                'product_name' => 'name',
                'item_name' => 'name',
                'service_name' => 'name',
                'title' => 'name',
                'product_sku' => 'sku',
                'item_code' => 'sku',
                'item_description' => 'description',
                'product_description' => 'description',
                'hsn' => 'hsn_sac',
                'hsn_code' => 'hsn_sac',
                'sac' => 'hsn_sac',
                'sac_code' => 'hsn_sac',
                'unit' => 'uqc',
                'unit_code' => 'uqc',
                'selling_price' => 'sale_price',
                'mrp' => 'sale_price',
                'cost_price' => 'purchase_price',
                'buy_price' => 'purchase_price',
                'tax_rate' => 'gst_rate',
                'gst_percent' => 'gst_rate',
                'tax_percent' => 'gst_rate',
                'item_type' => 'type',
                'product_type' => 'type',
                'stock' => 'opening_stock',
                'opening_qty' => 'opening_stock',
            ],
            'documents' => [
                'invoice_number' => 'number',
                'doc_number' => 'number',
                'bill_number' => 'number',
                'ref_number' => 'number',
                'invoice_date' => 'issue_date',
                'date' => 'issue_date',
                'bill_date' => 'issue_date',
                'due' => 'due_date',
                'customer_id' => 'party_id',
                'client_id' => 'party_id',
                'supplier_id' => 'party_id',
                'vendor_id' => 'party_id',
                'type' => 'doc_type',
                'document_type' => 'doc_type',
                'total' => 'grand_total',
                'amount' => 'grand_total',
                'invoice_total' => 'grand_total',
                'tax_amount' => 'cgst_total', // Will be split in importer
                'note' => 'notes',
                'remarks' => 'notes',
                'terms_conditions' => 'terms',
            ],
            'document_lines' => [
                'invoice_id' => 'document_id',
                'bill_id' => 'document_id',
                'product_id' => 'item_id',
                'product_name' => 'name',
                'item_name' => 'name',
                'description_text' => 'description',
                'hsn' => 'hsn_sac',
                'hsn_code' => 'hsn_sac',
                'unit' => 'uqc',
                'qty' => 'quantity',
                'unit_price' => 'rate',
                'price' => 'rate',
                'discount' => 'discount_amount',
                'tax_rate' => 'gst_rate',
                'gst_percent' => 'gst_rate',
                'total' => 'line_total',
                'amount' => 'line_total',
                'sort_order' => 'position',
                'order' => 'position',
            ],
            'payments' => [
                'invoice_id' => 'document_id',
                'bill_id' => 'document_id',
                'customer_id' => 'party_id',
                'client_id' => 'party_id',
                'payment_date' => 'paid_on',
                'date' => 'paid_on',
                'payment_method' => 'method',
                'mode' => 'method',
                'ref' => 'reference',
                'ref_number' => 'reference',
                'transaction_id' => 'reference',
                'remarks' => 'note',
                'notes' => 'note',
                'payment_type' => 'direction',
            ],
            'expenses' => [
                'vendor_id' => 'party_id',
                'supplier_id' => 'party_id',
                'expense_date' => 'spent_on',
                'date' => 'spent_on',
                'expense_category' => 'category',
                'ref' => 'reference',
                'ref_number' => 'reference',
                'remarks' => 'note',
                'notes' => 'note',
                'description' => 'note',
                'gst' => 'gst_amount',
                'tax_amount' => 'gst_amount',
            ],
        ];

        return $mappings[$section] ?? [];
    }
}
