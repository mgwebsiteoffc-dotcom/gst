@extends('layouts.marketing')

@section('content')
<section class="bg-gradient-to-b from-indigo-50 to-white py-16">
    <div class="mx-auto max-w-4xl px-4 text-center lg:px-6">
        <h1 class="text-4xl font-bold tracking-tight text-slate-900">Simple pricing, no surprises</h1>
        <p class="mt-4 text-lg text-slate-600">Every plan includes GST invoicing and a 14-day free trial. Cancel anytime.</p>
    </div>
</section>

<section class="pb-20">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <div class="grid gap-6 md:grid-cols-3">
            @foreach ($plans as $plan)
                @php $featured = $plan->slug === 'growth'; @endphp
                <article class="relative rounded-2xl border p-6 {{ $featured ? 'border-indigo-600 shadow-lg ring-1 ring-indigo-600' : 'border-slate-200' }}">
                    @if ($featured)
                        <span class="absolute -top-3 left-6 rounded-full bg-indigo-600 px-3 py-1 text-xs font-medium text-white">Most popular</span>
                    @endif

                    <h2 class="text-lg font-semibold text-slate-900">{{ $plan->name }}</h2>
                    <p class="mt-3">
                        <span class="text-3xl font-bold text-slate-900">₹{{ number_format($plan->price_paise / 100) }}</span>
                        <span class="text-sm text-slate-500">/month</span>
                    </p>
                    @if ($plan->yearly_price_paise)
                        <p class="mt-1 text-xs text-emerald-600">or ₹{{ number_format($plan->yearly_price_paise / 100) }}/year — save 2 months</p>
                    @endif

                    <ul class="mt-6 space-y-2.5 text-sm">
                        @foreach ([
                            $plan->invoice_limit ? number_format($plan->invoice_limit).' invoices per year' : 'Unlimited invoices',
                            $plan->user_limit.' '.\Illuminate\Support\Str::plural('user', $plan->user_limit),
                            'GST &amp; non-GST invoicing',
                            'Inventory &amp; payment tracking',
                            'GSTR-1 &amp; GSTR-3B reports',
                            $plan->einvoice ? 'e-Invoice (IRN) &amp; e-Way Bill' : null,
                            $plan->shopify ? 'Shopify integration' : null,
                        ] as $feature)
                            @if ($feature)
                                <li class="flex items-start gap-2 text-slate-600">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="mt-0.5 shrink-0 text-emerald-500" style="width:16px;height:16px"><path d="m5 13 4 4L19 7"/></svg>
                                    <span>{!! $feature !!}</span>
                                </li>
                            @endif
                        @endforeach
                    </ul>

                    <a href="{{ route('register') }}"
                       class="mt-6 block rounded-lg px-4 py-2.5 text-center text-sm font-medium {{ $featured ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
                        Start free trial
                    </a>
                </article>
            @endforeach
        </div>

        <p class="mt-8 text-center text-sm text-slate-500">All prices exclude GST. Pay by UPI, card or net banking.</p>

        <div class="mt-14 grid items-center gap-10 rounded-2xl border border-slate-200 bg-slate-50 p-8 lg:grid-cols-2">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">Every plan includes the essentials</h2>
                <p class="mt-3 text-slate-600">
                    GST and non-GST invoicing, unlimited customers and items, inventory, payment tracking
                    and GSTR-ready reports. You only pay more for scale and compliance add-ons.
                </p>
                <ul class="mt-5 grid gap-2 text-sm text-slate-700 sm:grid-cols-2">
                    @foreach (['Nine document types', 'WhatsApp & UPI sharing', 'Profit & loss reports',
                               'Party ledgers', 'CSV export', 'Custom branding'] as $inc)
                        <li class="flex items-start gap-2"><span class="text-emerald-600">✓</span> {{ $inc }}</li>
                    @endforeach
                </ul>
            </div>
            <x-illustration name="page-templates" alt="GST invoice templates included in every plan"
                            class="w-full rounded-xl" />
        </div>
    </div>
</section>

@include('marketing._faq')
@endsection
