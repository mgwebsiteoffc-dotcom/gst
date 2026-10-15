<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'hinglish' ? 'hi-Latn' : app()->getLocale() }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.gtm-head')
    @include('partials.pwa-head')
</head>
<body class="h-full text-slate-800 antialiased">
    @include('partials.gtm-body')
    @if (session('impersonator_id') && auth()->check())
        <div class="bg-amber-500 px-4 py-2 text-center text-sm font-medium text-white">
            Support mode — you are signed in as {{ auth()->user()->name }} ({{ auth()->user()->email }}).
            <form method="POST" action="{{ route('impersonate.leave') }}" class="ml-2 inline">
                @csrf
                <button class="rounded bg-white/20 px-2.5 py-0.5 text-xs font-semibold hover:bg-white/30">Exit &amp; back to admin</button>
            </form>
        </div>
    @endif
@php
    $tenant = \App\Support\TenantContext::get();
    $partnerProfile = auth()->check() && ! auth()->user()->tenant_id && ! auth()->user()->is_super_admin
        ? \App\Models\Partner::where('user_id', auth()->id())->first()
        : null;

    $isPlatform = auth()->check() && auth()->user()->is_super_admin;

    $groups = $partnerProfile ? [
        __('Partner') => [
            ['partner.dashboard', __('Dashboard'), []],
        ],
    ] : ($isPlatform ? [] : [
        __('Sales') => [
            ['documents.index', __('Invoices'), ['type' => 'invoice']],
            ['documents.index', __('Quotations'), ['type' => 'quotation']],
            ['documents.index', __('Credit Notes'), ['type' => 'credit_note']],
        ],
        __('Purchases') => [
            ['documents.index', __('Purchase Orders'), ['type' => 'purchase_order']],
            ['documents.index', __('Purchase Bills'), ['type' => 'purchase_bill']],
        ],
        __('Records') => [
            ['parties.index', __('Clients & Vendors'), []],
            ['items.index', __('Products & Services'), []],
        ],
        __('Reports') => [
            ['reports.pl', __('Profit & Loss'), []],
            ['reports.item', __('Item-wise Sales'), []],
            ['reports.party', __('Party & Outstanding'), []],
            ['reports.stock', __('Stock'), []],
            ['reports.gstr1', 'GSTR-1', []],
            ['reports.gstr3b', 'GSTR-3B', []],
        ],
        __('Setup') => [
            ['settings.index', __('Settings'), []],
            ['migration.index', __('Data Migration'), []],
            ['shopify.index', 'Shopify', []],
        ],
    ]);

    // Main-business owners/admins get the branch manager (visible from any branch).
    if (! $isPlatform && ! $partnerProfile && $tenant) {
        $home = $tenant->parent_tenant_id ? ($tenant->parent ?: $tenant) : $tenant;

        if (! $home->parent_tenant_id && auth()->user()->hasAnyRole(['owner', 'admin'])) {
            $groups[__('Setup')][] = ['business.branches', __('Business & Branches'), []];
        }
    }

    // Strings handed to the offline/PWA JavaScript. Built here as a variable
    // because Blade directives cannot take a multi-line array literal.
    $bbI18n = [
        'offline_saved' => __('Saved offline — will sync automatically.'),
        'all_synced' => __('All caught up!'),
        'back_online' => __('Back online — syncing…'),
        'still_offline' => __('You are offline'),
        'bills_synced' => __('Bills synced: :count'),
        'you_online' => __('You are online'),
        'you_offline' => __('You are offline'),
    ];

    if ($isPlatform) {
        $groups[__('Platform')] = [
            ['admin.dashboard', __('Overview'), []],
            ['admin.tenants.index', __('Businesses'), []],
            ['admin.plans.index', __('Subscription Plans'), []],
        ];
        $groups[__('Partners')] = [
            ['admin.partners.index', __('Partners'), []],
            ['admin.payouts.index', __('Payouts'), []],
            ['admin.commission-plans.index', __('Commission Plans'), []],
        ];
        $groups[__('Content')] = [
            ['admin.posts.index', __('Blog Posts'), []],
            ['admin.faqs.index', __('Marketing FAQs'), []],
            ['admin.leads.index', __('Enquiries'), []],
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
                        <x-badge tone="indigo">{{ __('Trial') }} · {{ (int) ceil(now()->floatDiffInDays($tenant->trial_ends_at, false)) }}{{ __('d left') }}</x-badge>
                    @endif
                @endif
            </div>

            <div class="flex items-center gap-2">
                @include('partials.locale-switcher')
                @include('partials.branch-switcher')
                @if (! $partnerProfile && ! $isPlatform && $tenant)
                    @include('partials.push-bell')
                    <a href="{{ route('offline.create') }}" data-offline-wrap title="{{ __('bills waiting to sync') }}"
                       class="hidden items-center gap-1 rounded-lg border border-amber-300 bg-amber-50 px-2 py-1.5 text-xs font-medium text-amber-700">
                        ⏳ <span data-offline-count>0</span>
                    </a>
                @endif
                @if (! $partnerProfile && ! $isPlatform && $tenant)
                    <x-btn variant="primary" size="sm" :href="route('documents.create', 'invoice')" onclick="if(!navigator.onLine){window.location.href='{{ route('offline.create') }}';return false;}">+ {{ __('New Invoice') }}</x-btn>
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
                                <a href="{{ route('partner.dashboard') }}" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">{{ __('Partner dashboard') }}</a>
                            @elseif ($tenant)
                                <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">{{ __('Settings') }}</a>
                            @endif

                            @if (auth()->user()->is_super_admin)
                                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">{{ __('Platform Admin') }}</a>
                            @endif

                            <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 text-left text-sm text-rose-600 hover:bg-rose-50">{{ __('Sign out') }}</button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </header>

        <main class="flex-1 px-4 pb-28 pt-6 lg:px-8 lg:pb-6">
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

    {{-- Mobile bottom tab bar: thumb-friendly nav for vendors on phones. --}}
    @if (! $partnerProfile && ! $isPlatform && $tenant)
        <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden">
            <div class="grid grid-cols-5 items-end px-2 pb-1 pt-1.5">
                <a href="{{ route('dashboard') }}"
                   class="flex flex-col items-center gap-0.5 rounded-lg py-1 text-[11px] {{ request()->routeIs('dashboard') ? 'font-semibold text-indigo-600' : 'text-slate-500' }}">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1V10Z"/></svg>
                    {{ __('Home') }}
                </a>
                <a href="{{ route('documents.index', 'invoice') }}"
                   class="flex flex-col items-center gap-0.5 rounded-lg py-1 text-[11px] {{ request()->routeIs('documents.index') ? 'font-semibold text-indigo-600' : 'text-slate-500' }}">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14h6M9 17.5h6M7 3h10l4 4v14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/></svg>
                    {{ __('Bills') }}
                </a>
                <a href="{{ route('documents.create', 'invoice') }}" aria-label="{{ __('New invoice') }}"
                   onclick="if(!navigator.onLine){window.location.href='{{ route('offline.create') }}';return false;}"
                   class="mx-auto -mt-7 grid h-14 w-14 place-items-center rounded-full bg-indigo-600 text-3xl leading-none text-white shadow-lg shadow-indigo-600/30 active:scale-95">+</a>
                <a href="{{ route('items.index') }}"
                   class="flex flex-col items-center gap-0.5 rounded-lg py-1 text-[11px] {{ request()->routeIs('items.*') ? 'font-semibold text-indigo-600' : 'text-slate-500' }}">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3 9 5-9 5-9-5 9-5Zm-9 9 9 5 9-5M3 17l9 5 9-5"/></svg>
                    {{ __('Items') }}
                </a>
                <button @click="nav = true"
                        class="flex flex-col items-center gap-0.5 rounded-lg py-1 text-[11px] text-slate-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    {{ __('Menu') }}
                </button>
            </div>
        </nav>
    @endif
</div>

@include('partials.pwa-install')
<script>
window.BB_I18N = @json($bbI18n);
</script>
<script src="/js/offline.js" defer></script>
<div id="bb-toasts" class="pointer-events-none fixed inset-x-3 top-16 z-50 flex flex-col items-center gap-2"></div>
</body>
</html>
