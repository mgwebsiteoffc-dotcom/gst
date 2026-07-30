@extends('layouts.app')
@section('title', 'Shopify')

@section('content')
<x-page title="Shopify" subtitle="Turn every Shopify order into a GST invoice automatically." />

@if ($stores->isEmpty())
    <div class="grid gap-5 lg:grid-cols-3">
        <x-card class="lg:col-span-2" title="Connect your store">
            <form method="POST" action="{{ route('shopify.install') }}" class="grid gap-4">
                @csrf
                <x-field label="Your Shopify store URL" name="shop" required
                         hint="For example: my-shop.myshopify.com">
                    <x-input name="shop" required placeholder="my-shop.myshopify.com" />
                </x-field>
                <div>
                    <x-btn variant="primary" type="submit">Connect Shopify store</x-btn>
                </div>
            </form>

            <div class="mt-6 border-t border-slate-100 pt-5">
                <p class="text-sm font-medium text-slate-900">What happens after connecting</p>
                <ol class="mt-3 space-y-2 text-sm text-slate-600">
                    @foreach ([
                        'You approve the permissions on Shopify’s own screen.',
                        'We import your products so HSN codes and GST rates can be set once.',
                        'Every new order becomes a GST invoice with the correct place of supply.',
                        'Refunds automatically become credit notes against the original invoice.',
                    ] as $i => $step)
                        <li class="flex gap-2.5">
                            <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-indigo-100 text-[11px] font-semibold text-indigo-700">{{ $i + 1 }}</span>
                            <span>{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </x-card>

        <x-card title="Before you start">
            <ul class="space-y-3 text-sm text-slate-600">
                @foreach ([
                    'A Shopify store you own or staff-access',
                    'Your GSTIN saved under Settings → Business details',
                    'A default HSN code and GST rate for products missing one',
                ] as $req)
                    <li class="flex items-start gap-2">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="mt-0.5 shrink-0 text-emerald-500" style="width:16px;height:16px"><path d="m5 13 4 4L19 7"/></svg>
                        <span>{{ $req }}</span>
                    </li>
                @endforeach
            </ul>
            @unless ($tenant?->gstin)
                <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                    Add your GSTIN in <a href="{{ route('settings.index') }}" class="underline">Settings</a> first,
                    otherwise Shopify orders will be billed without GST.
                </p>
            @endunless
        </x-card>
    </div>
@else
    @foreach ($stores as $store)
        <div class="mb-5 grid gap-5 lg:grid-cols-3">
            <x-card class="lg:col-span-2" :title="$store->shop_domain">
                <x-slot:actions>
                    <x-badge tone="{{ $store->status === 'active' ? 'emerald' : 'rose' }}">{{ ucfirst($store->status) }}</x-badge>
                </x-slot:actions>

                <form method="POST" action="{{ route('shopify.settings', $store) }}" class="grid gap-4">
                    @csrf @method('PUT')

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="Default HSN code" name="default_hsn" hint="Used when a product has none.">
                            <x-input name="default_hsn" maxlength="8" class="font-mono" value="{{ $store->default_hsn }}" />
                        </x-field>
                        <x-field label="Default GST rate" name="default_gst_rate">
                            <x-select name="default_gst_rate">
                                @foreach (config('gst.slabs') as $slab)
                                    <option value="{{ $slab }}" @selected((float) $store->default_gst_rate === (float) $slab)>
                                        {{ rtrim(rtrim(number_format($slab, 2), '0'), '.') }}%
                                    </option>
                                @endforeach
                            </x-select>
                        </x-field>
                    </div>

                    <div class="space-y-2 border-t border-slate-100 pt-4">
                        @foreach ([
                            'auto_create_invoice' => 'Create an invoice automatically for every new order',
                            'sync_products' => 'Keep products in sync with Shopify',
                            'prices_include_tax' => 'Shopify prices already include GST',
                        ] as $field => $label)
                            <label class="flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="{{ $field }}" value="1" @checked($store->$field)
                                       class="rounded border-slate-300 text-indigo-600"> {{ $label }}
                            </label>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-btn variant="primary" type="submit">Save settings</x-btn>
                        <x-btn type="submit" form="sync-{{ $store->id }}">Sync products now</x-btn>
                        <x-btn variant="success" type="submit" form="sync-orders-{{ $store->id }}">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                            Sync Orders → Invoices
                        </x-btn>
                    </div>
                </form>
            </x-card>

            <x-card title="Sync activity">
                <p class="text-sm text-slate-500">
                    Last synced: {{ $store->last_synced_at?->diffForHumans() ?? 'never' }}
                </p>

                {{-- Order sync with custom date --}}
                <div class="mt-3 flex items-end gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <div class="flex-1">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Sync orders since</label>
                        <input type="date" id="order-since-{{ $store->id }}"
                               value="{{ now()->subDays(30)->format('Y-m-d') }}"
                               class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm">
                    </div>
                    <x-btn variant="success" size="sm"
                           onclick="document.querySelector('#sync-orders-{{ $store->id }} input[name=since]').value = document.getElementById('order-since-{{ $store->id }}').value + 'T00:00:00Z'; document.getElementById('sync-orders-{{ $store->id }}').submit();">
                        Sync Orders
                    </x-btn>
                </div>

                <ul class="mt-4 space-y-2 text-xs">
                    @forelse ($store->logs->take(10) as $log)
                        <li class="flex items-start justify-between gap-2 border-b border-slate-50 pb-2">
                            <div>
                                <div class="font-medium text-slate-700">
                                    @if ($log->topic === 'orders-sync')
                                        <span class="text-indigo-600">🛒</span>
                                    @elseif ($log->topic === 'orders-create' || $log->topic === 'orders-updated')
                                        <span class="text-emerald-600">📦</span>
                                    @elseif (str_contains($log->topic, 'refund') || str_contains($log->topic, 'cancel'))
                                        <span class="text-rose-600">↩️</span>
                                    @endif
                                    {{ $log->topic }}
                                </div>
                                <div class="text-slate-400">{{ $log->message ?: $log->external_id }}</div>
                            </div>
                            <x-badge tone="{{ $log->status === 'processed' ? 'emerald' : ($log->status === 'failed' ? 'rose' : ($log->status === 'skipped' ? 'amber' : 'slate')) }}">{{ $log->status }}</x-badge>
                        </li>
                    @empty
                        <li class="text-slate-400">No sync activity yet. Click "Sync Orders" to import your Shopify orders as invoices.</li>
                    @endforelse
                </ul>
            </x-card>
        </div>

        <form id="sync-{{ $store->id }}" method="POST" action="{{ route('shopify.sync', $store) }}" class="hidden">@csrf</form>
        <form id="sync-orders-{{ $store->id }}" method="POST" action="{{ route('shopify.sync-orders', $store) }}" class="hidden">
            @csrf
            <input type="hidden" name="since" value="{{ now()->subDays(30)->toIso8601String() }}">
        </form>
    @endforeach
@endif
@endsection
