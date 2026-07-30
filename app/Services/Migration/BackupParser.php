<?php

namespace App\Services\Migration;

use App\Models\ImportJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BackupParser
{
    /**
     * Parse and validate the uploaded JSON backup file.
     */
    public function parse(UploadedFile $file, ImportJob $job): array
    {
        // Validate file extension
        if ($file->getClientOriginalExtension() !== 'json') {
            throw new \InvalidArgumentException('Only JSON files are supported.');
        }

        // Store file temporarily
        $path = $file->store('imports', 'local');
        $fullPath = Storage::disk('local')->path($path);

        // Read and decode JSON
        $content = file_get_contents($fullPath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Storage::disk('local')->delete($path);
            throw new \InvalidArgumentException('Invalid JSON file: ' . json_last_error_msg());
        }

        // Update job with file path
        $job->update(['file_path' => $path]);

        // Extract metadata
        $metadata = [
            'version' => $data['version'] ?? 'unknown',
            'created_at' => $data['created_at'] ?? null,
            'app_name' => $data['app_name'] ?? 'Unknown Application',
        ];

        // Detect sections and count records
        $sections = $this->detectSections($data);

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
     * Detect all available data sections in the backup.
     */
    protected function detectSections(array $data): array
    {
        $sectionMap = [
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
            'payments' => 'Payments',
            'expenses' => 'Expenses',
            'settings' => 'Settings',
            'warehouses' => 'Warehouses',
            'stock_movements' => 'Stock Movements',
        ];

        $sections = [];

        foreach ($sectionMap as $key => $label) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $count = count($data[$key]);
                if ($count > 0) {
                    $sections[$key] = [
                        'key' => $key,
                        'label' => $label,
                        'count' => $count,
                        'sample' => array_slice($data[$key], 0, 2),
                    ];
                }
            }
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
