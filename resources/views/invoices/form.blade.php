@extends('layouts.app')
@section('title', $document ? __('Edit').' '.$document->number : __('New').' '.__(\App\Models\Document::TYPES[$type] ?? 'Document'))

@php
    $isEdit = (bool) $document;
    $label = __(\App\Models\Document::TYPES[$type] ?? 'Document');
    $canChooseMode = $tenant->canIssueGst() && ($tenant->allow_both_modes ?? true);

    $reverseChargeOld = (bool) old('reverse_charge', $document?->reverse_charge);
    $isExportOld = (bool) old('is_export', $document?->is_export);
    $withTaxOld = (bool) old('with_payment_of_tax', $document?->with_payment_of_tax);

    $initialLines = old('lines', $isEdit
        ? $document->lines->map(fn ($l) => [
            'item_id' => $l->item_id, 'name' => $l->name, 'description' => $l->description,
            'hsn_sac' => $l->hsn_sac, 'uqc' => $l->uqc, 'quantity' => (float) $l->quantity,
            'rate' => (float) $l->rate, 'discount_percent' => (float) $l->discount_percent,
            'gst_rate' => (float) $l->gst_rate, 'cess_rate' => (float) $l->cess_rate,
        ])->all()
        : [['item_id' => null, 'name' => '', 'hsn_sac' => '', 'uqc' => 'NOS', 'quantity' => 1, 'rate' => 0, 'discount_percent' => 0, 'gst_rate' => 18, 'cess_rate' => 0]]
    );
@endphp

@section('content')
<form method="POST"
      action="{{ $isEdit ? route('documents.update', $document) : route('documents.store') }}"
      x-data="invoiceEditor({
          taxMode: @js(old('tax_mode', $taxMode)),
          partyId: @js(old('party_id', $document?->party_id)),
          issueDate: @js(old('issue_date', ($document?->issue_date ?? now())->format('Y-m-d'))),
          dueDate: @js(old('due_date', $document?->due_date?->format('Y-m-d'))),
          isExport: @js((bool) old('is_export', $document?->is_export)),
          sellerState: @js($tenant->state_code),
          placeOfSupply: @js(old('place_of_supply_code', $document?->place_of_supply_code ?? $tenant->state_code)),
          parties: @js($parties->keyBy('id')->map(fn($p) => ['state' => $p->resolvedStateCode(), 'gstin' => $p->gstin, 'credit_days' => $p->credit_days])),
          catalog: @js($items),
          lines: @js($initialLines),
          shipping: @js((float) old('shipping_charge', $document?->shipping_charge ?? 0)),
          roundOff: @js((bool) old('round_off_enabled', $document?->round_off_enabled ?? true)),
          isPurchase: @js(in_array($type, ['purchase_order','purchase_bill'], true)),
      })">
    @csrf
    @if ($isEdit) @method('PUT') @endif
    <input type="hidden" name="doc_type" value="{{ $type }}">
    <input type="hidden" name="tax_mode" :value="taxMode">
    <input type="hidden" name="round_off_enabled" :value="roundOff ? 1 : 0">

    <x-page :title="$isEdit ? __('Edit').' '.$document->number : __('New').' '.$label"
            :subtitle="$isEdit ? null : __('Numbering is assigned automatically when you save.')"
            :back="route('documents.index', $type)">
        <x-btn variant="secondary" :href="route('documents.index', $type)">{{ __('Cancel') }}</x-btn>
        <x-btn variant="secondary" type="submit" name="status" value="draft">{{ __('Save draft') }}</x-btn>
        <x-btn variant="primary" type="submit" name="status" value="sent">{{ __('Save and finalise') }}</x-btn>
    </x-page>

    {{-- Mode switch --}}
    @if ($canChooseMode)
        <div class="mb-5 inline-flex rounded-lg border border-slate-200 bg-white p-1 text-sm">
            @foreach (['gst' => __('GST Invoice'), 'non_gst' => __('Non-GST Invoice')] as $mode => $text)
                <button type="button" @click="taxMode = '{{ $mode }}'"
                        class="rounded-md px-3 py-1.5 transition"
                        :class="taxMode === '{{ $mode }}' ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100'">{{ $text }}</button>
            @endforeach
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        {{-- Left: party + dates --}}
        <x-card class="lg:col-span-2" :title="in_array($type, ['purchase_order','purchase_bill'], true) ? __('Vendor') : __('Client')">
            <x-slot:actions>
                <x-btn variant="ghost" size="sm" :href="route('parties.create')" target="_blank">+ {{ __('Add new') }}</x-btn>
            </x-slot:actions>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('Select contact')" name="party_id" required class="sm:col-span-2">
                    <x-select name="party_id" x-model="partyId" @change="onPartyChange()">
                        <option value="">{{ __('— Choose a contact —') }}</option>
                        @php $selectedParty = old('party_id', $document?->party_id); @endphp
                        @foreach ($parties as $p)
                            <option value="{{ $p->id }}" @selected((int) $selectedParty === (int) $p->id)>
                                {{ $p->name }}{{ $p->gstin ? ' — '.$p->gstin : '' }}
                            </option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field :label="__('Issue date')" name="issue_date" required>
                    <x-input type="date" name="issue_date" x-model="issueDate"
                             value="{{ old('issue_date', ($document?->issue_date ?? now())->format('Y-m-d')) }}" />
                </x-field>

                <x-field :label="__('Due date')" name="due_date">
                    <x-input type="date" name="due_date" x-model="dueDate"
                             value="{{ old('due_date', $document?->due_date?->format('Y-m-d')) }}" />
                </x-field>

                <template x-if="taxMode === 'gst'">
                    <div class="sm:col-span-2 grid gap-4 sm:grid-cols-2">
                        <x-field :label="__('Place of supply')" name="place_of_supply_code"
                                 :hint="__('Decides CGST+SGST vs IGST.')">
                            <x-select name="place_of_supply_code" x-model="placeOfSupply">
                                @php $selectedPos = old('place_of_supply_code', $document?->place_of_supply_code ?: $tenant->state_code); @endphp
                                @foreach ($states as $code => $name)
                                    <option value="{{ $code }}" @selected($selectedPos === $code)>{{ $code }} — {{ $name }}</option>
                                @endforeach
                            </x-select>
                        </x-field>

                        <div class="flex items-end">
                            <div class="rounded-lg border px-3 py-2 text-sm"
                                 :class="isInterstate ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-slate-200 bg-slate-50 text-slate-600'">
                                <span x-text="isInterstate ? 'Inter-state  IGST' : 'Intra-state  CGST + SGST'"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </x-card>

        {{-- Right: live summary --}}
        <x-card :title="__('Summary')" class="lg:row-span-2 h-fit lg:sticky lg:top-20">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">{{ __('Subtotal') }}</dt><dd x-text="money(subtotal)"></dd></div>
                <div class="flex justify-between" x-show="discountTotal > 0">
                    <dt class="text-slate-500">{{ __('Discount') }}</dt><dd class="text-rose-600" x-text="'−' + money(discountTotal)"></dd>
                </div>
                <template x-if="taxMode === 'gst'">
                    <div class="space-y-2">
                        <div class="flex justify-between border-t border-slate-100 pt-2">
                            <dt class="text-slate-500">{{ __('Taxable value') }}</dt><dd x-text="money(taxable)"></dd>
                        </div>
                        <template x-if="!isInterstate">
                            <div class="space-y-2">
                                <div class="flex justify-between"><dt class="text-slate-500">CGST</dt><dd x-text="money(cgst)"></dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500">SGST</dt><dd x-text="money(sgst)"></dd></div>
                            </div>
                        </template>
                        <template x-if="isInterstate">
                            <div class="flex justify-between"><dt class="text-slate-500">IGST</dt><dd x-text="money(igst)"></dd></div>
                        </template>
                        <div class="flex justify-between" x-show="cess > 0"><dt class="text-slate-500">Cess</dt><dd x-text="money(cess)"></dd></div>
                    </div>
                </template>

                <div class="flex items-center justify-between gap-2 border-t border-slate-100 pt-2">
                    <dt class="text-slate-500">{{ __('Shipping') }}</dt>
                    <dd class="w-28"><x-input type="number" step="0.01" min="0" name="shipping_charge" x-model.number="shipping" class="!py-1 text-right"/></dd>
                </div>

                <div class="flex justify-between" x-show="roundOff && Math.abs(rounding) >= 0.005">
                    <dt class="text-slate-500">{{ __('Round off') }}</dt><dd x-text="money(rounding)"></dd>
                </div>

                <div class="flex justify-between border-t border-slate-200 pt-3 text-base font-semibold">
                    <dt>{{ __('Total') }}</dt><dd x-text="money(grandTotal)"></dd>
                </div>
            </dl>

            <label class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                <input type="checkbox" x-model="roundOff" class="rounded border-slate-300 text-indigo-600"> {{ __('Round to nearest rupee') }}
            </label>

            <p class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500" x-show="grandTotal > 0">
                <span class="font-medium text-slate-600">{{ __('In words:') }}</span> <span x-text="inWords(grandTotal)"></span>
            </p>
        </x-card>

        {{-- Line items --}}
        <x-card class="lg:col-span-2 !p-0" :title="__('Items')">
            <x-slot:actions>
                <x-btn variant="ghost" size="sm" :href="route('items.create')" target="_blank">+ {{ __('New product') }}</x-btn>
            </x-slot:actions>

            <div class="lg:overflow-x-auto">
                <table class="block w-full text-sm lg:table">
                    <thead class="hidden lg:table-header-group">
                        <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="py-2 pl-5 font-medium" style="min-width:280px">{{ __('Item') }}</th>
                            <th class="px-2 font-medium" x-show="taxMode === 'gst'" style="width:96px">HSN/SAC</th>
                            <th class="px-2 text-right font-medium" style="width:80px">{{ __('Qty') }}</th>
                            <th class="px-2 text-right font-medium" style="width:120px">{{ __('Rate') }}</th>
                            <th class="px-2 text-right font-medium" style="width:84px">{{ __('Disc %') }}</th>
                            <th class="px-2 text-right font-medium" x-show="taxMode === 'gst'" style="width:96px">GST&nbsp;%</th>
                            <th class="px-2 pr-2 text-right font-medium" style="width:120px">{{ __('Amount') }}</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody class="block lg:table-row-group">
                        <template x-for="(line, i) in lines" :key="i">
                            <tr class="mx-3 mb-3 block rounded-xl border border-slate-200 bg-white p-3 align-top lg:m-0 lg:rounded-none lg:border-0 lg:border-b lg:border-slate-50 lg:p-0 lg:table-row">
                                <td class="block p-0 lg:table-cell lg:py-2 lg:pl-5 lg:pr-2">
                                    <input type="hidden" :name="`lines[${i}][item_id]`" :value="line.item_id ?? ''">
                                    <span class="mb-1 block text-xs font-medium text-slate-500 lg:hidden" x-text='(@js(__('Item')) + @js(' ') + (i + 1))'></span>
                                    <input list="catalog-list" :name="`lines[${i}][name]`" x-model="line.name"
                                           @change="pickItem(i, $event.target.value)" required placeholder="Type or pick an item"
                                           class="block w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 lg:min-w-[240px]">
                                    <input :name="`lines[${i}][description]`" x-model="line.description" placeholder="Add a description (optional)"
                                           class="mt-1 block w-full rounded-lg border-0 bg-transparent px-0 text-xs text-slate-500 placeholder:text-slate-300 focus:ring-0 lg:min-w-[240px]" />
                                </td>
                                <td class="flex items-center justify-between gap-3 py-1 lg:table-cell lg:px-2 lg:py-2" x-show="taxMode === 'gst'">
                                    <span class="text-xs text-slate-500 lg:hidden">HSN/SAC</span>
                                    <input :name="`lines[${i}][hsn_sac]`" x-model="line.hsn_sac" maxlength="8" inputmode="numeric"
                                           class="w-32 rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 lg:w-full">
                                </td>
                                <td class="flex items-center justify-between gap-3 py-1 lg:table-cell lg:px-2 lg:py-2">
                                    <span class="text-xs text-slate-500 lg:hidden">{{ __('Qty') }}</span>
                                    <input type="number" step="0.001" min="0.001" :name="`lines[${i}][quantity]`" x-model.number="line.quantity" required
                                           class="w-32 rounded-lg border-slate-300 text-right text-sm focus:border-indigo-500 focus:ring-indigo-500 lg:w-full">
                                </td>
                                <td class="flex items-center justify-between gap-3 py-1 lg:table-cell lg:px-2 lg:py-2">
                                    <span class="text-xs text-slate-500 lg:hidden">{{ __('Rate') }} (₹)</span>
                                    <input type="number" step="0.01" min="0" :name="`lines[${i}][rate]`" x-model.number="line.rate" required
                                           class="w-32 rounded-lg border-slate-300 text-right text-sm focus:border-indigo-500 focus:ring-indigo-500 lg:w-full">
                                </td>
                                <td class="flex items-center justify-between gap-3 py-1 lg:table-cell lg:px-2 lg:py-2">
                                    <span class="text-xs text-slate-500 lg:hidden">{{ __('Disc %') }}</span>
                                    <input type="number" step="0.01" min="0" max="100" :name="`lines[${i}][discount_percent]`" x-model.number="line.discount_percent"
                                           class="w-32 rounded-lg border-slate-300 text-right text-sm focus:border-indigo-500 focus:ring-indigo-500 lg:w-full">
                                </td>
                                <td class="flex items-center justify-between gap-3 py-1 lg:table-cell lg:px-2 lg:py-2" x-show="taxMode === 'gst'">
                                    <span class="text-xs text-slate-500 lg:hidden">GST %</span>
                                    <select :name="`lines[${i}][gst_rate]`" x-model.number="line.gst_rate"
                                            class="w-32 rounded-lg border-slate-300 text-right text-sm focus:border-indigo-500 focus:ring-indigo-500 lg:w-full">
                                        @foreach (config('gst.slabs') as $slab)
                                            <option value="{{ $slab }}">{{ rtrim(rtrim(number_format($slab, 2), '0'), '.') }}%</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="flex items-center justify-between gap-3 border-t border-slate-100 py-1.5 font-medium tabular-nums lg:table-cell lg:border-0 lg:px-2 lg:py-2 lg:text-right">
                                    <span class="text-xs font-normal text-slate-500 lg:hidden">{{ __('Amount') }}</span>
                                    <span x-text="money(lineTotal(line))"></span>
                                </td>
                                <td class="flex justify-end lg:table-cell lg:pr-3 lg:text-right">
                                    <button type="button" @click="removeLine(i)" x-show="lines.length > 1"
                                            class="rounded-lg px-3 py-1 text-xl leading-none text-slate-300 hover:bg-rose-50 hover:text-rose-500 lg:p-1 lg:text-base" title="{{ __('Remove') }}">× <span class="text-xs lg:hidden">{{ __('Remove') }}</span></button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <datalist id="catalog-list">
                @foreach ($items as $it)
                    <option value="{{ $it->name }}">{{ $it->sku }}</option>
                @endforeach
            </datalist>

            <div class="flex items-center gap-2 border-t border-slate-100 px-5 py-3">
                <x-btn type="button" variant="secondary" size="sm" @click="addLine()">+ {{ __('Add line') }}</x-btn>
                <span class="text-xs text-slate-400">{{ __('Press Enter in the last row to add another') }}</span>
            </div>
        </x-card>

        {{-- Notes --}}
        <x-card class="lg:col-span-2" :title="__('Notes and terms')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('Notes')" name="notes" :hint="__('Shown on the PDF under the totals.')">
                    <textarea name="notes" rows="3" placeholder="Any note for the client…" class="block w-full rounded-lg border border-slate-300 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $document?->notes ?? '') }}</textarea>
                </x-field>
                <x-field :label="__('Terms and conditions')" name="terms">
                    <textarea name="terms" rows="3" class="block w-full rounded-lg border border-slate-300 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('terms', $document?->terms ?? $tenant->setting('default_terms')) }}</textarea>
                </x-field>
            </div>

            <template x-if="taxMode === 'gst'">
                <div class="mt-4 flex flex-wrap gap-4 border-t border-slate-100 pt-4 text-sm">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="reverse_charge" value="1" class="rounded border-slate-300 text-indigo-600"
                               @checked($reverseChargeOld)> {{ __('Reverse charge') }}
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_export" value="1" x-model="isExport" class="rounded border-slate-300 text-indigo-600"
                               @checked($isExportOld)> {{ __('Export / SEZ') }}
                    </label>
                    <label class="flex items-center gap-2" x-show="isExport">
                        <input type="checkbox" name="with_payment_of_tax" value="1" class="rounded border-slate-300 text-indigo-600"
                               @checked($withTaxOld)> {{ __('With payment of tax') }}
                    </label>
                </div>
            </template>
        </x-card>
    </div>

    {{-- Sticky total + save on phones: sits above the bottom tab bar. --}}
    <div class="sticky bottom-16 z-20 -mx-4 mt-5 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur lg:hidden">
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="text-xs text-slate-500">{{ __('Total') }}</div>
                <div class="text-lg font-semibold tabular-nums" x-text="money(grandTotal)"></div>
            </div>
            <button type="submit" name="status" value="sent"
                    class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 active:scale-95">
                {{ $isEdit ? __('Save changes') : __('Save invoice') }}
            </button>
        </div>
    </div>
</form>
@endsection
