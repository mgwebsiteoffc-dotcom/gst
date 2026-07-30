<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-slate-800 antialiased">
@php
    $tenant = \App\Support\TenantContext::get();
    $partnerProfile = auth()->check() && ! auth()->user()->tenant_id && ! auth()->user()->is_super_admin
        ? \App\Models\Partner::where('user_id', auth()->id())->first()
        : null;

    $isPlatform = auth()->check() && auth()->user()->is_super_admin;

    $groups = $partnerProfile ? [
        'Partner' => [
            ['partner.dashboard', 'Dashboard', []],
        ],
    ] : ($isPlatform ? [] : [
        'Sales' => [
            ['documents.index', 'Invoices', ['type' => 'invoice']],
            ['documents.index', 'Quotations', ['type' => 'quotation']],
            ['documents.index', 'Credit Notes', ['type' => 'credit_note']],
        ],
        'Purchases' => [
            ['documents.index', 'Purchase Orders', ['type' => 'purchase_order']],
            ['documents.index', 'Purchase Bills', ['type' => 'purchase_bill']],
        ],
        'Records' => [
            ['parties.index', 'Clients & Vendors', []],
            ['items.index', 'Products & Services', []],
        ],
        'Reports' => [
            ['reports.pl', 'Profit & Loss', []],
            ['reports.item', 'Item-wise Sales', []],
            ['reports.party', 'Party & Outstanding', []],
            ['reports.stock', 'Stock', []],
            ['reports.gstr1', 'GSTR-1', []],
            ['reports.gstr3b', 'GSTR-3B', []],
        ],
        'Setup' => [
            ['settings.index', 'Settings', []],
            ['migration.index', 'Data Migration', []],
            ['shopify.index', 'Shopify', []],
        ],
    ]);

    if ($isPlatform) {
        $groups['Platform'] = [
            ['admin.dashboard', 'Overview', []],
            ['admin.tenants.index', 'Businesses', []],
            ['admin.plans.index', 'Subscription Plans', []],
        ];
        $groups['Partners'] = [
            ['admin.partners.index', 'Partners', []],
            ['admin.payouts.index', 'Payouts', []],
            ['admin.commission-plans.index', 'Commission Plans', []],
        ];
        $groups['Content'] = [
            ['admin.posts.index', 'Blog Posts', []],
            ['admin.faqs.index', 'Marketing FAQs', []],
            ['admin.leads.index', 'Enquiries', []],
        ];
    }
@endphp

<div x-data="{ nav: false }" class="min-h-full lg:flex">
    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-40 w-60 -translate-x-full overflow-y-auto border-r border-slate-200 bg-white transition-transform lg:static lg:translate-x-0"
           :class="nav && '!translate-x-0'">
        <div class="flex h-14 items-center gap-2 px-5">
            <span class="grid h-7 w-7 place-items-center rounded-lg bg-indigo-600 text-sm font-bold text-white">B</span>
            <span class="font-semibold tracking-tight">{{ config('app.name') }}</span>
        </div>

        <nav class="px-3 pb-6">
            @unless ($partnerProfile || $isPlatform)
                <a href="{{ route('dashboard') }}"
                   class="mb-3 block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                    Dashboard
                </a>
            @endunless

            @foreach ($groups as $label => $links)
                <p class="px-3 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $label }}</p>
                @foreach ($links as [$route, $text, $params])
                    @php $active = request()->routeIs($route) && (! isset($params['type']) || request('type') === $params['type'] || request()->route('type') === $params['type']); @endphp
                    <a href="{{ route($route, $params) }}"
                       class="block rounded-lg px-3 py-1.5 text-sm {{ $active ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">{!! $text !!}</a>
                @endforeach
            @endforeach
        </nav>
    </aside>

    <div x-show="nav" x-cloak @click="nav = false" class="fixed inset-0 z-30 bg-slate-900/20 lg:hidden"></div>

    {{-- Main --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex h-14 items-center justify-between gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur lg:px-8">
            <button @click="nav = true" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <div class="flex items-center gap-2 text-sm text-slate-500">
                @if ($tenant)
                    <span class="font-medium text-slate-700">{{ $tenant->name }}</span>
                    @if ($tenant->gstin)
                        <span class="hidden font-mono text-xs text-slate-400 sm:inline">{{ $tenant->gstin }}</span>
                    @else
                        <x-badge tone="amber">Non-GST</x-badge>
                    @endif
                    @if ($tenant->trial_ends_at?->isFuture())
                        <x-badge tone="indigo">Trial · {{ (int) ceil(now()->floatDiffInDays($tenant->trial_ends_at, false)) }}d left</x-badge>
                    @endif
                @endif
            </div>

            <div class="flex items-center gap-2">
                @if (! $partnerProfile && ! $isPlatform && $tenant)
                    <x-btn variant="primary" size="sm" :href="route('documents.create', 'invoice')">+ New Invoice</x-btn>
                @endif

                @auth
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.outside="open = false"
                                class="flex items-center gap-2 rounded-lg py-1 pl-1 pr-2 hover:bg-slate-100">
                            <span class="grid h-8 w-8 place-items-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">
                                {{ auth()->user()->initials() }}
                            </span>
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" x-cloak x-transition.origin.top.right
                             class="absolute right-0 z-30 mt-2 w-56 rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                            <div class="border-b border-slate-100 px-4 py-2.5">
                                <p class="text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                                <x-badge tone="slate" class="mt-1.5">{{ auth()->user()->roleLabel() }}</x-badge>
                            </div>

                            @if ($partnerProfile)
                                <a href="{{ route('partner.dashboard') }}" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Partner dashboard</a>
                            @elseif ($tenant)
                                <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Settings</a>
                            @endif

                            @if (auth()->user()->is_super_admin)
                                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Platform Admin</a>
                            @endif

                            <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 text-left text-sm text-rose-600 hover:bg-rose-50">Sign out</button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </header>

        <main class="flex-1 px-4 py-6 lg:px-8">
            @if (session('success') || session('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     class="mb-5 flex items-start justify-between gap-3 rounded-lg border px-4 py-3 text-sm {{ session('error') ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800' }}">
                    <span>{{ session('success') ?: session('error') }}</span>
                    <button @click="show = false" class="text-lg leading-none opacity-50 hover:opacity-100">&times;</button>
                </div>
            @endif

            @if ($errors->any() && ! $errors->has('__none'))
                <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <p class="font-medium">Please fix the following:</p>
                    <ul class="mt-1 list-inside list-disc">
                        @foreach ($errors->unique() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
