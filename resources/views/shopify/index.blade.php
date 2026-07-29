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
                    </div>
                </form>
            </x-card>

            <x-card title="Sync activity">
                <p class="text-sm text-slate-500">
                    Last synced: {{ $store->last_synced_at?->diffForHumans() ?? 'never' }}
                </p>
                <ul class="mt-4 space-y-2 text-xs">
                    @forelse ($store->logs->take(8) as $log)
                        <li class="flex items-start justify-between gap-2 border-b border-slate-50 pb-2">
                            <div>
                                <div class="font-medium text-slate-700">{{ $log->topic }}</div>
                                <div class="text-slate-400">{{ $log->message ?: $log->external_id }}</div>
                            </div>
                            <x-badge tone="{{ $log->status === 'processed' ? 'emerald' : ($log->status === 'failed' ? 'rose' : 'slate') }}">{{ $log->status }}</x-badge>
                        </li>
                    @empty
                        <li class="text-slate-400">No webhook activity yet.</li>
                    @endforelse
                </ul>
            </x-card>
        </div>

        <form id="sync-{{ $store->id }}" method="POST" action="{{ route('shopify.sync', $store) }}" class="hidden">@csrf</form>
    @endforeach
@endif
@endsection
