@extends('layouts.marketing')

@section('content')
<section class="mx-auto max-w-3xl px-4 py-16 lg:px-6">
    <h1 class="text-4xl font-bold tracking-tight text-slate-900">{{ $heading }}</h1>
    <p class="mt-4 text-lg text-slate-600">{{ $lede }}</p>

    <div class="mt-10 space-y-8">
        @foreach ([
            ['Reports & getting paid', now()->format('F Y'), [
                'Profit & loss, party ledger, item-wise and party-wise sales reports',
                'Outstanding ageing buckets: current, 1-30, 31-60, 61-90 and 90+ days',
                'WhatsApp share and a UPI QR on every invoice',
                'Signed public invoice links your customer can open without an account',
                'CSV export on every report',
            ]],
            ['Blog & marketing site', now()->subMonth()->format('F Y'), [
                'Public site with features, pricing, blog and legal pages',
                'Blog CMS with a rich text editor and per-post FAQ schema',
                'Sitemap, robots.txt and structured data for answer engines',
            ]],
            ['Team, roles and settings', now()->subMonths(2)->format('F Y'), [
                'Owner, admin, accountant, operator and viewer roles',
                'Settings consolidated into one page with five tabs',
                'Logo and signature upload, printed on every PDF',
            ]],
        ] as [$title, $when, $items])
            <article class="border-l-2 border-indigo-200 pl-5">
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ $when }}</p>
                <h2 class="mt-1 text-lg font-semibold text-slate-900">{{ $title }}</h2>
                <ul class="mt-2 space-y-1.5 text-sm text-slate-600">
                    @foreach ($items as $item)
                        <li class="flex gap-2"><span class="text-emerald-600"></span> {{ $item }}</li>
                    @endforeach
                </ul>
            </article>
        @endforeach
    </div>
</section>
@endsection
