@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">Dashboard</h1>

    <div class="grid gap-4 md:grid-cols-4 mb-6">
        @foreach ([
            ['Sales (MTD)', '₹ '.number_format($mtdSales, 2)],
            ['GST Collected (MTD)', '₹ '.number_format($mtdTax, 2)],
            ['Outstanding', '₹ '.number_format($outstanding, 2)],
            ['Overdue', '₹ '.number_format($overdue, 2)],
        ] as [$label, $value])
            <div class="rounded-xl bg-white p-4 shadow-sm border border-slate-200">
                <div class="text-xs uppercase text-slate-500">{{ $label }}</div>
                <div class="text-xl font-semibold mt-1">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-xl bg-white p-4 border border-slate-200">
            <h2 class="font-semibold mb-3">Recent documents</h2>
            <table class="w-full text-sm">
                <thead class="text-left text-slate-500">
                    <tr><th class="py-1">Number</th><th>Party</th><th>Date</th><th class="text-right">Total</th><th>Status</th></tr>
                </thead>
                <tbody>
                @forelse ($recent as $doc)
                    <tr class="border-t">
                        <td class="py-1"><a class="text-indigo-600" href="{{ route('documents.show', $doc) }}">{{ $doc->number }}</a></td>
                        <td>{{ $doc->party?->name ?? '—' }}</td>
                        <td>{{ $doc->issue_date->format('d M Y') }}</td>
                        <td class="text-right">₹ {{ number_format($doc->grand_total, 2) }}</td>
                        <td><span class="text-xs px-2 py-0.5 rounded bg-slate-100">{{ $doc->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-4 text-slate-500">No documents yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-xl bg-white p-4 border border-slate-200">
            <h2 class="font-semibold mb-3">Low stock</h2>
            <ul class="text-sm space-y-1">
                @forelse ($lowStock as $item)
                    <li class="flex justify-between border-b py-1">
                        <span>{{ $item->name }}</span>
                        <span class="text-rose-600">{{ rtrim(rtrim(number_format($item->stockOnHand(), 3), '0'), '.') }}</span>
                    </li>
                @empty
                    <li class="text-slate-500">All items above reorder level.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
