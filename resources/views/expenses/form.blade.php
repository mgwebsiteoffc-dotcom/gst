@extends('layouts.app')
@section('title', $expense ? 'Edit expense' : 'Add expense')

@section('content')
    @php $isEdit = (bool) $expense; @endphp

    <x-page :title="$isEdit ? 'Edit expense' : 'Add expense'"
            :back="route('expenses.index')">
    </x-page>

    <form method="POST" action="{{ $isEdit ? route('expenses.update', $expense) : route('expenses.store') }}"
          class="max-w-3xl" x-data="{ itc: {{ (bool) old('itc_eligible', $expense?->itc_eligible) ? 'true' : 'false' }} }">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-card>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Date" name="spent_on" required>
                    <x-input type="date" name="spent_on"
                             value="{{ old('spent_on', ($expense?->spent_on ?? now())->format('Y-m-d')) }}" />
                </x-field>

                <x-field label="Category" name="category" required>
                    <x-select name="category">
                        @foreach ($categories as $v => $label)
                            <option value="{{ $v }}" @selected(old('category', $expense?->category ?? 'misc') === $v)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Amount (₹)" name="amount" required hint="Total amount paid, including any GST.">
                    <x-input type="number" step="0.01" min="0.01" name="amount"
                             value="{{ old('amount', $expense?->amount) }}" placeholder="0.00" />
                </x-field>

                <x-field label="Vendor" name="party_id">
                    <x-select name="party_id">
                        <option value="">— None —</option>
                        @foreach ($vendors as $v)
                            <option value="{{ $v->id }}" @selected((int) old('party_id', $expense?->party_id) === (int) $v->id)>{{ $v->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Reference" name="reference" hint="Receipt / voucher / bill number.">
                    <x-input name="reference" value="{{ old('reference', $expense?->reference) }}" />
                </x-field>

                <x-field label="Note" name="note">
                    <x-input name="note" value="{{ old('note', $expense?->note) }}" placeholder="What was this for?" />
                </x-field>
            </div>

            <div class="mt-5 border-t border-slate-100 pt-4">
                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="itc_eligible" value="1" x-model="itc"
                           class="mt-0.5 rounded border-slate-300 text-indigo-600">
                    <span>
                        <span class="font-medium text-slate-700">GST paid — claim as ITC</span><br>
                        <span class="text-xs text-slate-500">Tick when you paid GST on this expense and hold a tax invoice. It counts towards ITC in GSTR-3B.</span>
                    </span>
                </label>

                <div class="mt-3 max-w-xs" x-show="itc" x-cloak>
                    <x-field label="GST amount included (₹)" name="gst_amount">
                        <x-input type="number" step="0.01" min="0" name="gst_amount"
                                 value="{{ old('gst_amount', $expense?->gst_amount ?? 0) }}" />
                    </x-field>
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4">
                <x-btn variant="secondary" :href="route('expenses.index')">Cancel</x-btn>
                <x-btn variant="primary" type="submit">{{ $isEdit ? 'Save changes' : 'Record expense' }}</x-btn>
            </div>
        </x-card>
    </form>
@endsection
