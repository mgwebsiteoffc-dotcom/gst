@extends('layouts.app')
@section('title', $item->exists ? 'Edit '.$item->name : 'New item')

@section('content')
<form method="POST" action="{{ $item->exists ? route('items.update', $item) : route('items.store') }}"
      x-data="{ type: @js(old('type', $item->type ?? 'goods')), track: @js((bool) old('track_inventory', $item->track_inventory ?? true)) }">
    @csrf
    @if ($item->exists) @method('PUT') @endif

    <x-page :title="$item->exists ? 'Edit '.$item->name : 'New item'" :back="route('items.index')">
        <x-btn variant="secondary" :href="route('items.index')">Cancel</x-btn>
        <x-btn variant="primary" type="submit">Save item</x-btn>
    </x-page>

    <div class="grid gap-5 lg:grid-cols-2">
        <x-card title="Details">
            <div class="grid gap-4">
                <x-field label="Name" name="name" required>
                    <x-input name="name" value="{{ old('name', $item->name) }}" required autofocus/>
                </x-field>

                <x-field label="Type" name="type" required>
                    <div class="flex gap-2">
                        @foreach (['goods' => 'Goods', 'service' => 'Service'] as $v => $t)
                            <label class="flex-1 cursor-pointer rounded-lg border px-3 py-2 text-center text-sm transition"
                                   :class="type === '{{ $v }}' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-300'">
                                <input type="radio" name="type" value="{{ $v }}" x-model="type" class="sr-only"> {{ $t }}
                            </label>
                        @endforeach
                    </div>
                </x-field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="SKU / Code" name="sku"><x-input name="sku" value="{{ old('sku', $item->sku) }}"/></x-field>
                    <x-field label="Unit" name="uqc">
                        <x-select name="uqc">
                            @foreach (config('gst.uqc') as $code => $text)
                                <option value="{{ $code }}" @selected(old('uqc', $item->uqc ?? 'NOS') === $code)>{{ $code }} — {{ $text }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                </div>

                <x-field label="Description" name="description">
                    <textarea name="description" rows="2" class="block w-full rounded-lg border border-slate-300 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $item->description) }}</textarea>
                </x-field>
            </div>
        </x-card>

        <x-card title="Pricing & tax">
            <div class="grid gap-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Sale price" name="sale_price" required>
                        <x-input type="number" step="0.01" min="0" name="sale_price" value="{{ old('sale_price', $item->sale_price ?? 0) }}" required/>
                    </x-field>
                    <x-field label="Purchase price" name="purchase_price">
                        <x-input type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price', $item->purchase_price ?? 0) }}"/>
                    </x-field>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field :label="'HSN / SAC'" name="hsn_sac" hint="HSN for goods, SAC for services.">
                        <x-input name="hsn_sac" maxlength="8" class="font-mono" value="{{ old('hsn_sac', $item->hsn_sac) }}"/>
                    </x-field>
                    <x-field label="GST rate" name="gst_rate" required>
                        <x-select name="gst_rate">
                            @foreach (config('gst.slabs') as $slab)
                                <option value="{{ $slab }}" @selected((float) old('gst_rate', $item->gst_rate ?? 18) === (float) $slab)>
                                    {{ rtrim(rtrim(number_format($slab, 2), '0'), '.') }}%
                                </option>
                            @endforeach
                        </x-select>
                    </x-field>
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="price_inclusive_tax" value="1" class="rounded border-slate-300 text-indigo-600"
                           @checked(old('price_inclusive_tax', $item->price_inclusive_tax ?? false))>
                    Sale price already includes GST
                </label>
            </div>
        </x-card>

        <x-card title="Inventory" class="lg:col-span-2" x-show="type === 'goods'">
            <label class="mb-4 flex items-center gap-2 text-sm">
                <input type="checkbox" name="track_inventory" value="1" x-model="track" class="rounded border-slate-300 text-indigo-600">
                Track stock for this item
            </label>

            <div class="grid gap-4 sm:grid-cols-3" x-show="track">
                <x-field label="Opening stock" name="opening_stock">
                    <x-input type="number" step="0.001" min="0" name="opening_stock"
                             value="{{ old('opening_stock', $item->opening_stock ?? 0) }}" :disabled="$item->exists" />
                    @if ($item->exists)
                        <p class="mt-1 text-xs text-slate-500">Current stock: {{ rtrim(rtrim(number_format($item->stockOnHand(), 3), '0'), '.') }}</p>
                    @endif
                </x-field>
                <x-field label="Reorder level" name="reorder_level" hint="Flags the item as low stock.">
                    <x-input type="number" step="0.001" min="0" name="reorder_level" value="{{ old('reorder_level', $item->reorder_level ?? 0) }}"/>
                </x-field>
                <x-field label="Barcode" name="barcode"><x-input name="barcode" value="{{ old('barcode', $item->barcode) }}"/></x-field>
            </div>
        </x-card>
    </div>
</form>
@endsection
