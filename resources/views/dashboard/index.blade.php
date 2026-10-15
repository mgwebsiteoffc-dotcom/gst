@extends('layouts.app')
@section('title', __('Dashboard'))

@section('content')
    <h1 class="text-2xl font-semibold mb-6">{{ __('Dashboard') }}</h1>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4 mb-6">
        @foreach ([
            [__('Sales (MTD)'), '₹ '.number_format($mtdSales, 2)],
            [__('GST Collected (MTD)'), '₹ '.number_format($mtdTax, 2)],
            [__('Outstanding'), '₹ '.number_format($outstanding, 2)],
            [__('Overdue'), '₹ '.number_format($overdue, 2)],
        ] as [$label, $value])
            <div class="rounded-xl bg-white p-4 shadow-sm border border-slate-200">
                <div class="text-xs uppercase text-slate-500">{{ $label }}</div>
                <div class="text-xl font-semibold mt-1">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-sm">
                <span id="offline-dot" class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                <span class="font-semibold">{{ __('Offline billing') }}</span>
                <span id="offline-state" class="text-slate-500">{{ __('You are online') }}</span>
                <span class="hidden text-slate-500" data-offline-wrap>· <span data-offline-count>0</span> {{ __('bills waiting to sync') }}</span>
            </div>
            <div class="flex gap-2">
                <x-btn variant="secondary" size="sm" :href="route('offline.create')">{{ __('New offline bill') }}</x-btn>
                <x-btn variant="primary" size="sm" onclick="window.BBOffline && BBOffline.syncNow()">{{ __('Sync now') }}</x-btn>
            </div>
        </div>
        <p class="mt-1 text-xs text-slate-500">{{ __('No internet? Bill offline — it syncs later.') }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-xl bg-white p-4 border border-slate-200">
            <h2 class="font-semibold mb-3">{{ __('Recent documents') }}</h2>
            <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] text-sm">
                <thead class="text-left text-slate-500">
                    <tr><th class="py-1">{{ __('Number') }}</th><th>{{ __('Party') }}</th><th>{{ __('Date') }}</th><th class="text-right">{{ __('Total') }}</th><th>{{ __('Status') }}</th></tr>
                </thead>
                <tbody>
                @forelse ($recent as $doc)
                    <tr class="border-t">
                        <td class="py-1"><a class="text-indigo-600" href="{{ route('documents.show', $doc) }}">{{ $doc->number }}</a></td>
                        <td>{{ $doc->party?->name ?? '—' }}</td>
                        <td>{{ $doc->issue_date->format('d M Y') }}</td>
                        <td class="text-right">₹ {{ number_format($doc->grand_total, 2) }}</td>
                        <td><span class="text-xs px-2 py-0.5 rounded bg-slate-100">{{ __(ucfirst(str_replace('_', ' ', $doc->status))) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-4 text-slate-500">{{ __('No documents yet.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <div class="rounded-xl bg-white p-4 border border-slate-200">
            <h2 class="font-semibold mb-3">{{ __('Low stock') }}</h2>
            <ul class="text-sm space-y-1">
                @forelse ($lowStock as $item)
                    <li class="flex justify-between border-b py-1">
                        <span>{{ $item->name }}</span>
                        <span class="text-rose-600">{{ rtrim(rtrim(number_format($item->stockOnHand(), 3), '0'), '.') }}</span>
                    </li>
                @empty
                    <li class="text-slate-500">{{ __('All items above reorder level.') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
