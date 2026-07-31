<?php

namespace App\Services\Bulk;

use App\Models\Document;
use App\Models\Item;
use App\Models\Party;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BulkExcelService
{
    /**
     * Column definitions for each entity type.
     * Each entry: 'column_name' => ['label' => 'Header', 'required' => bool, 'example' => 'value']
     */
    public function definitions(): array
    {
        return [
            'parties' => [
                'label' => 'Clients & Vendors',
                'route' => 'parties.index',
                'icon' => '👥',
                'columns' => [
                    'name'               => ['label' => 'Name *', 'required' => true, 'example' => 'Acme Corporation'],
                    'type'               => ['label' => 'Type', 'required' => false, 'example' => 'customer'],
                    'contact_person'     => ['label' => 'Contact Person', 'required' => false, 'example' => 'John Doe'],
                    'email'              => ['label' => 'Email', 'required' => false, 'example' => 'john@acme.com'],
                    'phone'              => ['label' => 'Phone', 'required' => false, 'example' => '+91 98765 43210'],
                    'gstin'              => ['label' => 'GSTIN', 'required' => false, 'example' => '27AABCU9603R1ZM'],
                    'pan'                => ['label' => 'PAN', 'required' => false, 'example' => 'AABCU9603R'],
                    'gst_treatment'      => ['label' => 'GST Treatment', 'required' => false, 'example' => 'regular'],
                    'billing_address'    => ['label' => 'Billing Address', 'required' => false, 'example' => '123 Business Park'],
                    'billing_city'       => ['label' => 'City', 'required' => false, 'example' => 'Mumbai'],
                    'billing_state'      => ['label' => 'State', 'required' => false, 'example' => 'Maharashtra'],
                    'billing_state_code' => ['label' => 'State Code', 'required' => false, 'example' => '27'],
                    'billing_pincode'    => ['label' => 'PIN Code', 'required' => false, 'example' => '400001'],
                    'billing_country'    => ['label' => 'Country', 'required' => false, 'example' => 'IN'],
                    'opening_balance'    => ['label' => 'Opening Balance', 'required' => false, 'example' => '50000'],
                    'credit_days'        => ['label' => 'Credit Days', 'required' => false, 'example' => '30'],
                ],
            ],

            'items' => [
                'label' => 'Products & Services',
                'route' => 'items.index',
                'icon' => '📦',
                'columns' => [
                    'name'                => ['label' => 'Name *', 'required' => true, 'example' => 'Web Development Service'],
                    'type'                => ['label' => 'Type', 'required' => false, 'example' => 'service'],
                    'sku'                 => ['label' => 'SKU', 'required' => false, 'example' => 'WEB-001'],
                    'barcode'             => ['label' => 'Barcode', 'required' => false, 'example' => '1234567890'],
                    'description'         => ['label' => 'Description', 'required' => false, 'example' => 'Custom web dev'],
                    'hsn_sac'             => ['label' => 'HSN/SAC', 'required' => false, 'example' => '998314'],
                    'uqc'                 => ['label' => 'Unit (UQC)', 'required' => false, 'example' => 'HRS'],
                    'sale_price'          => ['label' => 'Sale Price', 'required' => false, 'example' => '2500'],
                    'purchase_price'      => ['label' => 'Purchase Price', 'required' => false, 'example' => '0'],
                    'gst_rate'            => ['label' => 'GST Rate %', 'required' => false, 'example' => '18'],
                    'cess_rate'           => ['label' => 'Cess Rate %', 'required' => false, 'example' => '0'],
                    'price_inclusive_tax' => ['label' => 'Price Incl. Tax', 'required' => false, 'example' => 'No'],
                    'track_inventory'     => ['label' => 'Track Inventory', 'required' => false, 'example' => 'No'],
                    'opening_stock'       => ['label' => 'Opening Stock', 'required' => false, 'example' => '0'],
                    'reorder_level'       => ['label' => 'Reorder Level', 'required' => false, 'example' => '0'],
                ],
            ],

            'documents' => [
                'label' => 'Invoices / Quotations / Credit Notes',
                'route' => 'documents.index',
                'icon' => '📄',
                'columns' => [
                    'doc_type'             => ['label' => 'Type *', 'required' => true, 'example' => 'invoice'],
                    'number'               => ['label' => 'Number *', 'required' => true, 'example' => 'INV-2026-001'],
                    'issue_date'           => ['label' => 'Issue Date *', 'required' => true, 'example' => '2026-07-15'],
                    'due_date'             => ['label' => 'Due Date', 'required' => false, 'example' => '2026-08-14'],
                    'party_name'           => ['label' => 'Party Name', 'required' => false, 'example' => 'Acme Corporation'],
                    'party_gstin'          => ['label' => 'Party GSTIN', 'required' => false, 'example' => '27AABCU9603R1ZM'],
                    'status'               => ['label' => 'Status', 'required' => false, 'example' => 'sent'],
                    'supply_type'          => ['label' => 'Supply Type', 'required' => false, 'example' => 'B2B'],
                    'place_of_supply_code' => ['label' => 'POS Code', 'required' => false, 'example' => '27'],
                    'reverse_charge'       => ['label' => 'Reverse Charge', 'required' => false, 'example' => 'No'],
                    'currency'             => ['label' => 'Currency', 'required' => false, 'example' => 'INR'],
                    'subtotal'             => ['label' => 'Subtotal', 'required' => false, 'example' => '25000'],
                    'discount_total'       => ['label' => 'Discount', 'required' => false, 'example' => '0'],
                    'taxable_value'        => ['label' => 'Taxable Value', 'required' => false, 'example' => '25000'],
                    'cgst_total'           => ['label' => 'CGST', 'required' => false, 'example' => '2250'],
                    'sgst_total'           => ['label' => 'SGST', 'required' => false, 'example' => '2250'],
                    'igst_total'           => ['label' => 'IGST', 'required' => false, 'example' => '0'],
                    'cess_total'           => ['label' => 'Cess', 'required' => false, 'example' => '0'],
                    'shipping_charge'      => ['label' => 'Shipping', 'required' => false, 'example' => '0'],
                    'grand_total'          => ['label' => 'Grand Total', 'required' => false, 'example' => '29500'],
                    'notes'                => ['label' => 'Notes', 'required' => false, 'example' => 'Thank you'],
                    'terms'                => ['label' => 'Terms', 'required' => false, 'example' => 'Payment in 30 days'],
                ],
            ],
        ];
    }

    // =========================================================================
    // SAMPLE FILE GENERATION
    // =========================================================================

    /**
     * Generate a sample Excel file for download.
     */
    public function generateSample(string $entity): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $def = $this->definitions()[$entity] ?? null;
        if (!$def) abort(404, 'Unknown entity type');

        $headings = [];
        $sampleRow = [];
        $sampleRow2 = [];

        foreach ($def['columns'] as $key => $col) {
            $headings[] = $col['label'];
            $sampleRow[] = $col['example'];
        }

        // Second sample row with different data
        $examples2 = [
            'parties' => ['Tech Supplies Ltd', 'vendor', 'Jane Smith', 'jane@tech.com', '+91 87654 32109', '29AADCT1234F1Z5', 'AADCT1234F', 'regular', '456 Industrial Area', 'Bangalore', 'Karnataka', '29', '560001', 'IN', '0', '45'],
            'items' => ['Laptop Dell XPS 15', 'goods', 'LAPTOP-XPS15', '9876543210', 'High-performance laptop', '8471', 'NOS', '150000', '120000', '18', '0', 'No', 'Yes', '5', '2'],
            'documents' => ['quotation', 'QT-2026-001', '2026-07-20', '2026-08-20', 'Tech Supplies Ltd', '29AADCT1234F1Z5', 'draft', 'B2B', '29', 'No', 'INR', '50000', '5000', '45000', '0', '0', '8100', '0', '500', '53600', 'Valid for 30 days', ''],
        ];
        $sampleRow2 = $examples2[$entity] ?? array_fill(0, count($headings), '');

        $export = new class($headings, $sampleRow, $sampleRow2) implements FromCollection, WithHeadings, WithColumnWidths, WithStyles {
            public function __construct(
                private array $headings,
                private array $row1,
                private array $row2,
            ) {}

            public function collection(): Collection
            {
                return collect([$this->row1, $this->row2]);
            }

            public function headings(): array
            {
                return $this->headings;
            }

            public function columnWidths(): array
            {
                $widths = [];
                foreach ($this->headings as $i => $h) {
                    $widths[chr(65 + $i)] = max(strlen($h) + 4, 18);
                }
                return $widths;
            }

            public function styles(Worksheet $sheet): array
            {
                return [
                    1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']]],
                ];
            }
        };

        $filename = $entity . '_sample_' . date('Y-m-d') . '.xlsx';

        return Excel::download($export, $filename);
    }

    // =========================================================================
    // EXPORT
    // =========================================================================

    /**
     * Export existing data to Excel.
     */
    public function export(string $entity): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $def = $this->definitions()[$entity] ?? null;
        if (!$def) abort(404, 'Unknown entity type');

        $tenant = TenantContext::get();
        $headings = array_map(fn ($c) => $c['label'], $def['columns']);
        $keys = array_keys($def['columns']);

        $rows = match ($entity) {
            'parties' => Party::where('tenant_id', $tenant->id)->get(),
            'items' => Item::where('tenant_id', $tenant->id)->get(),
            'documents' => Document::where('tenant_id', $tenant->id)
                ->whereIn('doc_type', ['invoice', 'quotation', 'credit_note', 'debit_note', 'purchase_order', 'purchase_bill', 'proforma', 'delivery_challan', 'bill_of_supply'])
                ->with('party')->get(),
            default => collect(),
        };

        $data = $rows->map(function ($row) use ($keys, $entity) {
            $line = [];
            foreach ($keys as $key) {
                if ($key === 'party_name') {
                    $line[] = $row->party?->name ?? '';
                } elseif ($key === 'party_gstin') {
                    $line[] = $row->party?->gstin ?? '';
                } elseif ($key === 'price_inclusive_tax' || $key === 'track_inventory' || $key === 'reverse_charge') {
                    $line[] = $row->$key ? 'Yes' : 'No';
                } else {
                    $line[] = $row->$key ?? '';
                }
            }
            return $line;
        })->toArray();

        $export = new class($headings, $data) implements FromCollection, WithHeadings, WithColumnWidths, WithStyles {
            public function __construct(private array $headings, private array $data) {}
            public function collection(): Collection { return collect($this->data); }
            public function headings(): array { return $this->headings; }
            public function columnWidths(): array
            {
                $widths = [];
                foreach ($this->headings as $i => $h) {
                    $widths[chr(65 + $i)] = max(strlen($h) + 4, 18);
                }
                return $widths;
            }
            public function styles(Worksheet $sheet): array
            {
                return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']]]];
            }
        };

        $filename = $entity . '_export_' . date('Y-m-d_His') . '.xlsx';
        return Excel::download($export, $filename);
    }

    // =========================================================================
    // IMPORT
    // =========================================================================

    /**
     * Parse an uploaded Excel file and return rows with validation.
     */
    public function parseUpload($file, string $entity): array
    {
        $def = $this->definitions()[$entity] ?? null;
        if (!$def) abort(404, 'Unknown entity type');

        $columns = array_keys($def['columns']);
        $labels = array_map(fn ($c) => $c['label'], $def['columns']);

        // Read the Excel file
        $rows = Excel::toArray(null, $file)[0] ?? [];

        if (count($rows) < 2) {
            return ['rows' => [], 'errors' => ['File is empty or has no data rows.'], 'valid' => 0, 'invalid' => 0];
        }

        // Skip header row, map columns by header name
        $headerRow = array_shift($rows);

        // Map header labels to column keys
        $headerMap = [];
        foreach ($headerRow as $i => $header) {
            $header = trim((string)$header);
            // Find matching column by label
            foreach ($def['columns'] as $key => $col) {
                if (strcasecmp($col['label'], $header) === 0 || strcasecmp($key, $header) === 0) {
                    $headerMap[$i] = $key;
                    break;
                }
            }
        }

        $parsedRows = [];
        $errors = [];
        $valid = 0;
        $invalid = 0;

        foreach ($rows as $rowIndex => $row) {
            $record = [];
            $rowErrors = [];

            foreach ($headerMap as $colIndex => $key) {
                $value = $row[$colIndex] ?? null;
                if (is_string($value)) $value = trim($value);

                // Convert Yes/No to boolean
                if (in_array($key, ['price_inclusive_tax', 'track_inventory', 'reverse_charge'])) {
                    $value = in_array(strtolower((string)$value), ['yes', '1', 'true'], true);
                }

                $record[$key] = $value;
            }

            // Validate required fields
            foreach ($def['columns'] as $key => $col) {
                if ($col['required'] && empty($record[$key])) {
                    $rowErrors[] = "Missing required: {$col['label']}";
                }
            }

            $lineNum = $rowIndex + 2; // +2 because header is row 1 and 0-indexed

            if (empty($rowErrors)) {
                $valid++;
                $parsedRows[] = ['line' => $lineNum, 'data' => $record, 'status' => 'valid'];
            } else {
                $invalid++;
                $parsedRows[] = ['line' => $lineNum, 'data' => $record, 'status' => 'invalid', 'errors' => $rowErrors];
                foreach ($rowErrors as $err) {
                    $errors[] = "Row {$lineNum}: {$err}";
                }
            }
        }

        return [
            'rows' => $parsedRows,
            'errors' => $errors,
            'valid' => $valid,
            'invalid' => $invalid,
            'total' => count($parsedRows),
        ];
    }

    /**
     * Execute the import of parsed rows into the database.
     */
    public function executeImport(array $rows, string $entity, string $mode = 'import'): array
    {
        $tenant = TenantContext::get();
        $stats = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                if ($row['status'] === 'invalid') {
                    $stats['skipped']++;
                    continue;
                }

                try {
                    $result = match ($entity) {
                        'parties' => $this->importPartyRow($row['data'], $tenant, $mode),
                        'items' => $this->importItemRow($row['data'], $tenant, $mode),
                        'documents' => $this->importDocumentRow($row['data'], $tenant, $mode),
                        default => 'skipped',
                    };
                    $stats[$result]++;
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    $errors[] = "Row {$row['line']}: " . $e->getMessage();
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return ['stats' => $stats, 'errors' => $errors];
    }

    // =========================================================================
    // Row-level importers
    // =========================================================================

    protected function importPartyRow(array $data, $tenant, string $mode): string
    {
        $existing = Party::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($data) {
                $q->where('name', $data['name']);
                if (!empty($data['email'])) $q->orWhere('email', $data['email']);
                if (!empty($data['gstin'])) $q->orWhere('gstin', $data['gstin']);
            })->first();

        $allowed = ['type', 'name', 'contact_person', 'email', 'phone', 'gstin', 'pan',
            'gst_treatment', 'billing_address', 'billing_city', 'billing_state',
            'billing_state_code', 'billing_pincode', 'billing_country', 'opening_balance', 'credit_days'];
        $clean = array_intersect_key(array_filter($data, fn ($v) => $v !== null && $v !== ''), array_flip($allowed));

        if ($existing) {
            if ($mode === 'merge') {
                $existing->update($clean);
                return 'updated';
            }
            return 'skipped';
        }

        Party::create(array_merge($clean, ['tenant_id' => $tenant->id, 'type' => $data['type'] ?? 'customer']));
        return 'imported';
    }

    protected function importItemRow(array $data, $tenant, string $mode): string
    {
        $existing = Item::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($data) {
                $q->where('name', $data['name']);
                if (!empty($data['sku'])) $q->orWhere('sku', $data['sku']);
            })->first();

        $allowed = ['name', 'type', 'sku', 'barcode', 'description', 'hsn_sac', 'uqc',
            'sale_price', 'purchase_price', 'gst_rate', 'cess_rate',
            'price_inclusive_tax', 'track_inventory', 'opening_stock', 'reorder_level'];
        $clean = array_intersect_key(array_filter($data, fn ($v) => $v !== null && $v !== ''), array_flip($allowed));

        if ($existing) {
            if ($mode === 'merge') {
                $existing->update($clean);
                return 'updated';
            }
            return 'skipped';
        }

        Item::create(array_merge($clean, ['tenant_id' => $tenant->id]));
        return 'imported';
    }

    protected function importDocumentRow(array $data, $tenant, string $mode): string
    {
        // Resolve party by name or GSTIN
        $partyId = null;
        if (!empty($data['party_name'])) {
            $party = Party::where('tenant_id', $tenant->id)->where('name', $data['party_name'])->first();
            if (!$party && !empty($data['party_gstin'])) {
                $party = Party::where('tenant_id', $tenant->id)->where('gstin', $data['party_gstin'])->first();
            }
            if (!$party) {
                // Auto-create
                $party = Party::create([
                    'tenant_id' => $tenant->id, 'type' => 'customer',
                    'name' => $data['party_name'],
                    'gstin' => $data['party_gstin'] ?? null,
                    'gst_treatment' => !empty($data['party_gstin']) ? 'regular' : 'consumer',
                ]);
            }
            $partyId = $party->id;
        }

        $existing = Document::where('tenant_id', $tenant->id)
            ->where('doc_type', $data['doc_type'])
            ->where('number', $data['number'])
            ->first();

        $allowed = ['doc_type', 'number', 'issue_date', 'due_date', 'status', 'supply_type',
            'place_of_supply_code', 'reverse_charge', 'currency', 'subtotal', 'discount_total',
            'taxable_value', 'cgst_total', 'sgst_total', 'igst_total', 'cess_total',
            'shipping_charge', 'grand_total', 'notes', 'terms'];
        $clean = array_intersect_key(array_filter($data, fn ($v) => $v !== null && $v !== ''), array_flip($allowed));

        $clean['party_id'] = $partyId;
        $clean['tenant_id'] = $tenant->id;
        $clean['source'] = 'bulk_import';

        // Calculate balance
        $grand = (float)($clean['grand_total'] ?? 0);
        $clean['amount_paid'] = $clean['amount_paid'] ?? 0;
        $clean['balance_due'] = $grand - (float)$clean['amount_paid'];

        if ($existing) {
            if ($mode === 'merge') {
                $existing->update($clean);
                return 'updated';
            }
            return 'skipped';
        }

        Document::create($clean);
        return 'imported';
    }
}