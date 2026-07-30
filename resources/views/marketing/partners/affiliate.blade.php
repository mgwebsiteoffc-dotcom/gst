@extends('layouts.marketing')

@section('content')
<section class="bg-gradient-to-b from-amber-50 to-white py-16">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <div class="grid items-center gap-10 lg:grid-cols-2">
            <div>
                <p class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600">Affiliate &amp; Influencer Program</p>
                <h1 class="mt-5 text-4xl font-bold leading-tight tracking-tight text-slate-900 sm:text-5xl">
                    Get paid to share something useful
                </h1>
                <p class="mt-5 text-lg leading-relaxed text-slate-600">
                    If you make content for Indian business owners — GST explainers, shopkeeper tips,
                    finance reels — you already have the audience. Earn recurring commission on every
                    paying signup you send.
                </p>
                <div class="mt-7 grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        'Free to join, no minimum audience',
                        '20% recurring for 12 months',
                        '60-day cookie window',
                        'Monthly payouts by UPI',
                    ] as $point)
                        <p class="flex items-start gap-2 text-sm text-slate-700">
                            <span class="text-emerald-600">✓</span> {{ $point }}
                        </p>
                    @endforeach
                </div>
            </div>

            <div>
                <x-illustration name="partner-affiliate" alt="Creators earning commission by referring BikriBook" eager class="w-full rounded-2xl" />
            </div>
        </div>

        <div id="apply" class="mx-auto mt-12 max-w-2xl">
            @include('marketing.partners._form', ['type' => 'affiliate'])
        </div>
    </div>
</section>

<section class="py-16">
    <div class="mx-auto max-w-5xl px-4 lg:px-6">
        <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900">What you could earn</h2>
        <p class="mt-3 text-center text-slate-600">Assuming the ₹999/month Growth plan and 20% recurring commission.</p>

        <div class="mt-10 overflow-hidden rounded-2xl border border-slate-200">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr><th class="px-5 py-3 font-medium">Paying signups / month</th>
                        <th class="px-3 text-right font-medium">Month 1</th>
                        <th class="px-3 text-right font-medium">Month 6</th>
                        <th class="px-5 text-right font-medium">Month 12</th></tr>
                </thead>
                <tbody>
                @foreach ([5, 10, 25, 50] as $signups)
                    @php $perUser = 999 * 0.20; @endphp
                    <tr class="border-b border-slate-50 last:border-0">
                        <td class="px-5 py-3 font-medium">{{ $signups }}</td>
                        <td class="px-3 text-right tabular-nums">₹{{ number_format($signups * $perUser) }}</td>
                        <td class="px-3 text-right tabular-nums">₹{{ number_format($signups * 6 * $perUser) }}</td>
                        <td class="px-5 text-right font-semibold tabular-nums text-emerald-600">₹{{ number_format($signups * 12 * $perUser) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-4 text-center text-xs text-slate-500">
            Illustrative only. Actual earnings depend on how many referred businesses keep paying.
        </p>
    </div>
</section>

<section class="border-t border-slate-200 bg-slate-50 py-16">
    <div class="mx-auto max-w-3xl px-4 lg:px-6">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Program rules</h2>
        <dl class="mt-6 divide-y divide-slate-200">
            @foreach ([
                'When do I get paid?' => 'Commissions clear after a 30-day hold, then are paid out monthly once your balance passes ₹1,000.',
                'How long does attribution last?' => 'A 60-day cookie. First touch wins, so if someone clicks your link first you keep the credit.',
                'What counts as a conversion?' => 'A referred business paying for a plan. Free trials alone do not earn commission.',
                'What is not allowed?' => 'Bidding on our brand name in ads, coupon or cashback sites, spam, and self-referrals.',
                'Can I join if I am also a customer?' => 'Yes, but you cannot claim commission on your own account.',
            ] as $q => $a)
                <div class="py-4">
                    <dt class="font-medium text-slate-900">{{ $q }}</dt>
                    <dd class="mt-1 text-sm text-slate-600">{{ $a }}</dd>
                </div>
            @endforeach
        </dl>
        <p class="mt-6 text-sm text-slate-500">
            Run an agency or CA practice instead?
            <a href="{{ route('partners.channel') }}" class="font-medium text-indigo-600 hover:underline">See the channel partner program →</a>
        </p>
    </div>
</section>
@endsection
