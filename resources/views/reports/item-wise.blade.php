@extends('layouts.app')
@section('title', 'Item-wise Sales')

@section('content')
<x-page title="Item-wise Sales" subtitle="Which products are actually selling." />
@include('reports._filter', ['export' => 'item-wise'])

<x-card class="!p-0 overflow-x-auto">
    @if (! count($rows))
        <x-empty title="No sales in this period" message="Try widening the date range." />
    @else
        <table class="w-full min-w-[600px] text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase text-slate-500">
                <tr><th class="px-5 py-3 font-medium">Item</th><th class="px-3 font-medium">HSN/SAC</th>
                    <th class="px-3 text-right font-medium">Qty sold</th><th class="px-3 text-right font-medium">Taxable</th>
                    <th class="px-3 text-right font-medium">Tax</th><th class="px-5 text-right font-medium">Total</th></tr>
            </thead>
            <tbody>
            @foreach ($rows as $r)
                <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/60">
                    <td class="px-5 py-3 font-medium text-slate-900">{{ $r['name'] }}</td>
                    <td class="px-3 font-mono text-xs">{{ $r['hsn'] ?: '—' }}</td>
                    <td class="px-3 text-right tabular-nums">{{ rtrim(rtrim(number_format($r['quantity'], 3), '0'), '.') }}</td>
                    <td class="px-3 text-right tabular-nums">{{ number_format($r['taxable'], 2) }}</td>
                    <td class="px-3 text-right tabular-nums text-slate-500">{{ number_format($r['tax'], 2) }}</td>
                    <td class="px-5 text-right font-medium tabular-nums">₹{{ number_format($r['total'], 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</x-card>
@endsection
