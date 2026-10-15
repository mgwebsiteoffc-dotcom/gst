@extends('layouts.app')
@section('title', __('Stock'))

@section('content')
@php
    $totalValue = $items->sum('value');
    $lowCount = $items->where('low', true)->count();
    $outCount = $items->filter(fn ($r) => (float) $r['on_hand'] <= 0)->count();
    $unit = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');
@endphp

<x-page :title="__('Stock')" :subtitle="__('What is on hand, and what it is worth.')">
    <x-btn variant="secondary" :href="route('reports.export', 'stock')">{{ __('Download CSV') }}</x-btn>
</x-page>

<div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach ([
        [__('Stock value'), '₹'.number_format($totalValue, 2), 'text-slate-900'],
        [__('Items tracked'), number_format($items->count()), 'text-slate-900'],
        [__('Low stock'), number_format($lowCount), $lowCount ? 'text-amber-600' : 'text-slate-400'],
        [__('Out of stock'), number_format($outCount), $outCount ? 'text-rose-600' : 'text-slate-400'],
    ] as [$label, $value, $tone])
        <x-card>
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</div>
            <div class="mt-1 text-xl font-semibold {{ $tone }}">{{ $value }}</div>
        </x-card>
    @endforeach
</div>

<form class="mb-4 flex flex-wrap items-end gap-2">
    <x-input name="q" value="{{ request('q') }}" placeholder="{{ __('Search name, SKU or HSN') }}" class="!w-72" />
    <label class="flex items-center gap-2 pb-2 text-sm text-slate-600">
        <input type="checkbox" name="low" value="1" @checked(request()->boolean('low'))
               class="rounded border-slate-300 text-indigo-600">
        {{ __('Low stock only') }}
    </label>
    <x-btn type="submit">{{ __('Search') }}</x-btn>
    @if (request('q') || request()->boolean('low'))
        <x-btn variant="ghost" :href="route('reports.stock')">{{ __('Clear') }}</x-btn>
    @endif
</form>

<x-card class="!p-0 overflow-x-auto">
    @if ($items->isEmpty())
        <x-empty :title="__('Nothing to show')"
                 :message="__('Add products with stock tracking to see them here.')" />
    @else
        <table class="w-full min-w-[860px] text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">{{ __('Item') }}</th>
                    <th class="px-3 font-medium">HSN/SAC</th>
                    <th class="px-3 font-medium">{{ __('Unit') }}</th>
                    <th class="px-3 text-right font-medium">{{ __('On hand') }}</th>
                    <th class="px-3 text-right font-medium">{{ __('Reorder level') }}</th>
                    <th class="px-3 text-right font-medium">{{ __('Purchase price') }}</th>
                    <th class="px-3 text-right font-medium">{{ __('Stock value') }}</th>
                    <th class="px-5 font-medium">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($items as $row)
                @php $item = $row['item']; @endphp
                <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/60">
                    <td class="px-5 py-3">
                        <a href="{{ route('items.edit', $item) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $item->name }}</a>
                        @if ($item->sku)
                            <div class="text-xs text-slate-400">{{ $item->sku }}</div>
                        @endif
                    </td>
                    <td class="px-3 font-mono text-xs">{{ $item->hsn_sac ?: '—' }}</td>
                    <td class="px-3 text-xs text-slate-500">{{ $item->uqc }}</td>
                    <td class="px-3 text-right tabular-nums {{ (float) $row['on_hand'] <= 0 ? 'text-rose-600' : '' }}">
                        {{ $unit($row['on_hand']) }}
                    </td>
                    <td class="px-3 text-right tabular-nums text-slate-500">{{ $unit($item->reorder_level) }}</td>
                    <td class="px-3 text-right tabular-nums">₹{{ number_format((float) $item->purchase_price, 2) }}</td>
                    <td class="px-3 text-right font-medium tabular-nums">₹{{ number_format($row['value'], 2) }}</td>
                    <td class="px-5">
                        @if ((float) $row['on_hand'] <= 0)
                            <x-badge tone="rose">{{ __('Out of stock') }}</x-badge>
                        @elseif ($row['low'])
                            <x-badge tone="amber">{{ __('Low') }}</x-badge>
                        @else
                            <x-badge tone="emerald">{{ __('OK') }}</x-badge>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot class="border-t border-slate-200 bg-slate-50">
                <tr>
                    <td class="px-5 py-3 font-medium" colspan="6">{{ __('Total stock value') }}</td>
                    <td class="px-3 py-3 text-right font-semibold tabular-nums" colspan="2">₹{{ number_format($totalValue, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
</x-card>

<p class="mt-3 text-xs text-slate-500">
    {{ __('Stock value uses the purchase price. Items without inventory tracking are excluded.') }}
</p>
@endsection
