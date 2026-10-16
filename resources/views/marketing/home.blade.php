@extends('layouts.marketing')

@section('content')

{{-- ══ 1. HERO ══════════════════════════════════════════════ --}}
<section class="relative overflow-hidden bg-gradient-to-b from-brand-50/80 via-white to-white">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 pb-12 pt-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] lg:items-center lg:gap-12 lg:pb-16 lg:pt-14 lg:px-6">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    {{ __('100% GST compliant') }}
                </span>
                @if ($rating)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">
                        {{ $rating }}/5 {{ __('from business owners') }}
                    </span>
                @endif
            </div>

            <h1 class="mt-5 text-[2rem] font-bold leading-[1.12] tracking-tight text-slate-900 sm:text-5xl lg:text-[3.2rem]">
                {{ __('Best GST Billing Software for Small Businesses') }}
            </h1>

            <p class="mt-4 max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg">
                {{ __('Create a GST invoice in 30 seconds, share it on WhatsApp, collect by UPI — and stay ready for e-Invoicing, e-Way Bills and GSTR filing. Built for Indian shops, traders and consultants.') }}
            </p>

            <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('register') }}"
                   class="rounded-xl bg-brand-600 px-7 py-3.5 text-center text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:bg-brand-700">
                    {{ __('Start 14-day free trial') }}
                </a>
                <a href="{{ route('download') }}"
                   class="rounded-xl border border-slate-300 bg-white px-7 py-3.5 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    {{ __('Install the app') }}
                </a>
            </div>

            <ul class="mt-6 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-500">
                @foreach ([__('No credit card needed'), __('Works offline'), __('Hindi & Hinglish'), __('Free setup')] as $point)
                    <li class="flex items-center gap-1.5">
                        <x-icon name="check-circle" class="h-3.5 w-3.5 text-emerald-600" />
                        {{ $point }}
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="relative">
            <img src="{{ asset('images/hero-invoice.png') }}"
                 alt="{{ __('GST invoice created with BikriBook') }}"
                 width="1200" height="672" fetchpriority="high"
                 class="mx-auto w-full max-w-2xl">
        </div>
    </div>

    {{-- Platform badges --}}
    <div class="mx-auto max-w-7xl px-4 pb-10 lg:px-6">
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('download') }}" class="flex items-center gap-2.5 rounded-xl border border-slate-300 bg-white px-4 py-2.5 transition hover:bg-slate-50">
                <x-icon name="mobile" class="h-5 w-5 text-brand-600" />
                <span class="text-left leading-tight">
                    <span class="block text-[10px] uppercase tracking-wide text-slate-500">{{ __('Get it on') }}</span>
                    <span class="block text-sm font-semibold text-slate-900">Android</span>
                </span>
            </a>
            <a href="{{ route('download') }}" class="flex items-center gap-2.5 rounded-xl border border-slate-300 bg-white px-4 py-2.5 transition hover:bg-slate-50">
                <x-icon name="mobile" class="h-5 w-5 text-brand-600" />
                <span class="text-left leading-tight">
                    <span class="block text-[10px] uppercase tracking-wide text-slate-500">{{ __('Add to') }}</span>
                    <span class="block text-sm font-semibold text-slate-900">iPhone</span>
                </span>
            </a>
        </div>
    </div>
</section>

{{-- ══ 2. TRUST BAR ═════════════════════════════════════════ --}}
<section class="border-y border-slate-200 bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
        <p class="text-center text-xs font-semibold uppercase tracking-wider text-slate-400">
            {{ __('Businesses across India run their billing on BikriBook') }}
        </p>
        <div class="mt-6 grid grid-cols-2 gap-6 lg:grid-cols-4">
            @foreach ([
                [number_format($stats['businesses']).'+', __('Businesses onboard')],
                [number_format($stats['documents']).'+', __('Invoices & bills raised')],
                [number_format(max($stats['cities'], 1)), __('States served')],
                ['30 sec', __('Average invoice time')],
            ] as [$big, $label])
                <div class="text-center">
                    <div class="text-2xl font-bold text-slate-900 sm:text-3xl">{{ $big }}</div>
                    <div class="mt-1 text-xs text-slate-500 sm:text-sm">{{ $label }}</div>
                </div>
            @endforeach
        </div>

        {{-- Integrations row (a factual alternative to a press-logo strip) --}}
        <div class="mt-8 flex flex-wrap items-center justify-center gap-x-8 gap-y-3 border-t border-slate-200 pt-6 text-xs font-semibold uppercase tracking-wide text-slate-400">
            <span>{{ __('Works with') }}</span>
            @foreach (['NIC e-Invoice', 'GST Portal', 'Shopify', 'WhatsApp', 'Razorpay'] as $integration)
                <span class="text-slate-500">{{ $integration }}</span>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ 3. BUSINESS JOURNEY ═════════════════════════════════ --}}
<section class="bg-white py-16 lg:py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('Your business journey, built into BikriBook') }}</h2>
            <p class="mt-3 text-slate-600">{{ __('Start with simple billing and switch on more as you grow — nothing to reinstall, no data to move.') }}</p>
        </div>

        <ol class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['file', __('Getting started'), __('Add your items, set GST rates and raise your first invoice the same day.')],
                ['chart', __('Growing'), __('Track stock, send payment reminders and watch what is selling.')],
                ['trending', __('Established'), __('Team roles, purchase orders, party ledgers and profit reports.')],
                ['building', __('Scaling'), __('Multiple branches, e-Invoicing, e-Way Bills and Shopify sync.')],
            ] as $i => [$icon, $title, $desc])
                <li class="relative rounded-2xl border border-slate-200 bg-white p-6">
                    <span class="absolute -top-3 left-6 rounded-full bg-brand-600 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-white">
                        {{ __('Stage') }} {{ $i + 1 }}
                    </span>
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-50">
                        <x-icon :name="$icon" class="h-6 w-6 text-brand-600" />
                    </span>
                    <h3 class="mt-4 font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ══ 4. FIND YOUR SOLUTION ═══════════════════════════════ --}}
<section class="border-y border-slate-200 bg-slate-50 py-16 lg:py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('Find the right solution for you') }}</h2>
            <p class="mt-3 text-slate-600">{{ __('Whether you sell at a counter or file returns for fifty clients, there is a BikriBook setup for it.') }}</p>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-2">
            @foreach ([
                [
                    'store-front', __('For business owners'),
                    __('Run your shop, store or agency. Bill customers, track stock, send WhatsApp invoices and see your profit every month.'),
                    [__('GST & non-GST invoices'), __('Inventory with low-stock alerts'), __('WhatsApp sharing with UPI QR'), __('Multi-branch for chains')],
                    route('features'),
                ],
                [
                    'calculator', __('For accountants & consultants'),
                    __('Manage every client in one place — pull their sales and purchase data, file GSTR-1 and 3B, and give owners read-only access.'),
                    [__('Client-wise dashboards'), __('GSTR-1 & 3B ready exports'), __('Ledger and outstanding reports'), __('Partner programme with payouts')],
                    route('solution', 'accountants'),
                ],
            ] as [$icon, $title, $desc, $points, $link])
                <div class="flex flex-col rounded-2xl border border-slate-200 bg-white p-7">
                    <span class="grid h-12 w-12 place-items-center rounded-xl bg-brand-50">
                        <x-icon :name="$icon" class="h-6 w-6 text-brand-600" />
                    </span>
                    <h3 class="mt-4 text-xl font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>
                    <ul class="mt-5 flex-1 space-y-2 text-sm text-slate-700">
                        @foreach ($points as $point)
                            <li class="flex items-start gap-2.5">
                                <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                                {{ $point }}
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ $link }}" class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:text-brand-700">
                        {{ __('Explore') }}
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ 5. WHY BUSINESSES CHOOSE US (stats + photo) ═════════ --}}
<section class="bg-white py-16 lg:py-20">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 lg:grid-cols-2 lg:items-center lg:px-6">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('Why businesses choose BikriBook') }}</h2>
            <p class="mt-3 text-slate-600">
                {{ __('Most owners switch because a paper bill and a spreadsheet cannot keep up — and because getting GST wrong costs more than the software does.') }}
            </p>

            <div class="mt-8 grid grid-cols-2 gap-5">
                @foreach ([
                    [number_format($stats['businesses']).'+', __('businesses onboard'), 'users'],
                    [number_format(max($stats['documents'], 1)).'+', __('documents created'), 'invoice'],
                    [$rating ? $rating.'/5' : __('New'), __('customer rating'), 'award'],
                    ['100%', __('GST ready'), 'shield'],
                ] as [$big, $label, $icon])
                    <div class="rounded-2xl border border-slate-200 p-5">
                        <x-icon :name="$icon" class="h-5 w-5 text-brand-600" />
                        <div class="mt-2 text-2xl font-bold text-slate-900">{{ $big }}</div>
                        <div class="text-xs text-slate-500">{{ $label }}</div>
                    </div>
                @endforeach
            </div>

            <a href="{{ route('register') }}" class="mt-7 inline-block rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">
                {{ __('Create your free account') }}
            </a>
        </div>

        <div>
            <img src="{{ asset('images/marketing/shop-counter.jpg') }}"
                 alt="{{ __('Shop owner using BikriBook at the counter') }}"
                 loading="lazy" class="w-full rounded-3xl object-cover shadow-lg">
        </div>
    </div>
</section>

{{-- ══ 6. CORE FEATURES ════════════════════════════════════ --}}
<section class="border-y border-slate-200 bg-slate-50 py-16 lg:py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('Why BikriBook is the right billing software for small businesses') }}</h2>
            <p class="mt-3 text-slate-600">{{ __('Everything a growing Indian business needs, in one app.') }}</p>
        </div>

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['invoice', 'GST & non-GST invoicing', 'Tax invoice or plain bill, switched per document with separate numbering series.', 'solution:gst-billing'],
                ['zap', 'e-Invoice (IRN) in one click', 'Straight to the NIC portal. IRN and signed QR printed on the PDF.', 'solution:e-invoicing'],
                ['truck', 'e-Way Bills', 'Generated from the IRN with transporter and vehicle details.', 'solution:e-way-bill'],
                ['inventory', 'Inventory & stock', 'Live levels, low-stock alerts, purchase orders and purchase bills.', 'solution:inventory'],
                ['cart', 'Shopify sync', 'Orders become invoices; refunds become credit notes automatically.', 'solution:shopify'],
                ['chart', 'GST returns & reports', 'GSTR-1, GSTR-3B, profit & loss, item-wise and party-wise reports.', 'features'],
                ['message', 'WhatsApp invoicing', 'Send the bill as a WhatsApp link with a UPI QR in the same message.', 'features'],
                ['users', 'Team with roles', 'Owner, admin, accountant, counter staff and read-only CA access.', 'features'],
                ['store', 'Multi-branch', 'Run several shops from one login and switch between them.', 'features'],
            ] as [$icon, $title, $desc, $link])
                <a href="{{ str_starts_with($link, 'solution:') ? route('solution', substr($link, 9)) : route($link) }}"
                   class="group rounded-2xl border border-slate-200 bg-white p-6 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-50">
                        <x-icon :name="$icon" class="h-6 w-6 text-brand-600" />
                    </span>
                    <h3 class="mt-4 font-semibold text-slate-900">{{ __($title) }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __($desc) }}</p>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">
                        {{ __('Learn more') }} <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ 7. HOW IT WORKS ═════════════════════════════════════ --}}
<section class="bg-white py-16 lg:py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('How to start billing with BikriBook') }}</h2>
            <p class="mt-3 text-slate-600">{{ __('Three steps, no training. If you can use WhatsApp, you can use BikriBook.') }}</p>
        </div>

        <ol class="mt-12 grid gap-6 md:grid-cols-3">
            @foreach ([
                ['file', __('Add your items once'), __('Type a product name and price, set the GST rate, or paste your existing list. HSN codes are searchable from a built-in master.')],
                ['invoice-check', __('Raise the bill'), __('Pick the customer, tap items, save. Tax is calculated, the number is assigned and stock updates — offline too.')],
                ['rupee', __('Send and get paid'), __('Share the invoice on WhatsApp, collect by UPI QR, and track who has paid and who is overdue.')],
            ] as $i => [$icon, $title, $desc])
                <li class="relative rounded-2xl border border-slate-200 bg-white p-6">
                    <span class="absolute -top-3 left-6 rounded-full bg-brand-600 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-white">
                        {{ __('Step') }} {{ $i + 1 }}
                    </span>
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-50">
                        <x-icon :name="$icon" class="h-6 w-6 text-brand-600" />
                    </span>
                    <h3 class="mt-4 font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ══ 8. START BILLING BANNER ═════════════════════════════ --}}
<section class="bg-white pb-16 lg:pb-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-6">
        <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-brand-700 via-brand-600 to-brand-500 px-8 py-10 lg:px-12">
            <div class="flex flex-wrap items-center justify-between gap-6">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">{{ __('Create your first GST bill today') }}</h2>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-brand-50">
                        {{ __('Set up your business in under five minutes. Free for 14 days, no card, and your data stays yours if you leave.') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="rounded-xl bg-white px-6 py-3 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">
                        {{ __('Start billing free') }}
                    </a>
                    <a href="{{ route('contact') }}" class="rounded-xl border border-white/40 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                        {{ __('Talk to us') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ 9. BUILT-IN INTELLIGENCE / DASHBOARD ════════════════ --}}
<section class="border-y border-slate-200 bg-slate-50 py-16 lg:py-20">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 lg:grid-cols-2 lg:items-center lg:px-6">
        <div class="order-2 lg:order-1">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('Know your numbers without opening a register') }}</h2>
            <p class="mt-3 text-slate-600">
                {{ __('Sales this month, GST collected, who owes you money and what is sitting in your warehouse — the dashboard answers all four before you ask.') }}
            </p>

            <div class="mt-8 space-y-4">
                @foreach ([
                    ['chart', __('Profit & loss'), __('Revenue minus cost of goods and expenses, updated with every bill.')],
                    ['wallet', __('Outstanding & ageing'), __('See exactly who is overdue, by how many days, and send a reminder.')],
                    ['inventory', __('Stock valuation'), __('What is on hand, what it is worth, and what you need to reorder.')],
                    ['shield', __('GST-ready data'), __('Every figure feeds straight into your GSTR-1 and GSTR-3B exports.')],
                ] as [$icon, $title, $desc])
                    <div class="flex gap-4">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white ring-1 ring-slate-200">
                            <x-icon :name="$icon" class="h-5 w-5 text-brand-600" />
                        </span>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
                            <p class="mt-0.5 text-sm text-slate-600">{{ $desc }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="order-1 lg:order-2">
            <img src="{{ asset('images/marketing/app-phone.jpg') }}"
                 alt="{{ __('BikriBook dashboard on a phone') }}"
                 loading="lazy" class="mx-auto w-full max-w-lg rounded-3xl">
        </div>
    </div>
</section>

{{-- ══ 10. INVENTORY SHOWCASE ══════════════════════════════ --}}
<section class="bg-white py-16 lg:py-20">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 lg:grid-cols-2 lg:items-center lg:px-6">
        <div>
            <img src="{{ asset('images/marketing/warehouse-stock.jpg') }}"
                 alt="{{ __('Organised warehouse stock') }}"
                 loading="lazy" class="w-full rounded-3xl object-cover">
        </div>

        <div>
            <span class="inline-block rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ __('Inventory') }}</span>
            <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ __('Stock that matches what is actually on the shelf') }}</h2>
            <p class="mt-3 text-slate-600">
                {{ __('Every sale and purchase moves the number. Set a reorder level once and BikriBook tells you what to buy — no month-end counting.') }}
            </p>

            <div class="mt-7 grid grid-cols-2 gap-4">
                @foreach ([
                    [__('Live stock levels'), __('Updated the moment a bill is saved.')],
                    [__('Low-stock alerts'), __('Warnings before you run out of your best sellers.')],
                    [__('Purchase orders & bills'), __('Record purchases and stock in one step.')],
                    [__('Item-wise profit'), __('See which products actually earn money.')],
                ] as [$title, $desc])
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
                        <p class="mt-1 text-xs leading-relaxed text-slate-600">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ══ 11. LANGUAGE SUPPORT ════════════════════════════════ --}}
<section class="border-y border-slate-200 bg-brand-900 py-16 lg:py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-6">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-center">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white">
                    <x-icon name="globe" class="h-3.5 w-3.5" />
                    {{ __('Language') }}
                </span>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-white">
                    {{ __('Available in Hindi and Hinglish') }}
                </h2>
                <p class="mt-3 max-w-xl text-sm leading-relaxed text-brand-100">
                    {{ __('Your counter staff may not read English fluently — so the whole interface switches to Hindi or Hinglish with one tap. The switch is per person, so the owner can keep English while the counter works in Hindi.') }}
                </p>
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach (['English', 'हिंदी', 'Hinglish'] as $lang)
                        <span class="rounded-lg border border-white/25 px-3 py-1.5 text-xs font-medium text-white">{{ $lang }}</span>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ([
                    ['invoice', __('Invoice labels translated'), __('Tax Invoice, place of supply and terms print correctly in the language you pick.')],
                    ['users', __('Each user chooses'), __('The owner stays on English, the counter switches to Hindi — same business.')],
                    ['message', __('WhatsApp templates'), __('Share bills with the customer in the language they read.')],
                    ['graduation', __('Easier training'), __('New staff learn the app in a day because the words are already familiar.')],
                ] as [$icon, $title, $desc])
                    <div class="rounded-2xl border border-white/15 bg-white/5 p-5">
                        <x-icon :name="$icon" class="h-5 w-5 text-brand-200" />
                        <h3 class="mt-3 text-sm font-semibold text-white">{{ $title }}</h3>
                        <p class="mt-1.5 text-xs leading-relaxed text-brand-100">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ══ 12. TESTIMONIALS ════════════════════════════════════ --}}
@if ($testimonials->count())
    <section class="bg-white py-16 lg:py-20">
        <div class="mx-auto max-w-7xl px-4 lg:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('Business owners who switched') }}</h2>
                @if ($rating)
                    <p class="mt-3 text-slate-600">
                        <span class="font-semibold text-amber-600">{{ $rating }}/5</span>
                        {{ __('average rating from') }} {{ $testimonials->count() }}+ {{ __('reviews') }}
                    </p>
                @endif
            </div>

            <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($testimonials as $review)
                    <figure class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex gap-0.5">
                            @for ($i = 0; $i < $review->rating; $i++)
                                <x-icon name="star" class="h-4 w-4 text-amber-500" />
                            @endfor
                        </div>
                        <blockquote class="mt-3 flex-1 text-sm leading-relaxed text-slate-700">{{ $review->quote }}</blockquote>
                        <figcaption class="mt-5 flex items-center gap-3 border-t border-slate-100 pt-4">
                            @if ($review->photo)
                                <img src="{{ Storage::url($review->photo) }}" alt="{{ $review->name }}" class="h-10 w-10 rounded-full object-cover">
                            @else
                                <span class="grid h-10 w-10 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">{{ $review->initials() }}</span>
                            @endif
                            <span class="text-xs">
                                <span class="block font-semibold text-slate-900">{{ $review->name }}</span>
                                <span class="block text-slate-500">{{ $review->business ?: $review->typeLabel() }}{{ $review->city ? ' · '.$review->city : '' }}</span>
                            </span>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ══ 13. WHO CAN USE IT ══════════════════════════════════ --}}
<section class="border-y border-slate-200 bg-slate-50 py-16 lg:py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('Who uses BikriBook?') }}</h2>
            <p class="mt-3 text-slate-600">{{ __('If you buy or sell in India and need a correct bill, this is for you.') }}</p>
        </div>

        <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ([
                ['store-front', 'Retailers', 'Fast counter billing'],
                ['inventory', 'Wholesalers', 'Bulk rates & credit'],
                ['truck', 'Distributors', 'Routes & stock'],
                ['factory', 'Manufacturers', 'Job work & BOM'],
                ['calculator', 'Accountants', 'Client dashboards'],
                ['cart', 'Online sellers', 'Shopify orders'],
                ['briefcase', 'Consultants', 'Service billing'],
                ['wrench', 'Repair shops', 'SAC codes'],
                ['coffee', 'Cafes & restaurants', 'Daily bills'],
                ['building', 'Traders', 'e-Way bills'],
                ['users', 'Sales staff', 'Raise bills only'],
                ['file', 'Freelancers', 'Non-GST bills'],
            ] as [$icon, $title, $desc])
                <div class="rounded-2xl border border-slate-200 bg-white p-4 text-center transition hover:border-brand-200">
                    <x-icon :name="$icon" class="mx-auto h-6 w-6 text-brand-600" />
                    <h3 class="mt-2 text-sm font-semibold text-slate-900">{{ __($title) }}</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">{{ __($desc) }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ 14. PRICING PREVIEW ═════════════════════════════════ --}}
<section class="bg-white py-16 lg:py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('Simple, honest pricing') }}</h2>
            <p class="mt-3 text-slate-600">{{ __('Start free. Upgrade only when your business grows. No setup fee, cancel any time.') }}</p>
        </div>

        <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($plans->take(3) as $plan)
                <div class="flex flex-col rounded-2xl border-2 border-slate-200 bg-white p-6 {{ $loop->index === 1 ? 'border-brand-500 shadow-lg' : '' }}">
                    @if ($loop->index === 1)
                        <span class="mb-3 inline-block w-fit rounded-full bg-accent-500 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-white">
                            {{ __('Most popular') }}
                        </span>
                    @endif
                    <h3 class="text-lg font-semibold text-slate-900">{{ $plan->name }}</h3>
                    <p class="mt-2">
                        <span class="text-3xl font-bold text-slate-900">{{ $plan->priceLabel() }}</span>
                        @if ($plan->price_paise)
                            <span class="text-sm text-slate-500">/{{ __('month') }}</span>
                        @endif
                    </p>
                    <ul class="mt-5 flex-1 space-y-2 text-sm text-slate-600">
                        <li class="flex items-start gap-2">
                            <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                            {{ $plan->invoice_limit ? __(':count invoices / month', ['count' => $plan->invoice_limit]) : __('Unlimited invoices') }}
                        </li>
                        <li class="flex items-start gap-2">
                            <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                            {{ __(':count team users', ['count' => $plan->user_limit]) }}
                        </li>
                        @foreach ([
                            ['einvoice', __('e-Invoice (IRN) & e-Way Bill')],
                            ['shopify', 'Shopify'],
                            ['multi_branch', __('Multiple branches')],
                        ] as [$feature, $label])
                            <li class="flex items-start gap-2 {{ $plan->includes($feature) ? '' : 'text-slate-400' }}">
                                <x-icon :name="$plan->includes($feature) ? 'check-circle' : 'x-circle'" class="mt-0.5 h-4 w-4 shrink-0 {{ $plan->includes($feature) ? 'text-emerald-600' : 'text-slate-300' }}" />
                                {{ $label }}
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}"
                       class="mt-6 rounded-xl px-5 py-3 text-center text-sm font-semibold transition {{ $loop->index === 1 ? 'bg-brand-600 text-white hover:bg-brand-700' : 'border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
                        {{ __('Start free trial') }}
                    </a>
                </div>
            @endforeach
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('pricing') }}" class="text-sm font-medium text-brand-600 hover:underline">{{ __('Compare all plans') }}</a>
        </div>
    </div>
</section>

{{-- ══ 15. APP / OFFLINE ═══════════════════════════════════ --}}
<section class="border-y border-slate-200 bg-slate-50 py-16 lg:py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-6">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-center">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">
                    <x-icon name="mobile" class="h-3.5 w-3.5" />
                    {{ __('Mobile app') }}
                </span>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ __('Bill from your phone — even without internet') }}</h2>
                <p class="mt-3 text-slate-600">
                    {{ __('Install BikriBook from your browser. It opens full-screen like an app, saves bills offline and syncs them automatically when the signal returns.') }}
                </p>

                <ul class="mt-6 space-y-3">
                    @foreach ([
                        __('Works offline — bills queue and sync later'),
                        __('Reminders when invoices become overdue'),
                        __('Send invoices and payment nudges on WhatsApp'),
                        __('Hindi and Hinglish for counter staff'),
                    ] as $point)
                        <li class="flex items-start gap-2.5 text-sm text-slate-700">
                            <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                            {{ $point }}
                        </li>
                    @endforeach
                </ul>

                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ route('download') }}" class="rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">
                        {{ __('How to install') }}
                    </a>
                    <a href="{{ route('register') }}" class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        {{ __('Start free trial') }}
                    </a>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['signal', __('Works offline'), __('No signal at the counter? Bills queue on the phone and sync when you are back online.')],
                    ['bell', __('Overdue reminders'), __('Get a notification when invoices go past due, then send a WhatsApp nudge in one tap.')],
                    ['globe', __('Any device'), __('Phone, tablet or laptop — same books, same numbers, nothing to install.')],
                    ['shield', __('Your data stays safe'), __('Encrypted credentials, role-based access and a full audit trail of changes.')],
                ] as [$icon, $title, $desc])
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <x-icon :name="$icon" class="h-5 w-5 text-brand-600" />
                        <h3 class="mt-3 text-sm font-semibold text-slate-900">{{ $title }}</h3>
                        <p class="mt-1.5 text-xs leading-relaxed text-slate-600">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ══ 16. COMPARISON ══════════════════════════════════════ --}}
<section class="bg-white py-16 lg:py-20">
    <div class="mx-auto max-w-5xl px-4 lg:px-6">
        <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900">{{ __('Why businesses move from registers and spreadsheets') }}</h2>

        <div class="mt-10 overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full min-w-[600px] text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">{{ __('What you need') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('Register / Excel') }}</th>
                        <th class="px-5 py-3 font-medium text-brand-700">{{ __('With BikriBook') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ([
                        ['GST-compliant invoice with HSN', 'Made by hand, easy to get wrong', 'Auto-calculated, always correct'],
                        ['Monthly GSTR-1 data', 'Hours of retyping', 'Export in seconds'],
                        ['Stock that matches reality', 'Counted at month end', 'Updates with every bill'],
                        ['Sending the bill to the customer', 'Photo of a paper bill', 'WhatsApp link with UPI QR'],
                        ['Knowing who owes you money', 'Memory and follow-ups', 'Overdue list with reminders'],
                        ['Working from home or the shop', 'Only where the book is', 'Phone, laptop, any device'],
                    ] as [$need, $old, $new])
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ __($need) }}</td>
                            <td class="px-3 py-3 text-slate-500">{{ __($old) }}</td>
                            <td class="px-5 py-3 font-medium text-emerald-700">{{ __($new) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

{{-- ══ 17. PARTNER CTA ═════════════════════════════════════ --}}
<section class="border-t border-slate-200 bg-white py-16">
    <div class="mx-auto max-w-7xl px-4 lg:px-6">
        <div class="grid gap-6 overflow-hidden rounded-3xl bg-slate-900 p-8 lg:grid-cols-[minmax(0,7fr)_minmax(0,4fr)] lg:items-center lg:p-10">
            <div>
                <span class="inline-block rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white">{{ __('Partner programme') }}</span>
                <h2 class="mt-4 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                    {{ __('Sell BikriBook in your city and earn on every sale') }}
                </h2>
                <p class="mt-3 max-w-xl text-sm leading-relaxed text-slate-300">
                    {{ __('Become a distributor, channel partner or affiliate. Up to 35% margin, 20% recurring commission on renewals, marketing material and training included — with monthly payouts.') }}
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('partners.distributor') }}" class="rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">
                        {{ __('Become a distributor') }}
                    </a>
                    <a href="{{ route('partners.affiliate') }}" class="rounded-xl border border-slate-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        {{ __('Affiliate / influencer') }}
                    </a>
                </div>
            </div>

            <div class="grid gap-3">
                @foreach ([
                    ['Up to 35%', __('margin per sale')],
                    ['20%', __('recurring on renewals')],
                    [__('Monthly'), __('payouts to your bank')],
                ] as [$big, $label])
                    <div class="rounded-2xl bg-white/10 px-5 py-4">
                        <div class="text-xl font-bold text-white">{{ $big }}</div>
                        <div class="text-xs text-slate-300">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ══ 18. BLOG ════════════════════════════════════════════ --}}
@if ($posts->count())
    <section class="border-t border-slate-200 bg-slate-50 py-16 lg:py-20">
        <div class="mx-auto max-w-7xl px-4 lg:px-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('GST guides, in plain English') }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Practical answers to the questions shop owners actually ask.') }}</p>
                </div>
                <a href="{{ route('blog.index') }}" class="text-sm font-medium text-brand-600 hover:underline">{{ __('All articles') }}</a>
            </div>

            <div class="mt-8 grid gap-6 md:grid-cols-3">
                @foreach ($posts as $post)
                    @include('marketing.blog._card', ['post' => $post])
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ══ 19. FAQs ════════════════════════════════════════════ --}}
@include('marketing._faq')

{{-- ══ 20. FINAL CTA ═══════════════════════════════════════ --}}
<section class="relative overflow-hidden bg-brand-900 py-20">
    <div class="relative mx-auto max-w-3xl px-4 text-center lg:px-6">
        <img src="{{ asset('images/brand/logo-on-color.png') }}"
             alt="{{ config('app.name') }}" width="240" height="67"
             class="mx-auto h-11 w-auto" loading="lazy">
        <h2 class="mt-6 text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ __('Stop writing bills by hand') }}</h2>
        <p class="mt-4 text-lg text-brand-100">
            {{ __('Free for 14 days. No card, no training, no setup fee. Cancel any time.') }}
        </p>
        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            <a href="{{ route('register') }}" class="rounded-xl bg-white px-7 py-3.5 text-sm font-semibold text-brand-800 transition hover:bg-brand-50">
                {{ __('Create your free account') }}
            </a>
            <a href="{{ route('contact') }}" class="rounded-xl border border-white/30 px-7 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10">
                {{ __('Book a demo') }}
            </a>
        </div>
        <p class="mt-6 text-xs text-brand-200">{{ __('Setup takes under 5 minutes — most businesses raise their first invoice the same day.') }}</p>
    </div>
</section>

@endsection
