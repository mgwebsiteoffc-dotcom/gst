@extends('layouts.marketing')

@section('content')
<section class="mx-auto max-w-3xl px-4 py-14 lg:px-6">
    <nav aria-label="Breadcrumb" class="mb-4 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a> ›
        <a href="{{ route('tools') }}" class="hover:text-indigo-600">Tools</a> › {{ $name }}
    </nav>

    <div class="grid items-center gap-6 sm:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $name }}</h1>
            <p class="mt-3 text-slate-600">{{ $desc }}</p>
        </div>
        <x-illustration name="solution-gst-billing" alt="Calculating CGST, SGST and IGST on an invoice" eager
                        class="w-full rounded-xl" />
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200 p-6"
         x-data="{ amount: 10000, rate: 18, mode: 'exclusive', interstate: false,
             get taxable() { return this.mode === 'exclusive' ? +this.amount : +this.amount / (1 + this.rate / 100); },
             get tax() { return this.taxable * this.rate / 100; },
             get total() { return this.taxable + this.tax; },
             money(v) { return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(v || 0); } }">

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Amount (₹)</label>
                <input type="number" x-model.number="amount" min="0" step="0.01"
                      >
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">GST rate</label>
                <select x-model.number="rate">
                    @foreach ([0, 0.25, 3, 5, 12, 18, 28, 40] as $slab)
                        <option value="{{ $slab }}">{{ rtrim(rtrim(number_format($slab, 2), '0'), '.') }}%</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-4 text-sm">
            <label class="flex items-center gap-2">
                <input type="radio" value="exclusive" x-model="mode" class="text-indigo-600"> Amount excludes GST
            </label>
            <label class="flex items-center gap-2">
                <input type="radio" value="inclusive" x-model="mode" class="text-indigo-600"> Amount includes GST
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" x-model="interstate" class="rounded border-slate-300 text-indigo-600"> Inter-state sale
            </label>
        </div>

        <dl class="mt-6 space-y-2 border-t border-slate-100 pt-5 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Taxable value</dt><dd class="tabular-nums" x-text="money(taxable)"></dd></div>
            <template x-if="!interstate">
                <div>
                    <div class="flex justify-between"><dt class="text-slate-500">CGST</dt><dd class="tabular-nums" x-text="money(tax / 2)"></dd></div>
                    <div class="mt-2 flex justify-between"><dt class="text-slate-500">SGST</dt><dd class="tabular-nums" x-text="money(tax / 2)"></dd></div>
                </div>
            </template>
            <template x-if="interstate">
                <div class="flex justify-between"><dt class="text-slate-500">IGST</dt><dd class="tabular-nums" x-text="money(tax)"></dd></div>
            </template>
            <div class="flex justify-between border-t border-slate-200 pt-3 text-base font-semibold">
                <dt>Total</dt><dd class="tabular-nums" x-text="money(total)"></dd>
            </div>
        </dl>
    </div>

    <div class="prose prose-slate mt-10 max-w-none">
        <h2>How GST is calculated</h2>
        <p>
            GST is charged on the <strong>taxable value</strong> of a supply. When your price already
            includes tax, divide by <code>1 + rate/100</code> to get back to the taxable value.
        </p>
        <p>
            The split depends on where the goods go. Sell within your own state and the tax is halved
            into <strong>CGST</strong> and <strong>SGST</strong>. Sell to another state and the whole
            amount is charged as <strong>IGST</strong>.
        </p>
    </div>

    <div class="mt-8 rounded-xl bg-indigo-600 p-6 text-center">
        <p class="text-lg font-semibold text-white">Tired of calculating by hand?</p>
        <a href="{{ route('register') }}" class="mt-4 inline-block rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-indigo-700">Make GST invoices free</a>
    </div>
</section>
@endsection
