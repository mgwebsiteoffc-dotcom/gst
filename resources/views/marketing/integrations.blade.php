@extends('layouts.marketing')

@section('content')
<section class="bg-gradient-to-b from-indigo-50 to-white py-14">
    <div class="mx-auto max-w-4xl px-4 text-center lg:px-6">
        <h1 class="text-4xl font-bold tracking-tight text-slate-900">Integrations</h1>
        <p class="mt-4 text-lg text-slate-600">Connect BikriBook to the tools you already use.</p>
    </div>
</section>

<section class="py-14">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['Shopify', 'live', 'Orders become GST invoices automatically; refunds become credit notes.', 'solution', 'shopify'],
                ['NIC e-Invoice Portal', 'live', 'Generate the IRN and signed QR directly from any invoice.', 'solution', 'e-invoicing'],
                ['NIC e-Way Bill', 'live', 'Create an e-way bill from an existing IRN in seconds.', 'solution', 'e-way-bill'],
                ['REST API', 'live', 'Build your own workflows with token-authenticated endpoints.', 'solution', 'api'],
                ['UPI payments', 'live', 'A UPI QR on every invoice, with amount and reference pre-filled.', null, null],
                ['WhatsApp', 'live', 'Share any invoice to WhatsApp with the message pre-written.', null, null],
                ['Tally export', 'soon', 'Export vouchers in Tally XML for your accountant.', null, null],
                ['WooCommerce', 'soon', 'Same order-to-invoice flow as Shopify.', null, null],
                ['Razorpay & PhonePe', 'soon', 'Collect card and netbanking payments from the invoice link.', null, null],
            ] as [$name, $status, $desc, $route, $param])
                <article class="rounded-2xl border border-slate-200 p-6">
                    <div class="flex items-start justify-between gap-2">
                        <h2 class="font-semibold text-slate-900">{{ $name }}</h2>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $status === 'live' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $status === 'live' ? 'Available' : 'Coming soon' }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>
                    @if ($route)
                        <a href="{{ route($route, $param) }}" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:underline">Learn more →</a>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
@endsection
