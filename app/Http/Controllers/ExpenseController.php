<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Party;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public const CATEGORIES = [
        'rent' => 'Rent',
        'salaries' => 'Salaries & wages',
        'utilities' => 'Utilities',
        'travel' => 'Travel',
        'marketing' => 'Marketing & ads',
        'software' => 'Software & subscriptions',
        'office_supplies' => 'Office supplies',
        'professional_fees' => 'Professional fees (CA, legal)',
        'freight' => 'Freight & transport',
        'purchase' => 'Stock purchase (no bill)',
        'misc' => 'Miscellaneous',
    ];

    public function index(Request $request)
    {
        $expenses = Expense::with('party')
            ->when($request->q, fn ($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('note', 'like', "%$s%")
                    ->orWhere('reference', 'like', "%$s%")
                    ->orWhereHas('party', fn ($p) => $p->where('name', 'like', "%$s%"));
            }))
            ->when($request->category, fn ($q, $c) => $q->where('category', $c))
            ->when($request->from && $request->to, fn ($q) => $q->whereBetween('spent_on', [$request->from, $request->to]))
            ->latest('spent_on')->latest('id')
            ->paginate(25)->withQueryString();

        $month = Expense::whereBetween('spent_on', [now()->startOfMonth(), now()->endOfMonth()]);
        $summary = [
            'total' => (float) (clone $month)->sum('amount'),
            'itc' => (float) (clone $month)->where('itc_eligible', true)->sum('gst_amount'),
        ];

        return view('expenses.index', [
            'expenses' => $expenses,
            'summary' => $summary,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function create()
    {
        return view('expenses.form', [
            'expense' => null,
            'categories' => self::CATEGORIES,
            'vendors' => Party::vendors()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        Expense::create($this->validated($request));

        return redirect()->route('expenses.index')->with('success', 'Expense recorded.');
    }

    public function edit(Expense $expense)
    {
        return view('expenses.form', [
            'expense' => $expense,
            'categories' => self::CATEGORIES,
            'vendors' => Party::vendors()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $expense->update($this->validated($request));

        return redirect()->route('expenses.index')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'party_id' => ['nullable', 'exists:parties,id'],
            'category' => ['required', 'string', 'max:50'],
            'spent_on' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'gst_amount' => ['nullable', 'numeric', 'min:0'],
            'itc_eligible' => ['boolean'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $data['itc_eligible'] = (bool) ($data['itc_eligible'] ?? false);
        $data['gst_amount'] = (float) ($data['gst_amount'] ?? 0);
        if (! $data['itc_eligible']) {
            $data['gst_amount'] = 0;
        }

        return $data;
    }
}
