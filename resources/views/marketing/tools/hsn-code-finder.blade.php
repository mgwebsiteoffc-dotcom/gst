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
        <x-illustration name="page-tools" alt="Searching HSN and SAC codes" eager
                        class="w-full rounded-xl" />
    </div>

    @php
        $codes = [
            ['8471', 'Computers, laptops and data processing units', 18],
            ['84716060', 'Keyboards, mice and other input units', 18],
            ['6109', 'T-shirts, singlets and vests, knitted', 5],
            ['6203', "Men's suits, jackets and trousers", 5],
            ['1006', 'Rice', 0],
            ['0401', 'Milk and cream, not concentrated', 0],
            ['3004', 'Medicaments, packaged for retail sale', 12],
            ['8517', 'Telephones and smartphones', 18],
            ['9403', 'Furniture and parts', 18],
            ['2106', 'Food preparations not elsewhere specified', 18],
            ['7113', 'Jewellery of precious metal', 3],
            ['2402', 'Cigars and cigarettes', 28],
            ['998313', 'IT consulting and support services (SAC)', 18],
            ['998314', 'IT design and development services (SAC)', 18],
            ['996511', 'Road transport of goods (SAC)', 5],
            ['997212', 'Rental of non-residential property (SAC)', 18],
            ['998365', 'Advertising and marketing services (SAC)', 18],
            ['999293', 'Commercial training and coaching (SAC)', 18],
        ];
    @endphp

    <div class="mt-8" x-data="{ q: '', codes: @js($codes),
        get results() {
            if (!this.q.trim()) return this.codes;
            const t = this.q.toLowerCase();
            return this.codes.filter(c => c[0].includes(t) || c[1].toLowerCase().includes(t));
        } }">

        <input x-model="q" placeholder="Search by product name or code — try 'rice' or '8471'"
              >

        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr><th class="px-4 py-2.5 font-medium">Code</th><th class="px-3 font-medium">Description</th><th class="px-4 text-right font-medium">GST</th></tr>
                </thead>
                <tbody>
                    <template x-for="c in results" :key="c[0]">
                        <tr class="border-b border-slate-50 last:border-0">
                            <td class="px-4 py-2.5 font-mono text-xs" x-text="c[0]"></td>
                            <td class="px-3 text-slate-700" x-text="c[1]"></td>
                            <td class="px-4 text-right tabular-nums" x-text="c[2] + '%'"></td>
                        </tr>
                    </template>
                    <tr x-show="!results.length"><td colspan="3" class="px-4 py-8 text-center text-slate-400">No match found.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="prose prose-slate mt-10 max-w-none">
        <h2>HSN or SAC?</h2>
        <p>
            <strong>HSN</strong> codes classify goods; <strong>SAC</strong> codes classify services.
            Both go in the same column on a GST invoice.
        </p>
        <p>
            How many digits you must quote depends on turnover: 4 digits up to ₹5 crore, and 6 digits
            above that. B2C invoices from smaller businesses may omit it.
        </p>
        <p class="text-sm text-slate-500">
            This is a quick-reference list of common codes, not the complete schedule. Confirm against
            the CBIC tariff before filing.
        </p>
    </div>
</section>
@endsection
