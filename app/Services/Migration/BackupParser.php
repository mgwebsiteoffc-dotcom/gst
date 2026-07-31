<?php

namespace App\Services\Migration;

use App\Models\ImportJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BackupParser
{
    /**
     * Parse and validate the uploaded JSON backup file.
     */
    public function parse(UploadedFile $file, ImportJob $job): array
    {
        // Validate file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['json', 'txt'])) {
            throw new \InvalidArgumentException('Only JSON files are supported.');
        }

        // Store file temporarily
        $path = $file->store('imports', 'local');
        $fullPath = Storage::disk('local')->path($path);

        // Read and decode JSON
        $content = file_get_contents($fullPath);
        $rawData = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Storage::disk('local')->delete($path);
            throw new \InvalidArgumentException('Invalid JSON file: ' . json_last_error_msg());
        }

        // Update job with file path
        $job->update(['file_path' => $path]);

        // Normalize the data into a consistent {section: [records]} structure
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $data = $this->normalizeData($rawData, $filename);

        // Extract metadata
        $metadata = [
            'version' => $data['version'] ?? $rawData['version'] ?? 'unknown',
            'created_at' => $data['created_at'] ?? $rawData['created_at'] ?? null,
            'app_name' => $data['app_name'] ?? $rawData['app_name'] ?? 'JSON Import',
        ];

        // Detect sections and count records
        $sections = $this->detectSections($data);

        if (empty($sections)) {
            Storage::disk('local')->delete($path);
            throw new \InvalidArgumentException(
                'No recognizable data sections found in the file. ' .
                'The JSON should contain arrays of records under keys like "clients", "products", "invoices", etc., ' .
                'or be a flat array of records (the section is inferred from the filename).'
            );
        }

        // Save the NORMALIZED data back to the file so loadData() returns the same structure
        file_put_contents($fullPath, json_encode($data, JSON_PRETTY_PRINT));

        // Update job with parsed information
        $job->update([
            'backup_version' => $metadata['version'],
            'backup_created_at' => $metadata['created_at'],
            'sections' => $sections,
            'status' => 'analyzing',
        ]);

        return [
            'metadata' => $metadata,
            'sections' => $sections,
            'data' => $data,
            'path' => $path,
        ];
    }

    /**
     * Normalize any JSON structure into a {section_key: [records]} format.
     *
     * Handles:
     *  1. Full backup: {"clients": [...], "products": [...], "version": "1.0"}
     *  2. Flat array:   [{"name": "Client 1"}, ...]  (section inferred from filename)
     *  3. Data wrapper: {"data": [...]}               (section inferred from filename)
     *  4. Paginated:    {"data": [...], "total": 50}  (section inferred from filename)
     *  5. Named wrap:   {"clients": {"data": [...]}}  (unwrap nested data)
     */
    protected function normalizeData($rawData, string $filename): array
    {
        // Case 2: Flat array — the entire file IS the records list
        if ($this->isSequentialArray($rawData)) {
            $sectionKey = $this->inferSectionFromFilename($filename);
            return [$sectionKey => $rawData];
        }

        // Must be an object/associative array from here
        if (!is_array($rawData)) {
            return [];
        }

        // Case 3/4: {"data": [...], "total": 50, ...} — single-section with "data" wrapper
        if (isset($rawData['data']) && $this->isSequentialArray($rawData['data'])) {
            // Check if there are other recognizable section keys too
            $otherSections = $this->extractKnownSections($rawData);
            if (!empty($otherSections)) {
                // It's a full backup that happens to also have a "data" key — keep as-is
                return $rawData;
            }

            // Single section — infer from filename
            $sectionKey = $this->inferSectionFromFilename($filename);
            $normalized = [$sectionKey => $rawData['data']];

            // Preserve metadata keys
            foreach (['version', 'created_at', 'app_name'] as $meta) {
                if (isset($rawData[$meta])) {
                    $normalized[$meta] = $rawData[$meta];
                }
            }

            return $normalized;
        }

        // Case 5: Unwrap nested {"clients": {"data": [...]}}
        foreach ($rawData as $key => $value) {
            if (is_array($value) && !$this->isSequentialArray($value) && isset($value['data']) && $this->isSequentialArray($value['data'])) {
                $rawData[$key] = $value['data'];
            }
        }

        // Case 1: Already in the right format, or close enough
        return $rawData;
    }

    /**
     * Check if an array is a sequential (numerically indexed) array of records.
     */
    protected function isSequentialArray($value): bool
    {
        if (!is_array($value) || empty($value)) {
            return false;
        }

        // Check if keys are sequential integers starting from 0
        return array_keys($value) === range(0, count($value) - 1);
    }

    /**
     * Infer the section key from the filename.
     * e.g., "clients.json" → "clients", "product_list" → "products"
     */
    protected function inferSectionFromFilename(string $filename): string
    {
        $filename = strtolower(trim($filename));

        // Remove common suffixes/prefixes
        $clean = preg_replace('/[\s_\-]*(backup|export|data|list|dump|file|copy|old|new)[\s_\-]*/i', '', $filename);
        $clean = Str::snake(Str::plural(trim($clean, '_- ')));

        // Map common names to canonical section keys
        $aliases = [
            'clients' => 'clients',
            'client' => 'clients',
            'customers' => 'clients',
            'customer' => 'clients',
            'parties' => 'parties',
            'party' => 'parties',
            'contacts' => 'parties',
            'contact' => 'parties',
            'products' => 'items',
            'product' => 'items',
            'items' => 'items',
            'item' => 'items',
            'services' => 'items',
            'service' => 'items',
            'invoices' => 'documents',
            'invoice' => 'documents',
            'documents' => 'documents',
            'document' => 'documents',
            'bills' => 'documents',
            'bill' => 'documents',
            'quotations' => 'documents',
            'quotation' => 'documents',
            'quotes' => 'documents',
            'quote' => 'documents',
            'invoice_items' => 'document_lines',
            'line_items' => 'document_lines',
            'document_lines' => 'document_lines',
            'lines' => 'document_lines',
            'invoice_products' => 'invoice_products',
            'payments' => 'payments',
            'payment' => 'payments',
            'transactions' => 'payments',
            'transaction' => 'payments',
            'invoice_payments' => 'invoice_payments',
            'payment_documents' => 'payment_documents',
            'expenses' => 'expenses',
            'expense' => 'expenses',
            'bills_payable' => 'expenses',
            'suppliers' => 'parties',
            'supplier' => 'parties',
            'vendors' => 'parties',
            'vendor' => 'parties',
            'categories' => 'categories',
            'category' => 'categories',
            'brands' => 'brands',
            'brand' => 'brands',
            'warehouses' => 'warehouses',
            'warehouse' => 'warehouses',
            'locations' => 'warehouses',
            'stock_movements' => 'stock_movements',
            'inventory' => 'stock_movements',
            'settings' => 'settings',
            'preferences' => 'settings',
            'config' => 'settings',
        ];

        return $aliases[$clean] ?? $clean;
    }

    /**
     * Extract known section keys from the data.
     */
    protected function extractKnownSections(array $data): array
    {
        $knownKeys = [
            'brands', 'clients', 'customers', 'parties', 'products', 'items',
            'categories', 'suppliers', 'vendors', 'invoices', 'documents',
            'invoice_items', 'document_lines', 'payments', 'expenses',
            'settings', 'warehouses', 'stock_movements',
        ];

        $found = [];
        foreach ($knownKeys as $key) {
            if (isset($data[$key]) && is_array($data[$key]) && !empty($data[$key])) {
                $found[$key] = $data[$key];
            }
        }

        return $found;
    }

    /**
     * Detect all available data sections in the (normalized) backup.
     */
    protected function detectSections(array $data): array
    {
        $sectionLabels = [
            'brands' => 'Brands',
            'clients' => 'Clients',
            'customers' => 'Clients',
            'parties' => 'Clients & Vendors',
            'products' => 'Products',
            'items' => 'Products & Services',
            'categories' => 'Categories',
            'suppliers' => 'Suppliers',
            'vendors' => 'Vendors',
            'invoices' => 'Invoices',
            'documents' => 'Documents',
            'invoice_items' => 'Invoice Items',
            'document_lines' => 'Document Lines',
            'invoice_products' => 'Invoice Line Items',
            'payments' => 'Payments',
            'invoice_payments' => 'Invoice Payments',
            'payment_documents' => 'Payment Receipts',
            'expenses' => 'Expenses',
            'settings' => 'Settings',
            'config' => 'Settings',
            'warehouses' => 'Warehouses',
            'stock_movements' => 'Stock Movements',
        ];

        $sections = [];

        foreach ($data as $key => $value) {
            // Skip metadata keys
            if (in_array($key, ['version', 'created_at', 'app_name', 'hash', 'gstin', 'fp'], true)) {
                continue;
            }

            // Must be a non-empty sequential array
            if (!$this->isSequentialArray($value)) {
                continue;
            }

            $count = count($value);
            if ($count === 0) {
                continue;
            }

            // Determine label
            $label = $sectionLabels[$key] ?? ucfirst(str_replace('_', ' ', $key));

            $sections[$key] = [
                'key' => $key,
                'label' => $label,
                'count' => $count,
                'sample' => array_slice($value, 0, 2),
            ];
        }

        return $sections;
    }

    /**
     * Load parsed data from storage for processing.
     */
    public function loadData(ImportJob $job): array
    {
        if (!$job->file_path) {
            throw new \RuntimeException('No file path found for import job.');
        }

        $fullPath = Storage::disk('local')->path($job->file_path);

        if (!file_exists($fullPath)) {
            throw new \RuntimeException('Backup file not found.');
        }

        $content = file_get_contents($fullPath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse backup file: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Clean up temporary files.
     */
    public function cleanup(ImportJob $job): void
    {
        if ($job->file_path && Storage::disk('local')->exists($job->file_path)) {
            Storage::disk('local')->delete($job->file_path);
        }
    }
}