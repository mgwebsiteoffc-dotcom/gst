@extends('layouts.marketing')

@section('content')
{{-- Hero --}}
<section class="relative overflow-hidden bg-white">
    <div class="pointer-events-none absolute inset-x-0 -top-40 h-96 bg-gradient-to-b from-indigo-100/70 to-transparent blur-3xl"></div>

    <div class="relative mx-auto max-w-6xl px-4 pb-16 pt-16 lg:px-6 lg:pb-24 lg:pt-24">
        <div class="grid items-center gap-10 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 shadow-sm">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    100% GST compliant · e-Invoice ready
                </p>

                <h1 class="mt-5 text-4xl font-bold leading-[1.08] tracking-tight text-slate-900 sm:text-5xl">
                    GST invoices in<br><span class="text-indigo-600">30 seconds</span> ⚡
                </h1>

                <p class="mt-5 max-w-lg text-lg leading-relaxed text-slate-600">
                    Bikri ho, bill turant ho. Make the bill, share it on WhatsApp, and collect
                    payment by UPI — all from one screen.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">
                        Start free for 14 days
                    </a>
                    <a href="{{ route('features') }}" class="rounded-xl border border-slate-300 bg-white px-6 py-3.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        See all features
                    </a>
                </div>

                <p class="mt-5 text-xs text-slate-500">
                    <span class="font-medium text-emerald-600">✓</span> No credit card needed
                    <span class="px-2 text-slate-300">·</span>
                    <span class="font-medium text-emerald-600">✓</span> English &amp; Hindi
                    <span class="px-2 text-slate-300">·</span>
                    <span class="font-medium text-emerald-600">✓</span> Works on any phone
                </p>

                {{-- Social proof --}}
                <div class="mt-8 flex items-center gap-3">
                    <div class="flex -space-x-2">
                        @foreach (['R','S','A','M','P'] as $i => $ltr)
                            <span class="grid h-9 w-9 place-items-center rounded-full border-2 border-white text-xs font-semibold text-white
                                         {{ ['bg-indigo-500','bg-emerald-500','bg-amber-500','bg-rose-500','bg-slate-500'][$i] }}">{{ $ltr }}</span>
                        @endforeach
                    </div>
                    <div>
                        <div class="flex gap-0.5 text-amber-400" aria-label="5 out of 5 stars">
                            @for ($i = 0; $i < 5; $i++)
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1Z"/></svg>
                            @endfor
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500">Built for India's 6 crore+ small businesses</p>
                    </div>
                </div>
            </div>

            {{-- Hero illustration --}}
            <div class="relative">
                <picture>
                    <source srcset="{{ asset('images/hero-invoice.webp') }}" type="image/webp">
                    <img src="{{ asset('images/hero-invoice.png') }}"
                         alt="Creating a GST e-invoice in BikriBook on a laptop and sharing it from a phone"
                         width="1200" height="655" fetchpriority="high" decoding="async"
                         class="w-full max-w-xl lg:max-w-none">
                </picture>

                <div class="mt-2 flex flex-wrap justify-center gap-2">
                    @foreach (['Shared on WhatsApp', 'UPI ₹34,909 received'] as $chip)
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm">
                            <span class="text-emerald-600">✓</span> {{ $chip }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Direct-answer block for AEO --}}
<section class="border-y border-slate-200 bg-slate-50 py-12">
    <div class="mx-auto max-w-3xl px-4 text-center lg:px-6">
        <h2 class="text-lg font-semibold text-slate-900">What is {{ config('app.name') }}?</h2>
        <p class="mt-2 leading-relaxed text-slate-600">
            {{ config('app.name') }} is a cloud-based GST billing and inventory platform for Indian MSMEs,
            shopkeepers, traders and freelancers. It creates GST-compliant tax invoices and plain non-GST
            bills, generates e-invoices (IRN) and e-way bills through the NIC portal, tracks stock and
            payments, produces GSTR-1 and GSTR-3B reports for filing, and automatically bills Shopify
            orders. Plans start at ₹499 per month with a 14-day free trial.
        </p>
    </div>
</section>

{{-- Three big capability blocks, alternating --}}
@php
    $blocks = [
        ['Billing so fast it feels unfair', 'Pick a customer, pick an item, done. Rates, HSN codes and the CGST/SGST split fill themselves in. The software knows the rules so you do not have to.', ['Auto CGST/SGST or IGST', 'HSN & rate autofill', 'Nine document types', 'Quotation → invoice in one click']],
        ['Share anywhere. Get paid faster.', 'Send the bill on WhatsApp the second it is made. Attach a UPI QR so your customer can pay from the same message.', ['One-tap WhatsApp share', 'UPI QR on every invoice', 'Public link, no login needed', 'Outstanding tracked per customer']],
        ['Compliance that happens by itself', 'Bill normally through the month and your returns are simply ready. Generate the IRN and e-way bill without leaving the invoice.', ['e-Invoice (IRN) via NIC', 'e-Way Bill from the IRN', 'GSTR-1 & GSTR-3B ready', 'Profit & loss, ledgers, ageing']],
    ];
@endphp

@foreach ($blocks as $i => [$title, $desc, $points])
<section class="py-16 {{ $i % 2 ? 'bg-slate-50' : '' }}">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <div class="grid items-center gap-10 lg:grid-cols-2">
            <div class="{{ $i % 2 ? 'lg:order-2' : '' }}">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ $title }}</h2>
                <p class="mt-4 text-lg leading-relaxed text-slate-600">{{ $desc }}</p>
                <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                    @foreach ($points as $p)
                        <li class="flex items-start gap-2 text-sm text-slate-700">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.5" stroke-linecap="round" class="mt-0.5 shrink-0 text-indigo-600"
                                 style="width:16px;height:16px"><path d="m5 13 4 4L19 7"/></svg>
                            {{ $p }}
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}" class="mt-7 inline-block rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">Try it free →</a>
            </div>

            <div class="{{ $i % 2 ? 'lg:order-1' : '' }} rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @if ($i === 0)
                    <div class="space-y-2.5">
                        @foreach ([['Sharma Enterprises', '09AAACS1234G1ZB', 'CGST + SGST'], ['Mumbai Retail LLP', '27AABCM1234H1Z1', 'IGST']] as [$n, $g, $tax])
                            <div class="flex items-center justify-between rounded-xl border border-slate-200 p-3">
                                <div>
                                    <div class="text-sm font-medium text-slate-900">{{ $n }}</div>
                                    <div class="font-mono text-[11px] text-slate-400">{{ $g }}</div>
                                </div>
                                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-medium text-indigo-700">{{ $tax }}</span>
                            </div>
                        @endforeach
                        <div class="rounded-xl bg-slate-900 p-3 text-center text-sm font-medium text-white">Invoice ready in 30 seconds</div>
                    </div>
                @elseif ($i === 1)
                    <div class="space-y-3 text-center">
                        <div class="mx-auto grid h-28 w-28 place-items-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 text-xs text-slate-400">UPI QR</div>
                        <div class="text-sm font-medium text-slate-900">Pay ₹34,909</div>
                        <div class="flex justify-center gap-2">
                            <span class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white">WhatsApp</span>
                            <span class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs">Email</span>
                            <span class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs">Copy link</span>
                        </div>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach ([['IRN generated', 'emerald'], ['e-Way Bill · valid 3 days', 'emerald'], ['GSTR-1 ready to file', 'indigo'], ['GSTR-3B ₹5,126 payable', 'indigo']] as [$label, $tone])
                            <div class="flex items-center gap-2.5 rounded-xl border border-slate-200 p-3 text-sm">
                                <span class="grid h-6 w-6 place-items-center rounded-full bg-{{ $tone }}-100 text-{{ $tone }}-700">✓</span>
                                <span class="text-slate-700">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endforeach

{{-- Feature grid --}}
<section class="border-t border-slate-200 py-20">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Everything a growing business needs</h2>
            <p class="mt-3 text-slate-600">One tool instead of a spreadsheet, a WhatsApp folder and a shoebox of receipts.</p>
        </div>

        <div class="mt-12 grid gap-px overflow-hidden rounded-2xl border border-slate-200 bg-slate-200 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['GST & non-GST invoices', 'Tax invoice or plain bill, switched per document with separate numbering.'],
                ['e-Invoice (IRN)', 'Straight to the NIC portal. IRN and signed QR printed on the PDF.'],
                ['e-Way Bill', 'Generated from the IRN with transporter and vehicle details.'],
                ['Inventory & stock', 'Live levels, low-stock alerts, purchase orders and purchase bills.'],
                ['Profit & loss', 'Real margins after cost of goods and expenses — not just turnover.'],
                ['Party ledger', 'Running statement per customer, plus receivables ageing.'],
                ['Item & party reports', 'See what sells and who pays, exportable to Excel.'],
                ['Shopify sync', 'Orders become invoices; refunds become credit notes.'],
                ['Team with roles', 'Owner, admin, accountant, counter staff and read-only CA access.'],
            ] as [$title, $desc])
                <article class="bg-white p-6 transition hover:bg-slate-50">
                    <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- Who it's for --}}
<section class="bg-slate-50 py-16">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <h2 class="text-center text-2xl font-bold tracking-tight text-slate-900">Built for every kind of Indian business</h2>
        <div class="mt-8 flex flex-wrap justify-center gap-2.5">
            @foreach (['Kirana & retail shops', 'Wholesalers', 'Distributors', 'Traders', 'Manufacturers', 'Freelancers', 'Consultants', 'Service providers', 'Shopify sellers', 'Chartered accountants'] as $who)
                <span class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700">{{ $who }}</span>
            @endforeach
        </div>
    </div>
</section>

{{-- Blog --}}
@if ($posts->count())
<section class="py-20">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <div class="flex items-end justify-between">
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">GST guides, in plain English</h2>
            <a href="{{ route('blog.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">All articles →</a>
        </div>
        <div class="mt-8 grid gap-6 md:grid-cols-3">
            @foreach ($posts as $post)
                @include('marketing.blog._card', ['post' => $post])
            @endforeach
        </div>
    </div>
</section>
@endif

@include('marketing._faq')

{{-- CTA --}}
<section class="bg-slate-900 py-20">
    <div class="mx-auto max-w-3xl px-4 text-center lg:px-6">
        <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Stop writing bills by hand</h2>
        <p class="mt-4 text-lg text-slate-300">Free for 14 days. No card, no training, no setup fee.</p>
        <a href="{{ route('register') }}" class="mt-8 inline-block rounded-xl bg-white px-7 py-3.5 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">
            Create your free account
        </a>
    </div>
</section>
@endsection
