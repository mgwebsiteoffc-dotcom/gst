<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Item;
use App\Models\Party;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $start = now()->startOfMonth();
        $sales = Document::sales()->whereNotIn('status', ['draft', 'cancelled']);

        $monthly = Document::sales()->whereNotIn('status', ['draft', 'cancelled'])
            ->where('issue_date', '>=', now()->subMonths(11)->startOfMonth())
            ->get()
            ->groupBy(fn ($d) => $d->issue_date->format('Y-m'))
            ->map(fn ($g) => round((float) $g->sum('grand_total'), 2));

        return view('dashboard.index', [
            'mtdSales' => (clone $sales)->where('issue_date', '>=', $start)->sum('grand_total'),
            'mtdTax' => (clone $sales)->where('issue_date', '>=', $start)
                ->sum(\DB::raw('cgst_total + sgst_total + igst_total + cess_total')),
            'outstanding' => Document::sales()->whereNotIn('status', ['draft', 'cancelled', 'paid'])->sum('balance_due'),
            'overdue' => Document::sales()->whereNotIn('status', ['draft', 'cancelled', 'paid'])
                ->whereDate('due_date', '<', now())->sum('balance_due'),
            'customers' => Party::customers()->count(),
            'lowStock' => Item::where('track_inventory', true)->get()->filter->isLowStock()->take(10),
            'recent' => Document::with('party')->sales()->latest('id')->take(8)->get(),
            'monthly' => $monthly,
        ]);
    }
}
