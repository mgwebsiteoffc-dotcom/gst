<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Item;
use App\Models\Party;
use App\Services\Gst\GstrReportService;
use App\Services\Reports\BusinessReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function __construct(
        protected GstrReportService $gstr,
        protected BusinessReportService $biz,
    ) {}

    protected function range(Request $request): array
    {
        return [
            Carbon::parse($request->get('from', now()->startOfMonth())),
            Carbon::parse($request->get('to', now()->endOfMonth())),
        ];
    }

    public function gstr1(Request $request)
    {
        [$from, $to] = $this->range($request);
        $data = $this->gstr->gstr1($from, $to);

        return $request->wantsJson() || $request->boolean('json')
            ? response()->json($data)
            : view('reports.gstr1', compact('data', 'from', 'to'));
    }

    public function gstr3b(Request $request)
    {
        [$from, $to] = $this->range($request);
        $data = $this->gstr->gstr3b($from, $to);

        return $request->boolean('json') ? response()->json($data) : view('reports.gstr3b', compact('data', 'from', 'to'));
    }

    public function sales(Request $request)
    {
        [$from, $to] = $this->range($request);
        $documents = Document::with('party')->sales()->between($from->toDateString(), $to->toDateString())
            ->whereNotIn('status', ['draft', 'cancelled'])->get();

        return view('reports.sales', compact('documents', 'from', 'to'));
    }

    public function profitLoss(Request $request)
    {
        [$from, $to] = $this->range($request);

        return view('reports.profit-loss', [
            'data' => $this->biz->profitAndLoss($from, $to),
            'daily' => $this->biz->dailySales($from, $to),
            'from' => $from, 'to' => $to,
        ]);
    }

    public function itemWise(Request $request)
    {
        [$from, $to] = $this->range($request);

        return view('reports.item-wise', [
            'rows' => $this->biz->itemWiseSales($from, $to),
            'from' => $from, 'to' => $to,
        ]);
    }

    public function partyWise(Request $request)
    {
        [$from, $to] = $this->range($request);

        return view('reports.party-wise', [
            'rows' => $this->biz->partyWiseSales($from, $to),
            'ageing' => $this->biz->outstandingAgeing(),
            'from' => $from, 'to' => $to,
        ]);
    }

    public function ledger(Request $request, Party $party)
    {
        [$from, $to] = $this->range($request);

        return view('reports.ledger', [
            'ledger' => $this->biz->partyLedger($party, $from, $to),
            'from' => $from, 'to' => $to,
        ]);
    }

    /** CSV export — opens directly in Excel, no library needed. */
    public function export(Request $request, string $report): StreamedResponse
    {
        [$from, $to] = $this->range($request);

        [$headers, $rows] = match ($report) {
            'item-wise' => [
                ['Item', 'HSN/SAC', 'Quantity', 'Taxable', 'Tax', 'Total'],
                collect($this->biz->itemWiseSales($from, $to))
                    ->map(fn ($r) => [$r['name'], $r['hsn'], $r['quantity'], $r['taxable'], $r['tax'], $r['total']]),
            ],
            'party-wise' => [
                ['Party', 'Invoices', 'Taxable', 'Total', 'Outstanding'],
                collect($this->biz->partyWiseSales($from, $to))
                    ->map(fn ($r) => [$r['name'], $r['invoices'], $r['taxable'], $r['total'], $r['outstanding']]),
            ],
            'daily' => [
                ['Date', 'Invoices', 'Taxable', 'Tax', 'Total'],
                collect($this->biz->dailySales($from, $to))
                    ->map(fn ($r) => [$r['date']->format('Y-m-d'), $r['invoices'], $r['taxable'], $r['tax'], $r['total']]),
            ],
            default => [
                ['Number', 'Date', 'Party', 'GSTIN', 'Taxable', 'CGST', 'SGST', 'IGST', 'Total', 'Balance', 'Status'],
                Document::with('party')->sales()->whereNotIn('status', ['draft', 'cancelled'])
                    ->between($from->toDateString(), $to->toDateString())->get()
                    ->map(fn ($d) => [
                        $d->number, $d->issue_date->format('Y-m-d'), $d->party?->name, $d->party?->gstin,
                        $d->taxable_value, $d->cgst_total, $d->sgst_total, $d->igst_total,
                        $d->grand_total, $d->balance_due, $d->status,
                    ]),
            ],
        };

        $filename = $report.'-'.$from->format('Y-m-d').'-to-'.$to->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function stock()
    {
        $items = Item::where('track_inventory', true)->orderBy('name')->get()
            ->map(fn ($i) => [
                'item' => $i,
                'on_hand' => $i->stockOnHand(),
                'value' => $i->stockOnHand() * (float) $i->purchase_price,
                'low' => $i->isLowStock(),
            ]);

        return view('reports.stock', compact('items'));
    }
}
