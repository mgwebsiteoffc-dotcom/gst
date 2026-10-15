@extends('layouts.app')
@section('title', 'Party-wise Sales')

@section('content')
<x-page title="Party-wise Sales and Outstanding" subtitle="Who buys most, and who still owes you." />
@include('reports._filter', ['export' => 'party-wise'])

<div class="mb-5 grid gap-4 sm:grid-cols-5">
    @foreach ($ageing['buckets'] as $label => $amount)
        <x-card>
            <div class="text-xs uppercase tracking-wide text-slate-500">
                {{ $label === 'current' ? 'Not due' : $label.' days' }}
            </div>
            <div class="mt-1 text-lg font-semibold {{ $label === 'current' ? 'text-slate-900' : ($amount > 0 ? 'text-rose-600' : 'text-slate-300') }}">
                ₹{{ number_format($amount, 2) }}
            </div>
        </x-card>
    @endforeach
</div>

<x-card class="!p-0 overflow-x-auto">
    @if (! count($rows))
        <x-empty title="No sales in this period" />
    @else
        <table class="w-full min-w-[600px] text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase text-slate-500">
                <tr><th class="px-5 py-3 font-medium">Party</th><th class="px-3 text-right font-medium">Invoices</th>
                    <th class="px-3 text-right font-medium">Taxable</th><th class="px-3 text-right font-medium">Total</th>
                    <th class="px-5 text-right font-medium">Outstanding</th></tr>
            </thead>
            <tbody>
            @foreach ($rows as $r)
                <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/60">
                    <td class="px-5 py-3 font-medium text-slate-900">{{ $r['name'] }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $r['invoices'] }}</td>
                    <td class="px-3 text-right tabular-nums">{{ number_format($r['taxable'], 2) }}</td>
                    <td class="px-3 text-right tabular-nums">₹{{ number_format($r['total'], 2) }}</td>
                    <td class="px-5 text-right font-medium tabular-nums {{ $r['outstanding'] > 0 ? 'text-rose-600' : 'text-slate-300' }}">
                        ₹{{ number_format($r['outstanding'], 2) }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</x-card>
@endsection
