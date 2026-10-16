<!DOCTYPE html>
<html lang="en-IN" class="h-full scroll-smooth bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- ── SEO ─────────────────────────────────────────────── --}}
    <title>{{ $seo->title() }}</title>
    <meta name="description" content="{{ $seo->get('description') }}">
    <link rel="canonical" href="{{ $seo->get('canonical') }}">
    @if ($seo->get('noindex'))
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    @endif
    <meta name="author" content="{{ config('app.name') }}">
    <meta name="language" content="en-IN">
    <meta name="geo.region" content="IN">

    {{-- ── Open Graph / Twitter ────────────────────────────── --}}
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:type" content="{{ $seo->get('type') }}">
    <meta property="og:title" content="{{ $seo->title() }}">
    <meta property="og:description" content="{{ $seo->get('description') }}">
    <meta property="og:url" content="{{ $seo->get('canonical') }}">
    <meta property="og:image" content="{{ $seo->get('image') }}">
    <meta property="og:locale" content="en_IN">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo->title() }}">
    <meta name="twitter:description" content="{{ $seo->get('description') }}">
    <meta name="twitter:image" content="{{ $seo->get('image') }}">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="alternate" type="application/rss+xml" title="{{ config('app.name') }} Blog" href="{{ route('blog.index') }}">

    {{-- ── AEO: structured data for answer engines ─────────── --}}
    @foreach ($seo->schemas() as $schema)
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endforeach

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @include('partials.gtm-head')
    @include('partials.pwa-head')
</head>
<body class="flex min-h-full flex-col text-slate-800 antialiased">
    @include('partials.gtm-body')

<header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
    {{-- Thin announcement strip (Vyapar-style promo bar). --}}
    <div class="bg-slate-900 px-4 py-1.5 text-center text-xs text-slate-200">
        <span class="font-medium text-white">{{ __('Limited launch offer:') }}</span>
        {{ __('flat 50% off on yearly plans —') }}
        <a href="{{ route('pricing') }}" class="font-semibold text-indigo-300 underline hover:text-indigo-200">{{ __('see pricing') }}</a>
    </div>

    <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 lg:px-6" aria-label="Main">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2" aria-label="{{ config('app.name') }} home">
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-indigo-600 text-sm font-bold text-white">B</span>
            <span class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
        </a>

        {{-- Desktop navigation --}}
        <div class="hidden items-center gap-1 lg:flex">
            <div x-data="{ p: false }" class="relative" @mouseleave="p = false">
                <button @mouseenter="p = true" @click="p = !p"
                        class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                    {{ __('Product') }}
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div x-show="p" x-cloak x-transition.origin.top
                     class="absolute left-0 z-40 mt-1 w-[26rem] rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                    @foreach ([
                        ['gst-billing', 'GST Billing & Invoicing', 'Invoices that follow every rule'],
                        ['e-invoicing', 'e-Invoice (IRN)', 'Report to NIC in one click'],
                        ['e-way-bill', 'e-Way Bills', 'Generated from the IRN'],
                        ['inventory', 'Inventory & Stock', 'Know what is on the shelf'],
                        ['shopify', 'Shopify Sync', 'Orders billed automatically'],
                    ] as [$slug, $label, $desc])
                        <a href="{{ route('solution', $slug) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">
                            <span class="block text-sm font-medium text-slate-900">{{ $label }}</span>
                            <span class="block text-xs text-slate-500">{{ $desc }}</span>
                        </a>
                    @endforeach
                    <a href="{{ route('features') }}" class="mt-1 block rounded-lg bg-slate-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">
                        {{ __('See all features') }} →
                    </a>
                </div>
            </div>

            <a href="{{ route('pricing') }}" class="rounded-lg px-3 py-2 text-sm {{ request()->routeIs('pricing') ? 'font-medium text-indigo-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">{{ __('Pricing') }}</a>

            <div x-data="{ p: false }" class="relative" @mouseleave="p = false">
                <button @mouseenter="p = true" @click="p = !p"
                        class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                    {{ __('Partners') }}
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div x-show="p" x-cloak x-transition.origin.top
                     class="absolute left-0 z-40 mt-1 w-72 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                    @foreach ([
                        ['partners.distributor', 'Become a Distributor', 'Sell BikriBook in your city'],
                        ['partners.channel', 'Channel Partner', 'Resell to your client base'],
                        ['partners.affiliate', 'Influencer / Affiliate', 'Earn from your audience'],
                    ] as [$route, $label, $desc])
                        <a href="{{ route($route) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">
                            <span class="block text-sm font-medium text-slate-900">{{ __($label) }}</span>
                            <span class="block text-xs text-slate-500">{{ __($desc) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div x-data="{ p: false }" class="relative" @mouseleave="p = false">
                <button @mouseenter="p = true" @click="p = !p"
                        class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                    {{ __('Resources') }}
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div x-show="p" x-cloak x-transition.origin.top
                     class="absolute left-0 z-40 mt-1 w-64 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                    @foreach ([
                        ['blog.index', 'Blog', 'GST guides in plain English'],
                        ['tools', 'Free Tools', 'Calculators and HSN finder'],
                        ['templates', 'Invoice Formats', 'Downloadable templates'],
                        ['integrations', 'Integrations', 'Shopify, WhatsApp and more'],
                        ['about', 'About us', 'Who builds BikriBook'],
                    ] as [$route, $label, $desc])
                        <a href="{{ route($route) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">
                            <span class="block text-sm font-medium text-slate-900">{{ __($label) }}</span>
                            <span class="block text-xs text-slate-500">{{ __($desc) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('contact') }}" class="rounded-lg px-3 py-2 text-sm {{ request()->routeIs('contact') ? 'font-medium text-indigo-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">{{ __('Contact') }}</a>
        </div>

        <div class="flex items-center gap-2">
            @auth
                <a href="{{ auth()->user()->is_super_admin ? route('admin.dashboard') : route('dashboard') }}"
                   class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ __('Dashboard') }}</a>
            @else
                <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">{{ __('Login') }}</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    {{ __('Start free trial') }}
                </a>
            @endauth

            <button @click="open = !open" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </nav>

    {{-- Mobile menu --}}
    <div x-show="open" x-cloak class="border-t border-slate-200 bg-white lg:hidden">
        <div class="max-h-[70vh] overflow-y-auto px-2 py-2">
            @foreach ([
                ['Product', [['features', 'All features'], ['solution:gst-billing', 'GST Billing'], ['solution:e-invoicing', 'e-Invoice (IRN)'], ['solution:e-way-bill', 'e-Way Bill'], ['solution:inventory', 'Inventory'], ['solution:shopify', 'Shopify Sync']]],
                ['Partners', [['partners.distributor', 'Become a Distributor'], ['partners.channel', 'Channel Partner'], ['partners.affiliate', 'Influencer / Affiliate']]],
                ['Resources', [['blog.index', 'Blog'], ['tools', 'Free Tools'], ['templates', 'Invoice Formats'], ['integrations', 'Integrations'], ['about', 'About us']]],
            ] as [$group, $links])
                <p class="px-3 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ __($group) }}</p>
                @foreach ($links as [$target, $label])
                    @php $href = str_starts_with($target, 'solution:') ? route('solution', substr($target, 9)) : route($target); @endphp
                    <a href="{{ $href }}" class="block rounded-lg px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-50">{{ __($label) }}</a>
                @endforeach
            @endforeach

            <div class="mt-2 border-t border-slate-100 pt-2">
                <a href="{{ route('pricing') }}" class="block rounded-lg px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-50">{{ __('Pricing') }}</a>
                <a href="{{ route('contact') }}" class="block rounded-lg px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-50">{{ __('Contact') }}</a>
                <a href="{{ route('download') }}" class="block rounded-lg px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-50">{{ __('Install the app') }}</a>
            </div>
        </div>
    </div>
</header>

<main class="flex-1">
    @if (session('success'))
        <div class="mx-auto mt-4 max-w-6xl px-4">
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        </div>
    @endif
    @yield('content')
</main>

{{-- App download + newsletter band --}}
<section class="bg-slate-900">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 lg:grid-cols-2 lg:items-center lg:px-6">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">{{ __('Run your business from your phone') }}</h2>
            <p class="mt-3 max-w-lg text-sm leading-relaxed text-slate-300">
                {{ __('Install the BikriBook app on your phone — bill even without internet, send invoices on WhatsApp and get payment reminders. No app store needed.') }}
            </p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ route('download') }}" class="rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">
                    {{ __('Install the app') }}
                </a>
                <a href="{{ route('register') }}" class="rounded-xl border border-slate-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                    {{ __('Start 14-day free trial') }}
                </a>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            @foreach ([
                ['⚡', __('30-second billing'), __('Save a GST bill before the customer leaves the counter.')],
                ['📶', __('Works offline'), __('No signal? Bills queue on the phone and sync later.')],
                ['🔔', __('Payment reminders'), __('Get notified about overdue bills and send a WhatsApp nudge.')],
            ] as [$icon, $title, $desc])
                <div class="rounded-2xl border border-slate-700 bg-slate-800/60 p-4">
                    <div class="text-xl">{{ $icon }}</div>
                    <h3 class="mt-2 text-sm font-semibold text-white">{{ $title }}</h3>
                    <p class="mt-1 text-xs leading-relaxed text-slate-400">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<footer class="border-t border-slate-200 bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 py-14 lg:px-8">
        <div class="grid gap-10 sm:grid-cols-3 lg:grid-cols-6">

            {{-- Brand + socials --}}
            <div class="sm:col-span-3 lg:col-span-1">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-indigo-600 text-base font-bold text-white">B</span>
                    <span class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
                </a>

                <p class="mt-4 max-w-xs text-sm leading-relaxed text-slate-600">
                    GST billing, e-invoicing and inventory software built for Indian small businesses.
                    Bikri ho, bill turant ho.
                </p>

                <div class="mt-5 flex gap-2">
                    @foreach ([
                        ['Instagram', 'https://instagram.com/bikribook', 'M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm5 5.5A4.5 4.5 0 1 0 16.5 12 4.5 4.5 0 0 0 12 7.5Zm0 2A2.5 2.5 0 1 1 9.5 12 2.5 2.5 0 0 1 12 9.5ZM17.8 6a1.2 1.2 0 1 0 1.2 1.2A1.2 1.2 0 0 0 17.8 6Z'],
                        ['YouTube', 'https://youtube.com/@bikribook', 'M23 12s0-3.4-.4-5a2.5 2.5 0 0 0-1.8-1.8C19.1 4.8 12 4.8 12 4.8s-7.1 0-8.8.4A2.5 2.5 0 0 0 1.4 7C1 8.6 1 12 1 12s0 3.4.4 5a2.5 2.5 0 0 0 1.8 1.8c1.7.4 8.8.4 8.8.4s7.1 0 8.8-.4A2.5 2.5 0 0 0 22.6 17c.4-1.6.4-5 .4-5ZM9.8 15.3V8.7l6 3.3Z'],
                        ['LinkedIn', 'https://linkedin.com/company/bikribook', 'M4.98 3.5A2.5 2.5 0 1 1 2.5 6 2.5 2.5 0 0 1 5 3.5ZM3 8.98h4V21H3ZM9.5 8.98h3.8v1.64h.06a4.17 4.17 0 0 1 3.75-2.06c4 0 4.74 2.64 4.74 6.07V21h-4v-5.5c0-1.31 0-3-1.83-3s-2.11 1.43-2.11 2.9V21h-4Z'],
                        ['X', 'https://x.com/bikribook', 'M18.9 2H22l-6.9 7.9L23.2 22h-6.4l-5-6.6L6 22H2.9l7.4-8.5L1.2 2h6.6l4.5 6 5.3-6Zm-1.1 18h1.7L7.3 3.8H5.5Z'],
                        ['Facebook', 'https://facebook.com/bikribook', 'M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.4h-1.2c-1.2 0-1.6.8-1.6 1.6V12h2.7l-.4 2.9h-2.3v7A10 10 0 0 0 22 12Z'],
                    ] as [$name, $href, $path])
                        <a href="{{ $href }}" target="_blank" rel="noopener" aria-label="{{ config('app.name') }} on {{ $name }}"
                           class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-indigo-300 hover:text-indigo-600">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px"><path d="{{ $path }}"/></svg>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Link columns --}}
            @php
                $columns = [
                    'Quick Links' => [
                        ['home', [], 'Home'],
                        ['features', [], 'Features'],
                        ['pricing', [], 'Pricing'],
                        ['templates', [], 'Invoice Formats'],
                        ['tools', [], 'Free Tools'],
                    ],
                    'Features' => [
                        ['solution', ['e-invoicing'], 'e-Invoicing'],
                        ['solution', ['e-way-bill'], 'e-Way Bills'],
                        ['solution', ['gst-billing'], 'GST Billing'],
                        ['solution', ['inventory'], 'Inventory'],
                        ['solution', ['shopify'], 'BikriBook x Shopify'],
                        ['integrations', [], 'Integrations'],
                    ],
                    'Register' => [
                        ['register', [], 'Get Started'],
                        ['login', [], 'Login'],
                        ['contact', [], 'Contact Us'],
                        ['download', [], 'Download App'],
                    ],
                    'Explore' => [
                        ['blog.index', [], 'Blog'],
                        ['solution', ['accountants'], 'For Accountants'],
                        ['solution', ['startups'], 'For Startups'],
                        ['solution', ['api'], 'Developers'],
                        ['partners.distributor', [], 'Become a Distributor'],
                        ['partners.channel', [], 'Channel Partners'],
                        ['partners.affiliate', [], 'Affiliate Program'],
                        ['simple', ['product-updates'], 'Product Updates'],
                        ['simple', ['careers'], "We're hiring"],
                    ],
                    'Legal' => [
                        ['legal', ['privacy-policy'], 'Privacy Policy'],
                        ['legal', ['terms-of-service'], 'Terms of Service'],
                        ['legal', ['refund-policy'], 'Refund Policy'],
                        ['legal', ['shipping-policy'], 'Service Delivery'],
                        ['about', [], 'About Us'],
                    ],
                ];
            @endphp

            @foreach ($columns as $heading => $links)
                <nav aria-label="{{ $heading }}">
                    <h2 class="text-sm font-semibold text-slate-900">{{ $heading }}</h2>
                    <ul class="mt-3 space-y-2.5 text-sm">
                        @foreach ($links as [$route, $params, $label])
                            <li>
                                <a href="{{ route($route, $params) }}" class="text-slate-600 transition hover:text-indigo-700">{{ $label }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endforeach
        </div>
    </div>

    <div class="border-t border-slate-200">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-5 text-xs text-slate-500 lg:px-6">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p class="flex items-center gap-1.5">
                <span class="text-base leading-none">🇮🇳</span> Made in India, for Indian businesses
            </p>
        </div>
    </div>
</footer>
</body>
</html>
