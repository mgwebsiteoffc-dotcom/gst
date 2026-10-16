@extends('layouts.marketing')
@section('content')

{{-- ── Hero ─────────────────────────────────────────────── --}}
<section class="relative overflow-hidden bg-gradient-to-b from-indigo-50 via-white to-white">
    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 lg:grid-cols-2 lg:items-center lg:gap-14 lg:py-20 lg:px-6">
        <div>
            <span class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-white px-3 py-1 text-xs font-semibold text-indigo-700">
                <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                {{ __('BikriBook Distributor Programme') }}
            </span>

            <h1 class="mt-4 text-3xl font-bold leading-tight tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">
                {{ __('Sell GST billing software in your city.') }}
                <span class="text-indigo-600">{{ __('Earn on every sale.') }}</span>
            </h1>

            <p class="mt-4 max-w-xl text-lg leading-relaxed text-slate-600">
                {{ __('Join India’s growing network of BikriBook distributors. Buy at special distributor pricing, resell to shops, traders and CA firms in your area, and keep healthy margins — with our sales, marketing and training support.') }}
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="#apply" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-700">
                    {{ __('Apply to become a distributor') }}
                </a>
                <a href="https://wa.me/919999999999?text={{ rawurlencode('Hi, I want to become a BikriBook distributor') }}"
                   target="_blank" rel="noopener"
                   class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    {{ __('Talk to our partner team') }}
                </a>
            </div>

            <dl class="mt-8 grid max-w-md grid-cols-3 gap-4">
                @foreach ([
                    [__('Up to'), '35%', __('margin on every sale')],
                    [__('Recurring'), '20%', __('on renewals')],
                    [__('Payouts'), 'Monthly', __('no waiting')],
                ] as [$top, $big, $sub])
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">{{ $top }}</dt>
                        <dd class="mt-0.5 text-2xl font-bold text-slate-900">{{ $big }}</dd>
                        <dd class="text-xs text-slate-500">{{ $sub }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- Application form --}}
        <div id="apply" class="scroll-mt-24">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-900/5">
                <h2 class="text-lg font-semibold text-slate-900">{{ __('Become a distributor') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Fill this in and our partner team calls you within 2 working days.') }}</p>

                <div class="mt-5">
                    @include('marketing.partners._form', ['type' => 'distributor', 'compact' => true])
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── Why partner with us ─────────────────────────────── --}}
<section class="py-16">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900">{{ __('Why distributors choose BikriBook') }}</h2>
        <p class="mx-auto mt-3 max-w-2xl text-center text-slate-600">
            {{ __('Every shop in India needs GST billing. We give you the product, pricing and material to sell it — you bring the local relationships.') }}
        </p>

        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['💰', __('High margins'), __('Buy at distributor price and set your own selling price. Up to 35% margin plus 20% recurring commission on every renewal.')],
                ['📦', __('Zero inventory'), __('Software, not stock. No warehouse, no expiry, no dead inventory — deliver licences instantly by email or WhatsApp.')],
                ['🤝', __('Recurring income'), __('Your customers renew every year and you keep earning. A base of 100 customers becomes predictable monthly income.')],
                ['📣', __('Marketing kit'), __('Brochures, posters, WhatsApp creatives, demo videos and landing pages — branded and ready to share in your language.')],
                ['🎓', __('Free training'), __('Sales and product training for you and your team, plus a dedicated partner manager on WhatsApp.')],
                ['🏆', __('Territory support'), __('Get help targeting your city’s retail clusters, markets and CA associations — with joint campaigns and lead sharing.')],
            ] as [$icon, $title, $desc])
                <div class="rounded-2xl border border-slate-200 bg-white p-6 transition hover:border-indigo-200 hover:shadow-md">
                    <div class="grid h-11 w-11 place-items-center rounded-xl bg-indigo-50 text-xl">{{ $icon }}</div>
                    <h3 class="mt-4 font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── Who can apply ───────────────────────────────────── --}}
<section class="border-y border-slate-200 bg-slate-50 py-16">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900">{{ __('Who can become a distributor?') }}</h2>
        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['🏬', __('Software & IT dealers'), __('Already selling Tally, billing or POS software in your area.')],
                ['🧾', __('CA & tax consultants'), __('Serve 50+ businesses and want to offer billing software with your services.')],
                ['📱', __('Mobile & electronics shops'), __('Sell to the shops around you and add software to your catalogue.')],
                ['🚚', __('Distributors & stockists'), __('Existing FMCG, stationery or hardware distribution network.')],
            ] as [$icon, $title, $desc])
                <div class="rounded-2xl border border-slate-200 bg-white p-5 text-center">
                    <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-white text-2xl shadow-sm">{{ $icon }}</div>
                    <h3 class="mt-3 font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="mt-1.5 text-sm text-slate-600">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── How it works ────────────────────────────────────── --}}
<section class="py-16">
    <div class="mx-auto max-w-5xl px-4 lg:px-6">
        <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900">{{ __('How it works') }}</h2>

        <ol class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                [__('Apply'), __('Send this form. We verify your details and activate your distributor account.')],
                [__('Get pricing'), __('Receive your distributor price list, demo account and marketing kit.')],
                [__('Sell locally'), __('Demo to shops and CA firms in your city. We help with online demos and trials.')],
                [__('Earn monthly'), __('Track every sale and renewal in your partner dashboard. Payouts every month.')],
            ] as $i => [$title, $desc])
                <li class="relative rounded-2xl border border-slate-200 bg-white p-6">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-indigo-600 text-sm font-bold text-white">{{ $i + 1 }}</span>
                    <h3 class="mt-3 font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ── FAQ + CTA ───────────────────────────────────────── --}}
<section class="border-t border-slate-200 bg-slate-50 py-16">
    <div class="mx-auto max-w-3xl px-4 lg:px-6">
        <h2 class="text-center text-2xl font-bold tracking-tight text-slate-900">{{ __('Distributor FAQs') }}</h2>

        <div class="mt-8 space-y-3">
            @foreach ([
                [__('Is there any joining fee?'), __('No. Becoming a BikriBook distributor is free. You buy licences at distributor pricing when you have a customer.')],
                [__('Do I need to keep stock?'), __('No stock at all. Software licences are delivered digitally to your customer’s email or phone within minutes of purchase.')],
                [__('How much can I earn?'), __('Up to 35% margin on each sale plus 20% recurring commission on renewals. A distributor selling 20 licences a month typically earns ₹40,000–₹1,20,000 per month.')],
                [__('Will you sell directly in my city?'), __('We work with one or two distributors per district so you don’t compete with us. Online orders from your area are routed to you.')],
                [__('How do I get paid?'), __('Payouts are processed monthly to your bank account or UPI, once the hold period on the sale is over. Everything is visible in your partner dashboard.')],
            ] as [$q, $a])
                <details class="group rounded-xl border border-slate-200 bg-white p-4">
                    <summary class="flex cursor-pointer items-center justify-between gap-3 text-sm font-medium text-slate-900">
                        {{ $q }}
                        <span class="text-indigo-600 transition group-open:rotate-45">+</span>
                    </summary>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $a }}</p>
                </details>
            @endforeach
        </div>

        <div class="mt-10 rounded-2xl bg-slate-900 p-8 text-center">
            <h3 class="text-xl font-semibold text-white">{{ __('Ready to start earning in your city?') }}</h3>
            <p class="mx-auto mt-2 max-w-lg text-sm text-slate-300">
                {{ __('Send your application today — our partner team will call you within 2 working days.') }}
            </p>
            <a href="#apply" class="mt-5 inline-block rounded-xl bg-indigo-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-indigo-400">
                {{ __('Apply now') }}
            </a>
        </div>
    </div>
</section>

@endsection
