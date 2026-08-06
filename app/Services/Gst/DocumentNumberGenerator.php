<?php

namespace App\Services\Gst;

use App\Models\DocumentSeries;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentNumberGenerator
{
    /** Indian FY label for a date, e.g. 2026-27. */
    public static function financialYear(Carbon $date): string
    {
        $start = $date->month >= 4 ? $date->year : $date->year - 1;
        return $start.'-'.substr((string) ($start + 1), 2);
    }

    /**
     * GST and non-GST documents keep completely separate running series, because a
     * GST audit expects tax invoice numbers to form an unbroken consecutive sequence.
     */
    public function next(string $docType, ?Carbon $date = null, string $taxMode = 'gst'): string
    {
        $date ??= now();
        $fy = self::financialYear($date);
        $tenantId = TenantContext::id();

        // 'invoice' is the only doc type issued under both tax modes (INV vs BILL),
        // so it's the only one that needs a separate series per mode.
        $seriesTaxMode = $docType === 'invoice' ? $taxMode : null;

        return DB::transaction(function () use ($docType, $taxMode, $seriesTaxMode, $fy, $tenantId) {
            // Looked up by tenant+doc_type+mode+FY only (not prefix), so a tenant who has
            // customized the prefix/next_number in Settings still hits the same row.
            $series = DocumentSeries::query()
                ->where('tenant_id', $tenantId)
                ->where('doc_type', $docType)
                ->where('financial_year', $fy)
                ->where('tax_mode', $seriesTaxMode)
                ->lockForUpdate()
                ->first();

            if (! $series) {
                $series = DocumentSeries::create([
                    'tenant_id' => $tenantId,
                    'doc_type' => $docType,
                    'tax_mode' => $seriesTaxMode,
                    'prefix' => self::defaultPrefix($docType, $taxMode).'/'.$fy.'/',
                    'financial_year' => $fy,
                    'next_number' => 1,
                ]);
            }

            $padding = max(1, (int) ($series->padding ?: 4));
            $number = $series->prefix.str_pad((string) $series->next_number, $padding, '0', STR_PAD_LEFT).($series->suffix ?? '');
            $series->increment('next_number');

            return $number;
        });
    }

    public static function defaultPrefix(string $docType, string $taxMode = 'gst'): string
    {
        if ($taxMode === 'non_gst') {
            return match ($docType) {
                'invoice' => 'BILL',
                'quotation' => 'QT',
                'proforma' => 'PI',
                'delivery_challan' => 'DC',
                'credit_note' => 'CN',
                'debit_note' => 'DN',
                'purchase_order' => 'PO',
                'purchase_bill' => 'PB',
                default => 'DOC',
            };
        }

        return match ($docType) {
            'invoice' => 'INV',
            'bill_of_supply' => 'BOS',
            'quotation' => 'QT',
            'proforma' => 'PI',
            'delivery_challan' => 'DC',
            'credit_note' => 'CN',
            'debit_note' => 'DN',
            'purchase_order' => 'PO',
            'purchase_bill' => 'PB',
            default => 'DOC',
        };
    }
}
