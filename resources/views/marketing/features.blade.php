@extends('layouts.marketing')

@section('content')
<section class="bg-gradient-to-b from-indigo-50 to-white py-16">
    <div class="mx-auto max-w-4xl px-4 text-center lg:px-6">
        <h1 class="text-4xl font-bold tracking-tight text-slate-900">Features built for Indian billing</h1>
        <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-600">
            Nine document types, full GST compliance, NIC e-invoicing and inventory — without the complexity of a full ERP.
        </p>
    </div>
</section>

@php
    $groups = [
        'Billing &amp; documents' => [
            ['GST Tax Invoice', 'HSN/SAC, place of supply and an automatic CGST+SGST or IGST split.'],
            ['Non-GST Invoice', 'A plain bill with no tax columns — for unregistered businesses and exempt supplies.'],
            ['Bill of Supply', 'For composition-scheme dealers. We switch the format automatically.'],
            ['Quotations &amp; Proforma', 'Send an estimate, then convert it to an invoice in one click.'],
            ['Delivery Challan', 'Move goods without a tax invoice, with full challan formatting.'],
            ['Credit &amp; Debit Notes', 'Handle returns and corrections against the original invoice.'],
        ],
        'GST compliance' => [
            ['e-Invoice (IRN)', 'Direct NIC IRP integration. Get the IRN, acknowledgement number and signed QR on the PDF.'],
            ['e-Way Bill', 'Generate from an existing IRN, with transporter ID, vehicle number and distance.'],
            ['GSTR-1', 'Portal-ready JSON with B2B, B2C, CDNR, exports and HSN summary tables.'],
            ['GSTR-3B', 'Outward supplies, ITC and net tax payable, calculated from your books.'],
            ['GSTIN validation', 'Checksum-verified GSTIN entry that fills in state and PAN for you.'],
            ['Reverse charge, SEZ &amp; export', 'LUT and with-payment-of-tax handled correctly.'],
        ],
        'Sharing &amp; getting paid' => [
            ['WhatsApp share', 'One tap sends the invoice and a link straight to your customer\'s WhatsApp.'],
            ['UPI QR on every invoice', 'Customer scans and pays. The amount and reference are pre-filled.'],
            ['Public invoice link', 'A signed link your customer can open without creating an account.'],
            ['Email &amp; PDF', 'Send by email or download a print-ready PDF in one click.'],
            ['Payments &amp; part payments', 'Record what came in and see the balance update instantly.'],
            ['Outstanding ageing', 'See exactly who is 30, 60 or 90 days late.'],
        ],
        'Reports that tell you something' => [
            ['Profit &amp; loss', 'Real margin after cost of goods and expenses — net of GST.'],
            ['Party ledger', 'A running statement of account for any customer or vendor.'],
            ['Item-wise sales', 'Which products actually earn, ranked by revenue.'],
            ['Party-wise sales', 'Your best customers, and what each still owes.'],
            ['Daily sales', 'Day-by-day totals with no gaps, ready to chart.'],
            ['Excel/CSV export', 'Every report downloads straight into Excel for your CA.'],
        ],
        'Business operations' => [
            ['Inventory tracking', 'Live stock, reorder alerts, stock ledger and valuation.'],
            ['Purchase orders &amp; bills', 'Track what you owe suppliers and claim the right input credit.'],
            ['Multi-user with roles', 'Owner, admin, accountant, operator and read-only viewer.'],
            ['Custom branding', 'Your logo, signature, accent colour, bank details and terms on every PDF.'],
            ['Shopify integration', 'Orders become GST invoices automatically; refunds become credit notes.'],
            ['Multi-business ready', 'Each business keeps its own data, numbering and team.'],
        ],
    ];
@endphp

@php
    $groupArt = [
        'Billing &amp; documents' => 'solution-gst-billing',
        'GST compliance' => 'solution-e-invoicing',
        'Sharing &amp; getting paid' => 'feature-whatsapp',
        'Reports that tell you something' => 'feature-reports',
        'Business operations' => 'feature-inventory',
    ];
@endphp

@foreach ($groups as $heading => $items)
<section class="border-t border-slate-200 py-16 {{ $loop->even ? 'bg-slate-50' : '' }}">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <div class="mb-8 grid items-center gap-8 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)]">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900">{!! $heading !!}</h2>
            @isset($groupArt[$heading])
                <x-illustration :name="$groupArt[$heading]" :alt="strip_tags($heading)"
                                class="w-full rounded-xl {{ $loop->even ? 'lg:order-first' : '' }}" />
            @endisset
        </div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($items as [$t, $d])
                <article class="rounded-xl border border-slate-200 p-5">
                    <h3 class="font-medium text-slate-900">{!! $t !!}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $d }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endforeach

@include('marketing._faq')

<section class="bg-indigo-600 py-16">
    <div class="mx-auto max-w-3xl px-4 text-center lg:px-6">
        <h2 class="text-3xl font-semibold text-white">Try every feature free for 14 days</h2>
        <a href="{{ route('register') }}" class="mt-6 inline-block rounded-lg bg-white px-6 py-3 text-sm font-medium text-indigo-700 hover:bg-indigo-50">Start now</a>
    </div>
</section>
@endsection
