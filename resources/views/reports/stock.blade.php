@extends('layouts.app')
@section('title', 'Stock Report')

@section('content')
<x-page title="Stock Report" subtitle="Current inventory levels and valuation.">
    <x-btn variant="secondary" :href="route('items.index')">
        Manage Products
    </x-btn>
</x-page>

@php
    $totalItems = $items->count();
    $lowStock = $items->where('low', true)->count();
    $totalValue = $items->sum('value');
    $totalUnits = $items->sum('on_hand');
@endphp

{{-- Summary Cards --}}
<div class="mb-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-card>
        <div class="text-xs uppercase tracking-wide text-slate-500">Tracked Items</div>
        <div class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($totalItems) }}</div>
    </x-card>
    <x-card>
        <div class="text-xs uppercase tracking-wide text-slate-500">Total Units</div>
        <div class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($totalUnits, 0) }}</div>
    </x-card>
    <x-card>
        <div class="text-xs uppercase tracking-wide text-slate-500">Stock Value</div>
        <div class="mt-1 text-2xl font-semibold text-slate-900">₹{{ number_format($totalValue, 2) }}</div>
    </x-card>
    <x-card>
        <div class="text-xs uppercase tracking-wide text-slate-500">Low Stock Alerts</div>
        <div class="mt-1 text-2xl font-semibold {{ $lowStock > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
            {{ $lowStock }}
            @if ($lowStock > 0)
                <span class="text-xs font-normal text-rose-500">need reorder</span>
            @endif
        </div>
    </x-card>
</div>

{{-- Stock Table --}}
<x-card class="!p-0">
    @if ($items->isEmpty())
        <x-empty title="No tracked items" message="Enable inventory tracking on your products to see stock levels here." />
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-100 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Item</th>
                        <th class="px-3 font-medium">SKU</th>
                        <th class="px-3 text-right font-medium">On Hand</th>
                        <th class="px-3 text-right font-medium">Reorder Level</th>
                        <th class="px-3 text-right font-medium">Purchase Price</th>
                        <th class="px-5 text-right font-medium">Stock Value</th>
                        <th class="px-3 text-center font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($items as $row)
                    @php $item = $row['item']; @endphp
                    <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/60 {{ $row['low'] ? 'bg-rose-50/40' : '' }}">
                        <td class="px-5 py-3">
                            <div class="font-medium text-slate-900">{{ $item->name }}</div>
                            <div class="text-xs text-slate-400">{{ $item->hsn_sac ?: '—' }} · {{ $item->uqc }}</div>
                        </td>
                        <td class="px-3 font-mono text-xs text-slate-500">{{ $item->sku ?: '—' }}</td>
                        <td class="px-3 text-right tabular-nums font-medium {{ $row['on_hand'] <= 0 ? 'text-rose-600' : 'text-slate-900' }}">
                            {{ rtrim(rtrim(number_format($row['on_hand'], 3), '0'), '.') }}
                        </td>
                        <td class="px-3 text-right tabular-nums text-slate-500">
                            {{ rtrim(rtrim(number_format((float) $item->reorder_level, 3), '0'), '.') }}
                        </td>
                        <td class="px-3 text-right tabular-nums text-slate-500">
                            ₹{{ number_format((float) $item->purchase_price, 2) }}
                        </td>
                        <td class="px-5 text-right font-medium tabular-nums">
                            ₹{{ number_format($row['value'], 2) }}
                        </td>
                        <td class="px-3 text-center">
                            @if ($row['low'])
                                <x-badge tone="rose">Low Stock</x-badge>
                            @elseif ($row['on_hand'] <= 0)
                                <x-badge tone="slate">Out of Stock</x-badge>
                            @else
                                <x-badge tone="emerald">In Stock</x-badge>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot class="border-t border-slate-200 bg-slate-50 text-sm font-semibold">
                    <tr>
                        <td class="px-5 py-3 text-slate-700" colspan="2">Total ({{ $totalItems }} items)</td>
                        <td class="px-3 text-right tabular-nums">{{ number_format($totalUnits, 0) }}</td>
                        <td class="px-3" colspan="2"></td>
                        <td class="px-5 text-right tabular-nums">₹{{ number_format($totalValue, 2) }}</td>
                        <td class="px-3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</x-card>
@endsection