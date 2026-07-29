<?php

namespace App\Services\Gst;

use App\Models\Document;
use App\Models\Expense;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;

/** GSTR-1 / GSTR-3B / HSN summary builders (JSON offline-utility friendly). */
class GstrReportService
{
    public function gstr1(Carbon $from, Carbon $to): array
    {
        $tenant = TenantContext::get();
        $docs = Document::with('lines', 'party', 'einvoice')
            ->gst()
            ->whereIn('doc_type', ['invoice', 'bill_of_supply', 'credit_note', 'debit_note'])
            ->whereNot('status', 'draft')
            ->whereNot('status', 'cancelled')
            ->between($from->toDateString(), $to->toDateString())
            ->get();

        $b2b = [];
        $b2cl = [];   // inter-state B2C > 1,00,000 (post-2024 threshold)
        $b2cs = [];
        $exp = [];
        $cdnr = [];
        $hsn = [];

        foreach ($docs as $doc) {
            $itms = [];
            foreach ($doc->lines as $i => $line) {
                $itms[] = ['num' => $i + 1, 'itm_det' => array_filter([
                    'txval' => (float) $line->taxable_value,
                    'rt' => (float) $line->gst_rate,
                    'iamt' => (float) $line->igst_amount ?: null,
                    'camt' => (float) $line->cgst_amount ?: null,
                    'samt' => (float) $line->sgst_amount ?: null,
                    'csamt' => (float) $line->cess_amount ?: null,
                ], fn ($v) => $v !== null)];

                $key = ($line->hsn_sac ?: 'NA').'|'.$line->gst_rate;
                $hsn[$key] ??= ['hsn_sc' => $line->hsn_sac, 'uqc' => $line->uqc, 'qty' => 0, 'rt' => (float) $line->gst_rate,
                    'txval' => 0, 'iamt' => 0, 'camt' => 0, 'samt' => 0, 'csamt' => 0];
                $hsn[$key]['qty'] += (float) $line->quantity;
                $hsn[$key]['txval'] += (float) $line->taxable_value;
                $hsn[$key]['iamt'] += (float) $line->igst_amount;
                $hsn[$key]['camt'] += (float) $line->cgst_amount;
                $hsn[$key]['samt'] += (float) $line->sgst_amount;
                $hsn[$key]['csamt'] += (float) $line->cess_amount;
            }

            $inv = [
                'inum' => $doc->number,
                'idt' => $doc->issue_date->format('d-m-Y'),
                'val' => (float) $doc->grand_total,
                'pos' => $doc->place_of_supply_code,
                'rchrg' => $doc->reverse_charge ? 'Y' : 'N',
                'inv_typ' => match ($doc->supply_type) { 'SEZ' => 'SEWP', 'DEEMED_EXPORT' => 'DE', default => 'R' },
                'irn' => $doc->einvoice?->irn,
                'itms' => $itms,
            ];

            if (in_array($doc->doc_type, ['credit_note', 'debit_note'], true)) {
                $cdnr[] = ['ctin' => $doc->party?->gstin, 'nt' => array_merge($inv, [
                    'ntty' => $doc->doc_type === 'credit_note' ? 'C' : 'D',
                    'nt_num' => $doc->number,
                    'nt_dt' => $doc->issue_date->format('d-m-Y'),
                ])];
            } elseif ($doc->is_export) {
                $exp[] = ['exp_typ' => $doc->with_payment_of_tax ? 'WPAY' : 'WOPAY', 'inv' => [$inv]];
            } elseif ($doc->party?->gstin) {
                $b2b[$doc->party->gstin]['ctin'] = $doc->party->gstin;
                $b2b[$doc->party->gstin]['inv'][] = $inv;
            } elseif ($doc->is_interstate && (float) $doc->grand_total > 100000) {
                $b2cl[$doc->place_of_supply_code]['pos'] = $doc->place_of_supply_code;
                $b2cl[$doc->place_of_supply_code]['inv'][] = $inv;
            } else {
                foreach ($doc->lines as $line) {
                    $k = $doc->place_of_supply_code.'|'.$line->gst_rate;
                    $b2cs[$k] ??= ['sply_ty' => $doc->is_interstate ? 'INTER' : 'INTRA', 'pos' => $doc->place_of_supply_code,
                        'typ' => 'OE', 'rt' => (float) $line->gst_rate, 'txval' => 0, 'iamt' => 0, 'camt' => 0, 'samt' => 0, 'csamt' => 0];
                    $b2cs[$k]['txval'] += (float) $line->taxable_value;
                    $b2cs[$k]['iamt'] += (float) $line->igst_amount;
                    $b2cs[$k]['camt'] += (float) $line->cgst_amount;
                    $b2cs[$k]['samt'] += (float) $line->sgst_amount;
                    $b2cs[$k]['csamt'] += (float) $line->cess_amount;
                }
            }
        }

        return [
            'gstin' => $tenant?->gstin,
            'fp' => $from->format('mY'),
            'version' => 'GST3.2',
            'hash' => 'hash',
            'b2b' => array_values($b2b),
            'b2cl' => array_values($b2cl),
            'b2cs' => array_values($b2cs),
            'cdnr' => $cdnr,
            'exp' => $exp,
            'hsn' => ['data' => array_values(array_map(fn ($r, $i) => $r + ['num' => $i + 1], $hsn, array_keys(array_values($hsn))))],
        ];
    }

    public function gstr3b(Carbon $from, Carbon $to): array
    {
        $sales = Document::gst()->whereIn('doc_type', ['invoice', 'bill_of_supply', 'debit_note'])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->between($from->toDateString(), $to->toDateString())->get();
        $credits = Document::gst()->ofType('credit_note')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->between($from->toDateString(), $to->toDateString())->get();

        $sum = fn ($col, $c) => (float) $col->sum($c);
        $net = fn ($c) => $sum($sales, $c) - $sum($credits, $c);

        $itc = Expense::where('itc_eligible', true)
            ->whereBetween('spent_on', [$from->toDateString(), $to->toDateString()])->sum('gst_amount');
        $purchaseItc = Document::gst()->ofType('purchase_bill')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->between($from->toDateString(), $to->toDateString())->get();

        return [
            'period' => $from->format('m-Y'),
            'table_3_1_a_outward_taxable' => [
                'taxable_value' => round($net('taxable_value'), 2),
                'igst' => round($net('igst_total'), 2),
                'cgst' => round($net('cgst_total'), 2),
                'sgst' => round($net('sgst_total'), 2),
                'cess' => round($net('cess_total'), 2),
            ],
            'table_3_1_b_zero_rated' => [
                'taxable_value' => round((float) $sales->where('is_export', true)->sum('taxable_value'), 2),
                'igst' => round((float) $sales->where('is_export', true)->sum('igst_total'), 2),
            ],
            'table_3_1_d_inward_reverse_charge' => [
                'taxable_value' => round((float) $purchaseItc->where('reverse_charge', true)->sum('taxable_value'), 2),
            ],
            'table_4_itc_available' => [
                'igst' => round((float) $purchaseItc->sum('igst_total'), 2),
                'cgst' => round((float) $purchaseItc->sum('cgst_total'), 2),
                'sgst' => round((float) $purchaseItc->sum('sgst_total'), 2),
                'other_expenses_gst' => round((float) $itc, 2),
            ],
            'net_tax_payable' => round(
                $net('igst_total') + $net('cgst_total') + $net('sgst_total')
                - (float) $purchaseItc->sum('igst_total') - (float) $purchaseItc->sum('cgst_total')
                - (float) $purchaseItc->sum('sgst_total') - (float) $itc, 2),
        ];
    }
}
