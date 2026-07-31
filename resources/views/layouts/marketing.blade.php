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
</head>
<body class="flex min-h-full flex-col text-slate-800 antialiased">

<header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
    <nav class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 lg:px-6" aria-label="Main">
        <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="{{ config('app.name') }} home">
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-indigo-600 text-sm font-bold text-white">B</span>
            <span class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
        </a>

        <div class="hidden items-center gap-7 md:flex">
            <div x-data="{ p: false }" class="relative" @mouseleave="p = false">
                <button @mouseenter="p = true" @click="p = !p"
                        class="flex items-center gap-1 text-sm text-slate-600 hover:text-slate-900">
                    Product
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" style="width:14px;height:14px"><path d="m19 9-7 7-7-7"/></svg>
                </button>

                <div x-show="p" x-cloak x-transition
                     class="absolute left-0 z-40 mt-1 w-72 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                    @foreach ([
                        ['gst-billing', 'GST Billing', 'Invoices that follow the rules'],
                        ['e-invoicing', 'e-Invoicing (IRN)', 'Report to NIC in one click'],
                        ['e-way-bill', 'e-Way Bills', 'Generated from the IRN'],
                        ['inventory', 'Inventory', 'Stock that counts itself'],
                        ['shopify', 'Shopify', 'Orders billed automatically'],
                    ] as [$slug, $label, $desc])
                        <a href="{{ route('solution', $slug) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">
                            <span class="block text-sm font-medium text-slate-900">{{ $label }}</span>
                            <span class="block text-xs text-slate-500">{{ $desc }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            @foreach (['features' => 'Features', 'pricing' => 'Pricing', 'blog.index' => 'Blog', 'contact' => 'Contact'] as $r => $label)
                <a href="{{ route($r) }}" class="text-sm {{ request()->routeIs($r) ? 'font-medium text-indigo-700' : 'text-slate-600 hover:text-slate-900' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 sm:block">Sign in</a>
            <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Start free</a>
            <button @click="open = !open" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 md:hidden" aria-label="Menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </nav>

    <div x-show="open" x-cloak class="border-t border-slate-200 bg-white md:hidden">
        @foreach (['features' => 'Features', 'pricing' => 'Pricing', 'tools' => 'Free Tools', 'blog.index' => 'Blog', 'about' => 'About', 'contact' => 'Contact'] as $r => $label)
            <a href="{{ route($r) }}" class="block border-b border-slate-100 px-5 py-3 text-sm text-slate-700">{{ $label }}</a>
        @endforeach
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
                   
                    'Explore' => [
                        ['blog.index', [], 'Blog'],
                        ['solution', ['accountants'], 'For Accountants'],
                        ['solution', ['startups'], 'For Startups'],
                        ['solution', ['api'], 'Developers'],
                        ['partners.channel', [], 'Channel Partners'],
                        ['partners.affiliate', [], 'Affiliate Program'],
                        ['simple', ['product-updates'], 'Product Updates'],
                        ['simple', ['careers'], "We're hiring"],
                    ],
                     'Free Tools' => [
                        ['tool', ['gst-calculator'], 'GST Calculator'],
                        ['tool', ['gstin-validator'], 'GSTIN Validator'],
                        ['tool', ['hsn-code-finder'], 'HSN Code Finder'],
                        ['tool', ['gstin-search'], 'GSTIN Search'],
                        ['tool', ['gst-return-status'], 'GST Return Status'],
                        ['tool', ['gst-late-fee-calculator'], 'Late Fee Calculator'],
                        ['tool', ['ifsc-code'], 'IFSC Lookup'],
                        ['tool', ['einvoice-status'], 'E-Invoice Status'],
                        ['tool', ['online-ocr'], 'Online OCR'],
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
