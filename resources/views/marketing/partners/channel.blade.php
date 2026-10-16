@extends('layouts.marketing')

@section('content')
<section class="bg-gradient-to-b from-indigo-50 to-white py-16">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <div class="grid items-center gap-10 lg:grid-cols-2">
            <div>
                <p class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600">Channel Partner Program</p>
                <h1 class="mt-5 text-4xl font-bold leading-tight tracking-tight text-slate-900 sm:text-5xl">
                    Grow your practice with recurring income
                </h1>
                <p class="mt-5 text-lg leading-relaxed text-slate-600">
                    For CAs, tax consultants, IT resellers and business-service firms. Onboard your
                    clients onto BikriBook and earn a share of every rupee they pay us — every month,
                    not just once.
                </p>
                <div class="mt-7 grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        'Up to 30% recurring commission',
                        'Paid monthly by UPI or bank transfer',
                        'A dashboard for all your clients',
                        'Priority support and training',
                    ] as $point)
                        <p class="flex items-start gap-2 text-sm text-slate-700">
                            <span class="text-emerald-600"></span> {{ $point }}
                        </p>
                    @endforeach
                </div>
            </div>

            <div>
                <x-illustration name="partner-channel" alt="Channel partners earning recurring commission" eager class="w-full rounded-2xl" />
            </div>
        </div>

        <div id="apply" class="mx-auto mt-12 max-w-2xl">
            @include('marketing.partners._form', ['type' => 'channel'])
        </div>
    </div>
</section>

<section class="py-16">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900">How it works</h2>
        <ol class="mt-10 grid gap-6 sm:grid-cols-4">
            @foreach ([
                ['Apply', 'Tell us about your practice. We review within 2 working days.'],
                ['Get your code', 'On approval you get a referral link and a partner dashboard.'],
                ['Onboard clients', 'Sign them up through your link. Attribution lasts 60 days.'],
                ['Get paid', 'Commission accrues on every payment and is paid out monthly.'],
            ] as $i => [$t, $d])
                <li class="rounded-2xl border border-slate-200 p-6">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-indigo-600 text-sm font-semibold text-white">{{ $i + 1 }}</span>
                    <h3 class="mt-3 font-semibold text-slate-900">{{ $t }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $d }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

<section class="border-t border-slate-200 bg-slate-50 py-16">
    <div class="mx-auto max-w-4xl px-4 lg:px-6">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Partner tiers</h2>
        <div class="mt-8 grid gap-6 sm:grid-cols-3">
            @foreach ([
                ['Bronze', '1–9 clients', '20%', 'Standard support'],
                ['Silver', '10–24 clients', '25%', 'Priority support, co-branded material'],
                ['Gold', '25+ clients', '30%', 'Dedicated manager, joint webinars'],
            ] as [$tier, $clients, $rate, $perk])
                <article class="rounded-2xl border border-slate-200 bg-white p-6">
                    <h3 class="font-semibold text-slate-900">{{ $tier }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ $clients }}</p>
                    <p class="mt-3 text-3xl font-bold text-indigo-600">{{ $rate }}</p>
                    <p class="mt-1 text-xs text-slate-500">recurring commission</p>
                    <p class="mt-3 text-sm text-slate-600">{{ $perk }}</p>
                </article>
            @endforeach
        </div>
        <p class="mt-6 text-center text-sm text-slate-500">
            Looking for a one-off referral instead?
            <a href="{{ route('partners.affiliate') }}" class="font-medium text-indigo-600 hover:underline">Join the affiliate program </a>
        </p>
    </div>
</section>
@endsection
