@extends('layouts.app')
@section('title', $schedule ? 'Edit recurring invoice' : 'New recurring invoice')

@php
    $isEdit = (bool) $schedule;
    $canChooseMode = $tenant->canIssueGst() && ($tenant->allow_both_modes ?? true);
    $taxMode = old('tax_mode', $schedule?->tax_mode ?? ($from?->tax_mode ?? $tenant->defaultTaxMode()));

    $initialLines = old('lines', $template['lines'] ?? [[
        'item_id' => null, 'name' => '', 'description' => '', 'hsn_sac' => '', 'uqc' => 'NOS',
        'quantity' => 1, 'rate' => 0, 'discount_percent' => 0, 'gst_rate' => 18, 'cess_rate' => 0,
    ]]);

    $startsOn = old('starts_on', ($schedule?->starts_on ?? now())->format('Y-m-d'));
@endphp

@section('content')
<form method="POST"
      action="{{ $isEdit ? route('recurring.update', $schedule) : route('recurring.store') }}"
      x-data="invoiceEditor({
          taxMode: @js($taxMode),
          partyId: @js(old('party_id', $schedule?->party_id ?? $from?->party_id)),
          sellerState: @js($tenant->state_code),
          placeOfSupply: @js(old('place_of_supply_code', $template['place_of_supply_code'] ?? $tenant->state_code)),
          isExport: @js((bool) old('is_export', $template['is_export'] ?? false)),
          parties: @js($parties->keyBy('id')->map(fn($p) => ['state' => $p->resolvedStateCode(), 'gstin' => $p->gstin, 'credit_days' => $p->credit_days])),
          catalog: @js($items),
          lines: @js($initialLines),
          shipping: @js((float) old('shipping_charge', $template['shipping_charge'] ?? 0)),
      })">
    @csrf
    @if ($isEdit) @method('PUT') @endif
    <input type="hidden" name="tax_mode" :value="taxMode">

    <x-page :title="$isEdit ? 'Edit recurring invoice' : 'New recurring invoice'"
            :subtitle="$from ? 'Pre-filled from '.$from->number.' — every run reuses these lines.' : 'Automatically generates an invoice on the schedule you set.'"
            :back="route('recurring.index')">
        <x-btn variant="secondary" :href="$isEdit ? route('recurring.show', $schedule) : route('recurring.index')">Cancel</x-btn>
        <x-btn variant="primary" type="submit">{{ $isEdit ? 'Save changes' : 'Create schedule' }}</x-btn>
    </x-page>

    {{-- Mode switch --}}
    @if ($canChooseMode)
        <div class="mb-5 inline-flex rounded-lg border border-slate-200 bg-white p-1 text-sm">
            @foreach (['gst' => 'GST Invoice', 'non_gst' => 'Non-GST Invoice'] as $mode => $text)
                <button type="button" @click="taxMode = '{{ $mode }}'"
                        class="rounded-md px-3 py-1.5 transition"
                        :class="taxMode === '{{ $mode }}' ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100'">{{ $text }}</button>
            @endforeach
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        {{-- Client --}}
        <x-card class="lg:col-span-2" title="Client">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Customer" name="party_id" required class="sm:col-span-2">
                    <x-select name="party_id" x-model="partyId" @change="onPartyChange()">
                        <option value="">— Choose a customer —</option>
                        @php $selectedParty = old('party_id', $schedule?->party_id ?? $from?->party_id); @endphp
                        @foreach ($parties as $p)
                            <option value="{{ $p->id }}" @selected((int) $selectedParty === (int) $p->id)>
                                {{ $p->name }}{{ $p->gstin ? ' — '.$p->gstin : '' }}
                            </option>
                        @endforeach
                    </x-select>
                </x-field>

                @if ($tenant->isComposition())
                    <input type="hidden" name="doc_type" value="bill_of_supply">
                @else
                    <x-field label="Document to generate" name="doc_type" required>
                        <x-select name="doc_type">
                            @php $selectedType = old('doc_type', $schedule?->doc_type ?? $from?->doc_type ?? 'invoice'); @endphp
                            @foreach (\App\Models\RecurringInvoice::DOC_TYPES as $v => $label)
                                <option value="{{ $v }}" @selected($selectedType === $v)>{{ $label }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                @endif

                <template x-if="taxMode === 'gst'">
                    <x-field label="Place of supply" name="place_of_supply_code" hint="Decides CGST+SGST vs IGST.">
                        <x-select name="place_of_supply_code" x-model="placeOfSupply">
                            @php $selectedPos = old('place_of_supply_code', $template['place_of_supply_code'] ?? $tenant->state_code); @endphp
                            @foreach ($states as $code => $name)
                                <option value="{{ $code }}" @selected($selectedPos === $code)>{{ $code }} — {{ $name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                </template>
            </div>
        </x-card>

        {{-- Live estimate --}}
        <x-card title="Estimated invoice value" class="lg:row-span-2 h-fit lg:sticky lg:top-20">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd x-text="money(subtotal)"></dd></div>
                <div class="flex justify-between" x-show="discountTotal > 0">
                    <dt class="text-slate-500">Discount</dt><dd class="text-rose-600" x-text="'−' + money(discountTotal)"></dd>
                </div>
                <template x-if="taxMode === 'gst'">
                    <div class="space-y-2">
                        <div class="flex justify-between border-t border-slate-100 pt-2">
                            <dt class="text-slate-500">Taxable value</dt><dd x-text="money(taxable)"></dd>
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
                    </div>
                </template>

                <div class="flex items-center justify-between gap-2 border-t border-slate-100 pt-2">
                    <dt class="text-slate-500">Shipping</dt>
                    <dd class="w-28"><x-input type="number" step="0.01" min="0" name="shipping_charge" x-model.number="shipping" class="!py-1 text-right"/></dd>
                </div>

                <div class="flex justify-between" x-show="Math.abs(rounding) >= 0.005">
                    <dt class="text-slate-500">Round off</dt><dd x-text="money(rounding)"></dd>
                </div>

                <div class="flex justify-between border-t border-slate-200 pt-3 text-base font-semibold">
                    <dt>Per invoice</dt><dd x-text="money(grandTotal)"></dd>
                </div>
            </dl>
            <p class="mt-3 text-xs text-slate-400">Totals are recalculated with current tax rates every time an invoice is generated.</p>
        </x-card>

        {{-- Schedule --}}
        <x-card class="lg:col-span-2" title="Schedule">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field label="Repeat every" name="interval_count" required>
                    <x-input type="number" min="1" max="52" name="interval_count"
                             value="{{ old('interval_count', $schedule?->interval_count ?? 1) }}" />
                </x-field>

                <x-field label="Frequency" name="frequency" required>
                    <x-select name="frequency">
                        @php $selectedFreq = old('frequency', $schedule?->frequency ?? 'monthly'); @endphp
                        @foreach (\App\Models\RecurringInvoice::FREQUENCIES as $v => $label)
                            <option value="{{ $v }}" @selected($selectedFreq === $v)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="First invoice on" name="starts_on" required hint="This is when the first invoice is generated.">
                    <x-input type="date" name="starts_on" value="{{ $startsOn }}" />
                </x-field>

                <x-field label="Stop after (date)" name="ends_on" hint="Optional. Leave blank to run forever.">
                    <x-input type="date" name="ends_on" value="{{ old('ends_on', $schedule?->ends_on?->format('Y-m-d')) }}" />
                </x-field>

                <x-field label="Stop after (invoices)" name="max_occurrences" hint="Optional maximum count.">
                    <x-input type="number" min="1" max="1000" name="max_occurrences"
                             value="{{ old('max_occurrences', $schedule?->max_occurrences) }}" placeholder="e.g. 12" />
                </x-field>

                <x-field label="Payment terms (days)" name="due_days" hint="Overrides the customer's credit days.">
                    <x-input type="number" min="0" max="365" name="due_days"
                             value="{{ old('due_days', $schedule?->due_days) }}" placeholder="e.g. 15" />
                </x-field>
            </div>

            <label class="mt-4 flex items-start gap-3 text-sm">
                <input type="checkbox" name="auto_finalize" value="1" class="mt-0.5 rounded border-slate-300 text-indigo-600"
                       @checked((bool) old('auto_finalize', $schedule?->auto_finalize ?? true))>
                <span>
                    <span class="font-medium text-slate-700">Finalise invoices automatically</span><br>
                    <span class="text-xs text-slate-500">Generated invoices are issued immediately. Untick to create them as drafts you review and send yourself.</span>
                </span>
            </label>
        </x-card>

        {{-- Line items --}}
        <x-card class="lg:col-span-3 !p-0" title="Invoice lines">
            <x-slot:actions>
                <x-btn variant="ghost" size="sm" :href="route('items.create')" target="_blank">+ New product</x-btn>
            </x-slot:actions>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="py-2 pl-5 font-medium" style="min-width:280px">Item</th>
                            <th class="px-2 font-medium" x-show="taxMode === 'gst'" style="width:96px">HSN/SAC</th>
                            <th class="px-2 text-right font-medium" style="width:80px">Qty</th>
                            <th class="px-2 text-right font-medium" style="width:120px">Rate</th>
                            <th class="px-2 text-right font-medium" style="width:84px">Disc&nbsp;%</th>
                            <th class="px-2 text-right font-medium" x-show="taxMode === 'gst'" style="width:96px">GST&nbsp;%</th>
                            <th class="px-2 pr-2 text-right font-medium" style="width:120px">Amount</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, i) in lines" :key="i">
                            <tr class="border-b border-slate-50 align-top">
                                <td class="py-2 pl-5 pr-2">
                                    <input type="hidden" :name="`lines[${i}][item_id]`" :value="line.item_id ?? ''">
                                    <input type="hidden" :name="`lines[${i}][cess_rate]`" :value="line.cess_rate ?? 0">
                                    <input list="catalog-list" :name="`lines[${i}][name]`" x-model="line.name"
                                           @change="pickItem(i, $event.target.value)" required placeholder="Type or pick an item"
                                           class="block w-full min-w-[240px] rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <input :name="`lines[${i}][description]`" x-model="line.description" placeholder="Add a description (optional)"
                                           class="mt-1 block w-full min-w-[240px] rounded-lg border-0 bg-transparent px-0 text-xs text-slate-500 placeholder:text-slate-300 focus:ring-0" />
                                </td>
                                <td class="px-2 py-2" x-show="taxMode === 'gst'">
                                    <input :name="`lines[${i}][hsn_sac]`" x-model="line.hsn_sac" maxlength="8"
                                           class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="hidden" :name="`lines[${i}][uqc]`" :value="line.uqc ?? 'NOS'">
                                    <input type="number" step="0.001" min="0.001" :name="`lines[${i}][quantity]`" x-model.number="line.quantity" required
                                           class="w-full rounded-lg border-slate-300 text-right text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="number" step="0.01" min="0" :name="`lines[${i}][rate]`" x-model.number="line.rate" required
                                           class="w-full rounded-lg border-slate-300 text-right text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="number" step="0.01" min="0" max="100" :name="`lines[${i}][discount_percent]`" x-model.number="line.discount_percent"
                                           class="w-full rounded-lg border-slate-300 text-right text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </td>
                                <td class="px-2 py-2" x-show="taxMode === 'gst'">
                                    <select :name="`lines[${i}][gst_rate]`" x-model.number="line.gst_rate"
                                            class="w-full rounded-lg border-slate-300 text-right text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach (config('gst.slabs') as $slab)
                                            <option value="{{ $slab }}">{{ rtrim(rtrim(number_format($slab, 2), '0'), '.') }}%</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-2 py-2 text-right font-medium tabular-nums" x-text="money(lineTotal(line))"></td>
                                <td class="pr-3 text-right">
                                    <button type="button" @click="removeLine(i)" x-show="lines.length > 1"
                                            class="rounded p-1 text-slate-300 hover:bg-rose-50 hover:text-rose-500" title="Remove">&times;</button>
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
                <x-btn type="button" variant="secondary" size="sm" @click="addLine()">+ Add line</x-btn>
                <span class="text-xs text-slate-400">Press Enter in the last row to add another</span>
            </div>
        </x-card>

        {{-- Notes + GST flags --}}
        <x-card class="lg:col-span-3" title="Notes, terms and tax options">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Notes" name="notes" hint="Shown on the PDF under the totals.">
                    <textarea name="notes" rows="3" class="block w-full rounded-lg border border-slate-300 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $template['notes'] ?? '') }}</textarea>
                </x-field>
                <x-field label="Terms and conditions" name="terms">
                    <textarea name="terms" rows="3" class="block w-full rounded-lg border border-slate-300 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('terms', $template['terms'] ?? $tenant->setting('default_terms')) }}</textarea>
                </x-field>
            </div>

            <template x-if="taxMode === 'gst'">
                <div class="mt-4 flex flex-wrap gap-4 border-t border-slate-100 pt-4 text-sm">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="reverse_charge" value="1" class="rounded border-slate-300 text-indigo-600"
                               @checked((bool) old('reverse_charge', $template['reverse_charge'] ?? false))>
                        Reverse charge
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_export" value="1" class="rounded border-slate-300 text-indigo-600"
                               @checked((bool) old('is_export', $template['is_export'] ?? false))>
                        Export / SEZ supply
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="with_payment_of_tax" value="1" class="rounded border-slate-300 text-indigo-600"
                               @checked((bool) old('with_payment_of_tax', $template['with_payment_of_tax'] ?? false))>
                        With payment of tax (not under LUT)
                    </label>
                </div>
            </template>
        </x-card>

        <div class="lg:col-span-3 flex justify-end gap-2">
            <x-btn variant="secondary" :href="$isEdit ? route('recurring.show', $schedule) : route('recurring.index')">Cancel</x-btn>
            <x-btn variant="primary" type="submit">{{ $isEdit ? 'Save changes' : 'Create schedule' }}</x-btn>
        </div>
    </div>
</form>
@endsection
