@extends('layouts.app')
@section('title', 'Expenses')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Expenses</h1>
            <p class="mt-0.5 text-sm text-slate-500">
                This month: <span class="font-medium text-slate-700">₹ {{ number_format($summary['total'], 2) }}</span>
                · ITC claimable GST: <span class="font-medium text-emerald-700">₹ {{ number_format($summary['itc'], 2) }}</span>
            </p>
        </div>
        <x-btn variant="primary" :href="route('expenses.create')">+ Add expense</x-btn>
    </div>

    <form class="mb-4 flex flex-wrap gap-2 text-sm">
        <input name="q" value="{{ request('q') }}" placeholder="Search note, reference or vendor" class="rounded border-slate-300 px-3 py-2">
        <select name="category" class="rounded border-slate-300 px-3 py-2">
            <option value="">All categories</option>
            @foreach ($categories as $v => $label)
                <option value="{{ $v }}" @selected(request('category') === $v)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="rounded border-slate-300 px-3 py-2">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded border-slate-300 px-3 py-2">
        <button class="rounded bg-slate-800 px-4 py-2 text-white">Filter</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="p-3">Date</th><th>Category</th><th>Vendor / note</th><th>Reference</th>
                    <th class="text-right">Amount</th><th class="text-right">GST</th><th>ITC</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($expenses as $expense)
                <tr class="border-t">
                    <td class="p-3 whitespace-nowrap">{{ $expense->spent_on->format('d M Y') }}</td>
                    <td>{{ $categories[$expense->category] ?? ucfirst(str_replace('_', ' ', $expense->category)) }}</td>
                    <td>
                        <div class="font-medium text-slate-700">{{ $expense->party?->name ?? '—' }}</div>
                        @if ($expense->note)<div class="text-xs text-slate-400">{{ $expense->note }}</div>@endif
                    </td>
                    <td class="font-mono text-xs">{{ $expense->reference }}</td>
                    <td class="text-right font-medium tabular-nums">₹ {{ number_format($expense->amount, 2) }}</td>
                    <td class="text-right tabular-nums">{{ $expense->gst_amount > 0 ? '₹ '.number_format($expense->gst_amount, 2) : '—' }}</td>
                    <td>
                        @if ($expense->itc_eligible)
                            <x-badge tone="emerald">ITC</x-badge>
                        @else
                            <span class="text-xs text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="pr-3 text-right whitespace-nowrap">
                        <a class="text-xs text-indigo-600" href="{{ route('expenses.edit', $expense) }}">Edit</a>
                        <form method="post" action="{{ route('expenses.destroy', $expense) }}" class="inline"
                              onsubmit="return confirm('Delete this expense?')">
                            @csrf @method('DELETE')
                            <button class="ml-2 text-xs text-rose-500">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="p-10 text-center text-slate-500">
                        <p class="font-medium text-slate-700">No expenses recorded</p>
                        <p class="mt-1 text-sm">Track rent, salaries, ads and other costs here — they flow into Profit &amp; Loss, and ITC-eligible GST into GSTR-3B.</p>
                        <p class="mt-3"><x-btn variant="primary" size="sm" :href="route('expenses.create')">Add your first expense</x-btn></p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $expenses->links() }}</div>
@endsection
