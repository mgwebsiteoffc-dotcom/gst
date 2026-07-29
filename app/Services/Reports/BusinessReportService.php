<?php

namespace App\Services\Reports;

use App\Models\Document;
use App\Models\Expense;
use App\Models\Party;
use App\Models\Payment;
use Illuminate\Support\Carbon;

/** Operational reports: P&L, ledgers, item-wise and day-wise sales. */
class BusinessReportService
{
    public function profitAndLoss(Carbon $from, Carbon $to): array
    {
        $range = [$from->toDateString(), $to->toDateString()];

        $sales = Document::whereIn('doc_type', ['invoice', 'bill_of_supply'])
            ->whereNotIn('status', ['draft', 'cancelled'])->between(...$range)->get();

        $returns = Document::ofType('credit_note')
            ->whereNotIn('status', ['draft', 'cancelled'])->between(...$range)->get();

        $purchases = Document::ofType('purchase_bill')
            ->whereNotIn('status', ['draft', 'cancelled'])->between(...$range)->get();

        $expenses = (float) Expense::whereBetween('spent_on', $range)->sum('amount');

        // Revenue and costs are net of tax — GST collected is not income.
        $revenue = (float) $sales->sum('taxable_value') - (float) $returns->sum('taxable_value');
        $cogs = (float) $purchases->sum('taxable_value');
        $gross = $revenue - $cogs;

        return [
            'from' => $from, 'to' => $to,
            'revenue' => round($revenue, 2),
            'returns' => round((float) $returns->sum('taxable_value'), 2),
            'cogs' => round($cogs, 2),
            'gross_profit' => round($gross, 2),
            'expenses' => round($expenses, 2),
            'net_profit' => round($gross - $expenses, 2),
            'margin' => $revenue > 0 ? round(($gross - $expenses) / $revenue * 100, 1) : 0.0,
            'invoice_count' => $sales->count(),
            'average_invoice' => $sales->count() ? round((float) $sales->sum('grand_total') / $sales->count(), 2) : 0.0,
        ];
    }

    /** Running-balance statement for one customer or vendor. */
    public function partyLedger(Party $party, Carbon $from, Carbon $to): array
    {
        $range = [$from->toDateString(), $to->toDateString()];

        $docs = Document::where('party_id', $party->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->between(...$range)->get()
            ->map(fn ($d) => [
                'date' => $d->issue_date,
                'particulars' => $d->title.' '.$d->number,
                'debit' => in_array($d->doc_type, ['invoice', 'bill_of_supply', 'debit_note'], true) ? (float) $d->grand_total : 0.0,
                'credit' => $d->doc_type === 'credit_note' ? (float) $d->grand_total : 0.0,
            ]);

        $payments = Payment::where('party_id', $party->id)
            ->whereBetween('paid_on', $range)->get()
            ->map(fn ($p) => [
                'date' => $p->paid_on,
                'particulars' => 'Payment received ('.$p->method.')'.($p->reference ? ' — '.$p->reference : ''),
                'debit' => 0.0,
                'credit' => (float) $p->amount,
            ]);

        $balance = (float) $party->opening_balance;

        $rows = $docs->concat($payments)
            ->sortBy('date')->values()
            ->map(function ($row) use (&$balance) {
                $balance += $row['debit'] - $row['credit'];
                $row['balance'] = round($balance, 2);

                return $row;
            });

        return [
            'party' => $party,
            'opening' => round((float) $party->opening_balance, 2),
            'rows' => $rows,
            'total_debit' => round($rows->sum('debit'), 2),
            'total_credit' => round($rows->sum('credit'), 2),
            'closing' => round($balance, 2),
        ];
    }

    /** Which products actually make money. */
    public function itemWiseSales(Carbon $from, Carbon $to): array
    {
        return Document::with('lines')
            ->whereIn('doc_type', ['invoice', 'bill_of_supply'])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->between($from->toDateString(), $to->toDateString())
            ->get()
            ->pluck('lines')->flatten()
            ->groupBy(fn ($l) => $l->name)
            ->map(fn ($lines, $name) => [
                'name' => $name,
                'hsn' => $lines->first()->hsn_sac,
                'quantity' => round((float) $lines->sum('quantity'), 3),
                'taxable' => round((float) $lines->sum('taxable_value'), 2),
                'tax' => round((float) $lines->sum(fn ($l) => $l->cgst_amount + $l->sgst_amount + $l->igst_amount), 2),
                'total' => round((float) $lines->sum('line_total'), 2),
            ])
            ->sortByDesc('total')->values()->all();
    }

    public function partyWiseSales(Carbon $from, Carbon $to): array
    {
        return Document::with('party')
            ->whereIn('doc_type', ['invoice', 'bill_of_supply'])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->between($from->toDateString(), $to->toDateString())
            ->get()
            ->groupBy(fn ($d) => $d->party?->name ?? 'Walk-in')
            ->map(fn ($docs, $name) => [
                'name' => $name,
                'invoices' => $docs->count(),
                'taxable' => round((float) $docs->sum('taxable_value'), 2),
                'total' => round((float) $docs->sum('grand_total'), 2),
                'outstanding' => round((float) $docs->sum('balance_due'), 2),
            ])
            ->sortByDesc('total')->values()->all();
    }

    /** Day-by-day sales, zero-filled so charts do not mislead. */
    public function dailySales(Carbon $from, Carbon $to): array
    {
        $byDay = Document::whereIn('doc_type', ['invoice', 'bill_of_supply'])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->between($from->toDateString(), $to->toDateString())
            ->get()
            ->groupBy(fn ($d) => $d->issue_date->toDateString());

        $rows = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $docs = $byDay[$day->toDateString()] ?? collect();
            $rows[] = [
                'date' => $day->copy(),
                'invoices' => $docs->count(),
                'taxable' => round((float) $docs->sum('taxable_value'), 2),
                'tax' => round((float) $docs->sum(fn ($d) => $d->tax_total), 2),
                'total' => round((float) $docs->sum('grand_total'), 2),
            ];
        }

        return $rows;
    }

    /** Receivables bucketed by how overdue they are. */
    public function outstandingAgeing(): array
    {
        $buckets = ['current' => 0.0, '1-30' => 0.0, '31-60' => 0.0, '61-90' => 0.0, '90+' => 0.0];

        $docs = Document::with('party')->sales()
            ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
            ->where('balance_due', '>', 0)->get();

        foreach ($docs as $d) {
            $days = $d->due_date ? now()->startOfDay()->diffInDays($d->due_date->startOfDay(), false) : 0;
            $overdue = $days < 0 ? abs($days) : 0;

            $key = match (true) {
                $overdue === 0 => 'current',
                $overdue <= 30 => '1-30',
                $overdue <= 60 => '31-60',
                $overdue <= 90 => '61-90',
                default => '90+',
            };

            $buckets[$key] += (float) $d->balance_due;
        }

        return ['buckets' => array_map(fn ($v) => round($v, 2), $buckets), 'documents' => $docs];
    }
}
