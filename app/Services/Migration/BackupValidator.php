<?php

namespace App\Services\Migration;

use App\Models\ImportJob;
use Illuminate\Support\Collection;

class BackupValidator
{
    protected array $warnings = [];
    protected array $errors = [];

    /**
     * Validate the backup data before import.
     */
    public function validate(array $data, ImportJob $job, array $mapping): array
    {
        $this->warnings = [];
        $this->errors = [];

        foreach ($mapping as $section => $action) {
            if ($action === 'skip' || !isset($data[$section])) {
                continue;
            }

            $records = $data[$section];

            // Validate required fields
            $this->validateRequiredFields($section, $records);

            // Detect duplicates within the backup
            $this->detectDuplicates($section, $records);

            // Validate relationships
            $this->validateRelationships($section, $records, $data);
        }

        $validation = [
            'warnings' => $this->warnings,
            'errors' => $this->errors,
            'warning_count' => count($this->warnings),
            'error_count' => count($this->errors),
        ];

        $job->update(['validation' => $validation]);

        return $validation;
    }

    /**
     * Validate required fields for each section.
     */
    protected function validateRequiredFields(string $section, array $records): void
    {
        $requiredFields = $this->getRequiredFields($section);

        if (empty($requiredFields)) {
            return;
        }

        foreach ($records as $index => $record) {
            foreach ($requiredFields as $field) {
                if (empty($record[$field])) {
                    $this->warnings[] = [
                        'section' => $section,
                        'record_index' => $index,
                        'record_id' => $record['id'] ?? $index,
                        'message' => "Missing required field: {$field}",
                        'severity' => 'warning',
                    ];
                }
            }
        }
    }

    /**
     * Detect duplicate records within the backup.
     */
    protected function detectDuplicates(string $section, array $records): void
    {
        $uniqueFields = $this->getUniqueFields($section);

        if (empty($uniqueFields)) {
            return;
        }

        foreach ($uniqueFields as $field) {
            $values = array_column($records, $field);
            $duplicates = array_diff_assoc($values, array_unique($values));

            if (!empty($duplicates)) {
                $this->warnings[] = [
                    'section' => $section,
                    'message' => "Duplicate values found for field '{$field}': " . implode(', ', array_unique($duplicates)),
                    'severity' => 'warning',
                ];
            }
        }
    }

    /**
     * Validate relationships between records.
     */
    protected function validateRelationships(string $section, array $records, array $allData): void
    {
        $relationships = $this->getRelationships($section);

        foreach ($relationships as $field => $targetSection) {
            if (!isset($allData[$targetSection])) {
                continue;
            }

            $targetIds = array_column($allData[$targetSection], 'id');

            foreach ($records as $index => $record) {
                if (!empty($record[$field]) && !in_array($record[$field], $targetIds)) {
                    $this->warnings[] = [
                        'section' => $section,
                        'record_index' => $index,
                        'record_id' => $record['id'] ?? $index,
                        'message' => "Invalid reference: {$field}={$record[$field]} not found in {$targetSection}",
                        'severity' => 'warning',
                    ];
                }
            }
        }
    }

    /**
     * Get required fields for each section.
     */
    protected function getRequiredFields(string $section): array
    {
        $fields = [
            'parties' => ['name'],
            'clients' => ['name'],
            'customers' => ['name'],
            'items' => ['name'],
            'products' => ['name'],
            'documents' => ['number', 'issue_date'],
            'invoices' => ['number', 'issue_date'],
            'document_lines' => ['document_id', 'name'],
            'invoice_items' => ['invoice_id', 'name'],
            'payments' => ['amount', 'paid_on'],
            'expenses' => ['amount', 'spent_on'],
        ];

        return $fields[$section] ?? [];
    }

    /**
     * Get unique fields for duplicate detection.
     */
    protected function getUniqueFields(string $section): array
    {
        $fields = [
            'parties' => ['gstin'],
            'clients' => ['email', 'gstin'],
            'items' => ['sku'],
            'products' => ['sku'],
            'documents' => ['number'],
            'invoices' => ['number'],
        ];

        return $fields[$section] ?? [];
    }

    /**
     * Get relationship mappings.
     */
    protected function getRelationships(string $section): array
    {
        $relationships = [
            'documents' => [
                'party_id' => 'parties',
                'client_id' => 'clients',
            ],
            'invoices' => [
                'party_id' => 'parties',
                'client_id' => 'clients',
            ],
            'document_lines' => [
                'document_id' => 'documents',
                'item_id' => 'items',
            ],
            'invoice_items' => [
                'invoice_id' => 'invoices',
                'product_id' => 'products',
            ],
            'payments' => [
                'document_id' => 'documents',
                'invoice_id' => 'invoices',
                'party_id' => 'parties',
            ],
            'expenses' => [
                'party_id' => 'parties',
            ],
        ];

        return $relationships[$section] ?? [];
    }

    /**
     * Check if validation has critical errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if validation has warnings.
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }
}
