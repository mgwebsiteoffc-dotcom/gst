@extends('layouts.app')
@section('title', 'Products & Services')

@section('content')
<x-page title="Products & Services" subtitle="Your catalog, with HSN/SAC and GST rates.">
    <x-btn variant="primary" :href="route('items.create')">+ New item</x-btn>
</x-page>

<form class="mb-4 flex flex-wrap gap-2">
    <x-input name="q" value="{{ request('q') }}" placeholder="Search name, SKU or HSN" class="!w-72"/>
    <x-select name="type" class="!w-40" onchange="this.form.submit()">
        <option value="">All types</option>
        <option value="goods" @selected(request('type') === 'goods')>Goods</option>
        <option value="service" @selected(request('type') === 'service')>Services</option>
    </x-select>
    <x-btn type="submit">Search</x-btn>
</form>

<x-card class="!p-0">
    @if ($items->isEmpty())
        <x-empty title="No items yet" message="Add products or services to speed up invoicing.">
            <x-btn variant="primary" :href="route('items.create')">+ New item</x-btn>
        </x-empty>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Item</th>
                        <th class="px-3 font-medium">HSN/SAC</th>
                        <th class="px-3 text-right font-medium">Sale price</th>
                        <th class="px-3 text-right font-medium">GST</th>
                        <th class="px-3 text-right font-medium">Stock</th>
                        <th class="px-5"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($items as $it)
                    <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/60">
                        <td class="px-5 py-3">
                            <div class="font-medium text-slate-900">{{ $it->name }}</div>
                            <div class="text-xs text-slate-400">{{ $it->sku ?: 'No SKU' }} · {{ ucfirst($it->type) }}</div>
                        </td>
                        <td class="px-3 font-mono text-xs">{{ $it->hsn_sac ?: '—' }}</td>
                        <td class="px-3 text-right tabular-nums">₹{{ number_format($it->sale_price, 2) }}</td>
                        <td class="px-3 text-right tabular-nums">{{ rtrim(rtrim(number_format($it->gst_rate, 2), '0'), '.') }}%</td>
                        <td class="px-3 text-right tabular-nums">
                            @if ($it->track_inventory)
                                <span class="{{ $it->isLowStock() ? 'font-medium text-rose-600' : '' }}">
                                    {{ rtrim(rtrim(number_format($it->stockOnHand(), 3), '0'), '.') }}
                                </span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right"><x-btn variant="ghost" size="sm" :href="route('items.edit', $it)">Edit</x-btn></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-card>

<div class="mt-4">{{ $items->links() }}</div>
@endsection
