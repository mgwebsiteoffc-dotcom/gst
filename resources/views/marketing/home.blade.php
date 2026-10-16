@extends('layouts.marketing')

@section('content')

{{-- ══ 1. HERO ══════════════════════════════════════════════ --}}
<section class="relative overflow-hidden bg-gradient-to-b from-indigo-50/80 via-white to-white">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 pb-14 pt-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] lg:items-center lg:gap-12 lg:pb-20 lg:pt-16 lg:px-6">

        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    {{ __('100% GST compliant · e-Invoice ready') }}
                </span>
                @if ($rating)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">
                         {{ $rating }}/5 {{ __('from shop owners') }}
                    </span>
                @endif
            </div>

            <h1 class="mt-5 text-[2.1rem] font-bold leading-[1.1] tracking-tight text-slate-900 sm:text-5xl lg:text-[3.4rem]">
                {{ __('GST invoices in') }}<br>
                <span class="text-indigo-600">{{ __('30 seconds') }}</span> <x-icon name="zap" class="h-6 w-6 text-brand-600" />
            </h1>

            <p class="mt-5 max-w-xl text-lg leading-relaxed text-slate-600">
                {{ __('Bikri ho, bill turant ho. Make the bill, share it on WhatsApp and collect payment by UPI — plus e-Invoicing, e-Way Bills, stock and GST reports in one app.') }}
            </p>

            <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('register') }}"
                   class="rounded-xl bg-indigo-600 px-7 py-3.5 text-center text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-700">
                    {{ __('Start 14-day free trial') }}
                </a>
                <a href="{{ route('download') }}"
                   class="rounded-xl border border-slate-300 bg-white px-7 py-3.5 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    {{ __('Install the app') }}
                </a>
            </div>

            <ul class="mt-6 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-500">
                @foreach ([
                    __('No credit card needed'),
                    __('Works offline'),
                    __('English & Hindi'),
                    __('Free setup'),
                ] as $point)
                    <li class="flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                        {{ $point }}
                    </li>
                @endforeach
            </ul>

            {{-- Store badges (links to the install guide) --}}
            <div class="mt-7 flex flex-wrap items-center gap-3">
                <a href="{{ route('download') }}" class="flex items-center gap-2.5 rounded-xl border border-slate-300 bg-white px-4 py-2.5 transition hover:bg-slate-50">
                    <svg class="h-6 w-6 text-slate-800" viewBox="0 0 24 24" fill="currentColor"><path d="M3 20.5V3.5c0-.6.3-1.1.8-1.4l10.5 10.4L3.8 21.9c-.5-.3-.8-.8-.8-1.4Zm13.3-8.6 2.9-2.9 2.1 1.2c1 .6 1 2 0 2.6l-2.1 1.2-2.9-2.9v.8Zm-1.1-1.1L5.1 1l10.6 6.1-1.5 3.7Zm0 2.2 1.5 3.7L5.1 23l10.1-10Z"/></svg>
                    <span class="text-left leading-tight">
                        <span class="block text-[10px] uppercase tracking-wide text-slate-500">{{ __('Get it on') }}</span>
                        <span class="block text-sm font-semibold text-slate-900">Android</span>
                    </span>
                </a>
                <a href="{{ route('download') }}" class="flex items-center gap-2.5 rounded-xl border border-slate-300 bg-white px-4 py-2.5 transition hover:bg-slate-50">
                    <svg class="h-6 w-6 text-slate-800" viewBox="0 0 24 24" fill="currentColor"><path d="M16.4 12.7c0-2.2 1.8-3.3 1.9-3.3-1-1.5-2.6-1.7-3.2-1.7-1.4-.1-2.6.8-3.3.8-.7 0-1.7-.8-2.9-.8-1.5 0-2.9.9-3.7 2.3-1.6 2.7-.4 6.7 1.1 8.9.8 1.1 1.7 2.3 2.9 2.3 1.2 0 1.6-.7 3-.7s1.8.7 3 .7c1.3 0 2.1-1.1 2.8-2.2.9-1.3 1.3-2.5 1.3-2.6 0 0-2.8-1.1-2.9-3.7ZM14.3 6c.6-.8 1.1-1.9 1-3-.9 0-2.1.6-2.7 1.4-.6.7-1.1 1.8-1 2.9 1 .1 2.1-.5 2.7-1.3Z"/></svg>
                    <span class="text-left leading-tight">
                        <span class="block text-[10px] uppercase tracking-wide text-slate-500">{{ __('Add to') }}</span>
                        <span class="block text-sm font-semibold text-slate-900">iPhone</span>
                    </span>
                </a>
            </div>
        </div>

        <div class="relative">
            <img src="{{ asset('images/hero-invoice.png') }}"
                 alt="{{ __('BikriBook GST invoice on a laptop and phone') }}"
                 width="1200" height="672" loading="eager" fetchpriority="high"
                 class="mx-auto w-full max-w-2xl drop-shadow-2xl">
        </div>
    </div>
</section>

{{-- ══ 2. LIVE NUMBERS ═════════════════════════════════════ --}}
<section class="border-y border-slate-200 bg-slate-50">
    <div class="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-4 py-8 lg:grid-cols-4 lg:px-6">
        @foreach ([
            [number_format($stats['documents']).'+', __('Invoices & bills created')],
            [number_format($stats['businesses']).'+', __('Businesses on BikriBook')],
            [number_format(max($stats['cities'], 1)), __('States served')],
            ['30 sec', __('Average time per invoice')],
        ] as [$big, $label])
            <div class="text-center">
                <div class="text-2xl font-bold text-slate-900 sm:text-3xl">{{ $big }}</div>
                <div class="mt-1 text-xs text-slate-500 sm:text-sm">{{ $label }}</div>
            </div>
        @endforeach
    </div>
</section>

{{-- ══ 3. TRUST STRIP ══════════════════════════════════════ --}}
@if ($featuredReviews->count())
    <section class="bg-white py-12">
        <div class="mx-auto max-w-7xl px-4 lg:px-6">
            <p class="text-center text-xs font-semibold uppercase tracking-wider text-slate-400">
                {{ __('Trusted by shops, traders and accountants across India') }}
            </p>
            <div class="mt-8 grid gap-5 md:grid-cols-3">
                @foreach ($featuredReviews as $review)
                    <figure class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                        <div class="text-amber-500">@for ($i = 0; $i < $review->rating; $i++)@endfor</div>
                        <blockquote class="mt-3 text-sm leading-relaxed text-slate-700">“{{ $review->quote }}”</blockquote>
                        <figcaption class="mt-4 flex items-center gap-3">
                            <span class="grid h-9 w-9 place-items-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">{{ $review->initials() }}</span>
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

{{-- ══ 4. EVERYTHING IN ONE APP ════════════════════════════ --}}
<section class="bg-white py-16 lg:py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">
                {{ __('Everything your business needs') }}<span class="text-indigo-600">.</span>
            </h2>
            <p class="mt-3 text-slate-600">
                {{ __('One app instead of a spreadsheet, a WhatsApp folder and a shoebox of receipts.') }}
            </p>
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
                ['store', 'Multi-branch', 'Run several shops or stores from one login and switch between them.', 'features'],
            ] as [$icon, $title, $desc, $link])
                <a href="{{ str_starts_with($link, 'solution:') ? route('solution', substr($link, 9)) : route($link) }}"
                   class="group rounded-2xl border border-slate-200 bg-white p-6 transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-lg">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-indigo-50"><x-icon :name="$icon" class="h-6 w-6 text-brand-600" /></span>
                    <h3 class="mt-4 font-semibold text-slate-900">{{ __($title) }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __($desc) }}</p>
                    <span class="mt-3 inline-block text-xs font-medium text-indigo-600 opacity-0 transition group-hover:opacity-100">{{ __('Learn more') }} </span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ 5. THREE STEPS ══════════════════════════════════════ --}}
<section class="border-y border-slate-200 bg-slate-50 py-16 lg:py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('From counter to WhatsApp in three steps') }}</h2>
            <p class="mt-3 text-slate-600">{{ __('No training needed. If you can use WhatsApp, you can use BikriBook.') }}</p>
        </div>

        <ol class="mt-12 grid gap-6 md:grid-cols-3">
            @foreach ([
                [__('Add your items once'), __('Type a product name and price, set the GST rate, or paste your existing list. HSN codes are searchable from a built-in master.'), 'file'],
                [__('Make the bill'), __('Pick the customer, tap items, hit save. Tax is calculated, the PDF is numbered and the stock is updated — offline if needed.'), 'zap'],
                [__('Send and get paid'), __('Share the invoice link on WhatsApp, collect by UPI QR, and track who has paid and who is overdue.'), 'rupee'],
            ] as $i => [$title, $desc, $icon])
                <li class="relative rounded-2xl border border-slate-200 bg-white p-6">
                    <span class="absolute -top-3 left-6 rounded-full bg-indigo-600 px-3 py-1 text-xs font-bold text-white">{{ __('Step') }} {{ $i + 1 }}</span>
                    <x-icon :name="$icon" class="mt-2 h-8 w-8 text-brand-600" />
                    <h3 class="mt-3 font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ══ 6. BUILT FOR EVERY BUSINESS ═════════════════════════ --}}
<section class="bg-white py-16 lg:py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-6">
        <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900">{{ __('Built for every kind of Indian business') }}</h2>
        <p class="mx-auto mt-3 max-w-2xl text-center text-slate-600">
            {{ __('Whatever you sell, the bill looks professional and the returns stay correct.') }}
        </p>

        <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ([
                ['store-front', 'Kirana & retail', 'Fast counter billing'],
                ['inventory', 'Wholesalers', 'Bulk rates & credit'],
                ['truck', 'Distributors', 'Routes & stock'],
                ['factory', 'Manufacturers', 'BOM & job work'],
                ['invoice', 'Chartered accountants', 'Client dashboards'],
                ['cart', 'Online sellers', 'Shopify orders'],
                ['briefcase', 'Freelancers', 'Non-GST bills'],
                ['wrench', 'Service businesses', 'SAC codes'],
                ['coffee', 'Cafes & restaurants', 'Quick daily bills'],
                ['building', 'Traders & suppliers', 'e-Way bills'],
            ] as [$icon, $title, $desc])
                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 text-center transition hover:border-indigo-200 hover:bg-white">
                    <x-icon :name="$icon" class="mx-auto h-7 w-7 text-brand-600" />
                    <h3 class="mt-2 text-sm font-semibold text-slate-900">{{ __($title) }}</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">{{ __($desc) }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('features') }}" class="text-sm font-medium text-indigo-600 hover:underline">{{ __('See every feature') }} </a>
        </div>
    </div>
</section>

{{-- ══ 7. COMPARISON ═══════════════════════════════════════ --}}
<section class="border-y border-slate-200 bg-slate-50 py-16 lg:py-20">
    <div class="mx-auto max-w-5xl px-4 lg:px-6">
        <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900">
            {{ __('Why businesses move from registers and spreadsheets') }}
        </h2>

        <div class="mt-10 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full min-w-[600px] text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">{{ __('What you need') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('Register / Excel') }}</th>
                        <th class="px-5 py-3 font-medium text-indigo-700">{{ __('With BikriBook') }}</th>
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
                            <td class="px-5 py-3 font-medium text-emerald-700"> {{ __($new) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

{{-- ══ 8. TESTIMONIALS ═════════════════════════════════════ --}}
@if ($testimonials->count())
    <section class="bg-white py-16 lg:py-20">
        <div class="mx-auto max-w-7xl px-4 lg:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('Business owners who switched') }}</h2>
                @if ($rating)
                    <p class="mt-3 text-slate-600">
                        <span class="font-semibold text-amber-600"> {{ $rating }}/5</span>
                        {{ __('average rating from') }} {{ $testimonials->count() }}+ {{ __('reviews') }}
                    </p>
                @endif
            </div>

            <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($testimonials as $review)
                    <figure class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="text-amber-500">@for ($i = 0; $i < $review->rating; $i++)@endfor</div>
                        <blockquote class="mt-3 flex-1 text-sm leading-relaxed text-slate-700">“{{ $review->quote }}”</blockquote>
                        <figcaption class="mt-5 flex items-center gap-3 border-t border-slate-100 pt-4">
                            @if ($review->photo)
                                <img src="{{ Storage::url($review->photo) }}" alt="{{ $review->name }}" class="h-10 w-10 rounded-full object-cover">
                            @else
                                <span class="grid h-10 w-10 place-items-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">{{ $review->initials() }}</span>
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

{{-- ══ 9. PRICING PREVIEW ══════════════════════════════════ --}}
<section class="border-y border-slate-200 bg-slate-50 py-16 lg:py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('Simple, honest pricing') }}</h2>
            <p class="mt-3 text-slate-600">{{ __('Start free. Upgrade only when your business grows. No setup fee, cancel any time.') }}</p>
        </div>

        <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($plans->take(3) as $plan)
                <div class="flex flex-col rounded-2xl border-2 border-slate-200 bg-white p-6 {{ $loop->index === 1 ? 'border-indigo-500 shadow-lg' : '' }}">
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
                        <li> {{ $plan->invoice_limit ? __(':count invoices / month', ['count' => $plan->invoice_limit]) : __('Unlimited invoices') }}</li>
                        <li> {{ __(':count team users', ['count' => $plan->user_limit]) }}</li>
                        <li class="{{ $plan->includes('einvoice') ? '' : 'text-slate-400' }}">{{ $plan->includes('einvoice') ? '' : '—' }} {{ __('e-Invoice (IRN) & e-Way Bill') }}</li>
                        <li class="{{ $plan->includes('shopify') ? '' : 'text-slate-400' }}">{{ $plan->includes('shopify') ? '' : '—' }} Shopify</li>
                        <li class="{{ $plan->includes('multi_branch') ? '' : 'text-slate-400' }}">{{ $plan->includes('multi_branch') ? '' : '—' }} {{ __('Multiple branches') }}</li>
                    </ul>
                    <a href="{{ route('register') }}"
                       class="mt-6 rounded-xl px-5 py-3 text-center text-sm font-semibold transition {{ $loop->index === 1 ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
                        {{ __('Start free trial') }}
                    </a>
                </div>
            @endforeach
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('pricing') }}" class="text-sm font-medium text-indigo-600 hover:underline">{{ __('Compare all plans') }} </a>
        </div>
    </div>
</section>

{{-- ══ 10. APP / OFFLINE ═══════════════════════════════════ --}}
<section class="bg-white py-16 lg:py-20">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 lg:grid-cols-2 lg:items-center lg:px-6">
        <div class="order-2 lg:order-1">
            <span class="inline-block rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ __('Mobile app') }}</span>
            <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-900">
                {{ __('Bill from your phone — even without internet') }}
            </h2>
            <p class="mt-4 text-slate-600">
                {{ __('Install BikriBook from your browser. It opens full-screen like a normal app, saves bills offline and syncs them automatically when the signal returns.') }}
            </p>

            <ul class="mt-6 space-y-3">
                @foreach ([
                    __('Works offline — bills queue on the phone and sync later'),
                    __('Push reminders when invoices become overdue'),
                    __('Send invoices and payment nudges on WhatsApp'),
                    __('Hindi and Hinglish interface for counter staff'),
                ] as $point)
                    <li class="flex items-start gap-2.5 text-sm text-slate-700">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                        {{ $point }}
                    </li>
                @endforeach
            </ul>

            <a href="{{ route('download') }}" class="mt-7 inline-block rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                {{ __('How to install') }}
            </a>
        </div>

        <div class="order-1 lg:order-2">
            <img src="{{ asset('images/marketing/app-phone.jpg') }}"
                 alt="{{ __('BikriBook app on a phone') }}"
                 loading="lazy" class="mx-auto w-full max-w-xl rounded-3xl">
        </div>
    </div>
</section>

{{-- ══ 11. STOCK / INVENTORY SHOWCASE ══════════════════════ --}}
<section class="border-y border-slate-200 bg-slate-50 py-16 lg:py-20">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 lg:grid-cols-2 lg:items-center lg:px-6">
        <div>
            <img src="{{ asset('images/marketing/warehouse-stock.jpg') }}"
                 alt="{{ __('Organised warehouse stock') }}"
                 loading="lazy" class="mx-auto w-full max-w-xl rounded-3xl">
        </div>

        <div>
            <span class="inline-block rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ __('Inventory') }}</span>
            <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-900">
                {{ __('Know exactly what is on the shelf') }}
            </h2>
            <p class="mt-4 text-slate-600">
                {{ __('Stock updates with every bill you raise. Get warned before you run out, and see what your inventory is worth — without counting boxes at month end.') }}
            </p>

            <div class="mt-6 grid grid-cols-2 gap-4">
                @foreach ([
                    [__('Live stock levels'), __('Every sale and purchase moves the number instantly.')],
                    [__('Low-stock alerts'), __('Set a reorder level per item and see what to buy.')],
                    [__('Stock valuation'), __('Total value of inventory at purchase price.')],
                    [__('Purchase orders & bills'), __('Record purchases and update stock in one step.')],
                ] as [$title, $desc])
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
                        <p class="mt-1 text-xs leading-relaxed text-slate-600">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ══ 12. MADE FOR INDIA ══════════════════════════════════ --}}
<section class="bg-white py-16 lg:py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('Made in India, for Indian businesses') }}</h2>
            <p class="mt-3 text-slate-600">
                {{ __('Built around the rules you actually file under — not a foreign tool with Indian labels.') }}
            </p>
        </div>

        <div class="mx-auto mt-10 w-full max-w-4xl overflow-hidden rounded-3xl">
            <img src="{{ asset('images/marketing/shop-counter.jpg') }}"
                 alt="{{ __('Shop owner billing a customer with BikriBook') }}"
                 loading="lazy" class="h-64 w-full object-cover sm:h-80">
        </div>

        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['globe', __('GST from the ground up'), __('CGST, SGST, IGST, cess, reverse charge, exports, SEZ and composition — all handled.')],
                ['lock', __('Your data stays yours'), __('Encrypted NIC credentials, role-based access and a full audit trail of who did what.')],
                ['globe', __('Bilingual interface'), __('English, Hindi or Hinglish — switch any time, even for your counter staff.')],
                ['phone', __('Support that speaks your language'), __('Chat, email or WhatsApp support for as long as you use BikriBook.')],
            ] as [$icon, $title, $desc])
                <div class="rounded-2xl border border-slate-200 p-6 text-center">
                    <x-icon :name="$icon" class="mx-auto h-8 w-8 text-brand-600" />
                    <h3 class="mt-3 font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ 13. DISTRIBUTOR / PARTNER CTA ═══════════════════════ --}}
<section class="border-t border-slate-200 bg-white py-16">
    <div class="mx-auto max-w-7xl px-4 lg:px-6">
        <div class="grid gap-6 overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 to-indigo-800 p-8 lg:grid-cols-[minmax(0,7fr)_minmax(0,4fr)] lg:items-center lg:p-10">
            <div>
                <span class="inline-block rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">{{ __('Partner programme') }}</span>
                <h2 class="mt-4 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                    {{ __('Sell BikriBook in your city and earn on every sale') }}
                </h2>
                <p class="mt-3 max-w-xl text-sm leading-relaxed text-indigo-100">
                    {{ __('Become a distributor, channel partner or affiliate. Up to 35% margin, 20% recurring commission on renewals, marketing material and training included — with monthly payouts.') }}
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('partners.distributor') }}" class="rounded-xl bg-white px-5 py-3 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50">
                        {{ __('Become a distributor') }}
                    </a>
                    <a href="{{ route('partners.affiliate') }}" class="rounded-xl border border-white/40 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
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
                    <div class="rounded-2xl bg-white/10 px-5 py-4 backdrop-blur">
                        <div class="text-xl font-bold text-white">{{ $big }}</div>
                        <div class="text-xs text-indigo-100">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ══ 14. BLOG ════════════════════════════════════════════ --}}
@if ($posts->count())
    <section class="border-t border-slate-200 bg-slate-50 py-16 lg:py-20">
        <div class="mx-auto max-w-7xl px-4 lg:px-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('GST guides, in plain English') }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Practical answers to the questions shop owners actually ask.') }}</p>
                </div>
                <a href="{{ route('blog.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">{{ __('All articles') }} </a>
            </div>

            <div class="mt-8 grid gap-6 md:grid-cols-3">
                @foreach ($posts as $post)
                    @include('marketing.blog._card', ['post' => $post])
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ══ 15. FAQs ════════════════════════════════════════════ --}}
@include('marketing._faq')

{{-- ══ 16. FINAL CTA ═══════════════════════════════════════ --}}
<section class="relative overflow-hidden bg-slate-900 py-20">
    <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-indigo-600/20 blur-3xl"></div>
    <div class="relative mx-auto max-w-3xl px-4 text-center lg:px-6">
        <img src="{{ asset('images/brand/logo-on-color.png') }}"
             alt="{{ config('app.name') }}" width="240" height="67"
             class="mx-auto h-11 w-auto" loading="lazy">
        <h2 class="mt-6 text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ __('Stop writing bills by hand') }}</h2>
        <p class="mt-4 text-lg text-slate-300">
            {{ __('Free for 14 days. No card, no training, no setup fee. Cancel any time.') }}
        </p>
        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            <a href="{{ route('register') }}" class="rounded-xl bg-white px-7 py-3.5 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">
                {{ __('Create your free account') }}
            </a>
            <a href="{{ route('contact') }}" class="rounded-xl border border-slate-600 px-7 py-3.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                {{ __('Book a demo') }}
            </a>
        </div>
        <p class="mt-6 text-xs text-slate-400">{{ __('Setup takes under 5 minutes — most businesses raise their first invoice the same day.') }}</p>
    </div>
</section>

@endsection
