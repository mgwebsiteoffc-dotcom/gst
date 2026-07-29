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
        $prefix = self::defaultPrefix($docType, $taxMode).'/'.$fy.'/';

        return DB::transaction(function () use ($docType, $fy, $tenantId, $prefix) {
            $series = DocumentSeries::query()
                ->where('tenant_id', $tenantId)
                ->where('doc_type', $docType)
                ->where('financial_year', $fy)
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->first();

            if (! $series) {
                $series = DocumentSeries::create([
                    'tenant_id' => $tenantId,
                    'doc_type' => $docType,
                    'prefix' => $prefix,
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
