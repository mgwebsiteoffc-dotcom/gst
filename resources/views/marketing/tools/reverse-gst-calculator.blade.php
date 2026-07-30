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
        <x-illustration name="solution-gst-billing" alt="Working backwards from a tax-inclusive price" eager
                        class="w-full rounded-xl" />
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200 p-6"
         x-data="{ total: 11800, rate: 18,
             get taxable() { return +this.total / (1 + this.rate / 100); },
             get tax() { return +this.total - this.taxable; },
             money(v) { return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(v || 0); } }">

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Total amount paid (₹)</label>
                <input type="number" x-model.number="total" min="0" step="0.01"
                      >
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">GST rate applied</label>
                <select x-model.number="rate">
                    @foreach ([0.25, 3, 5, 12, 18, 28, 40] as $slab)
                        <option value="{{ $slab }}" @selected($slab === 18)>{{ rtrim(rtrim(number_format($slab, 2), '0'), '.') }}%</option>
                    @endforeach
                </select>
            </div>
        </div>

        <dl class="mt-6 space-y-2 border-t border-slate-100 pt-5 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Taxable value (before GST)</dt><dd class="tabular-nums font-medium" x-text="money(taxable)"></dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">GST included</dt><dd class="tabular-nums" x-text="money(tax)"></dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">CGST + SGST (if intra-state)</dt><dd class="tabular-nums text-slate-500"><span x-text="money(tax / 2)"></span> each</dd></div>
            <div class="flex justify-between border-t border-slate-200 pt-3 text-base font-semibold"><dt>Total paid</dt><dd class="tabular-nums" x-text="money(total)"></dd></div>
        </dl>
    </div>

    <div class="prose prose-slate mt-10 max-w-none">
        <h2>When do you need reverse GST?</h2>
        <p>
            Retail prices in India are usually quoted inclusive of GST. To record the sale correctly
            you need the taxable value — the amount before tax — because that is what goes in your
            books and your GSTR-1.
        </p>
        <p>The formula is: <code>taxable value = total ÷ (1 + rate ÷ 100)</code>.</p>
    </div>

    <div class="mt-8 rounded-xl bg-indigo-600 p-6 text-center">
        <p class="text-lg font-semibold text-white">BikriBook does this automatically</p>
        <a href="{{ route('register') }}" class="mt-4 inline-block rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-indigo-700">Start free</a>
    </div>
</section>
@endsection
