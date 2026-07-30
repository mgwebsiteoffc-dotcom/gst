@extends('layouts.marketing')

@section('content')
<section class="bg-gradient-to-b from-indigo-50 to-white py-14">
    <div class="mx-auto max-w-4xl px-4 text-center lg:px-6">
        <h1 class="text-4xl font-bold tracking-tight text-slate-900">Free GST invoice formats</h1>
        <p class="mt-4 text-lg text-slate-600">Professional, Rule 46 compliant templates. Pick one in Settings and it applies to every invoice.</p>
        <x-illustration name="page-templates" alt="Three GST invoice template designs" eager
                        class="mx-auto mt-10 w-full max-w-xl" />
    </div>
</section>

<section class="py-14">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['Classic', 'A clean bordered layout that prints well on A4 and looks familiar to any accountant.'],
                ['Modern', 'More whitespace and a coloured accent header, using your brand colour.'],
                ['Compact', 'Fits up to 20 line items on a single page — built for wholesalers.'],
            ] as [$tpl, $desc])
                <article class="overflow-hidden rounded-2xl border border-slate-200">
                    <div class="border-b border-slate-100 bg-slate-50 p-5">
                        <div class="rounded-lg bg-white p-4 shadow-sm">
                            <div class="flex justify-between border-b border-slate-100 pb-2">
                                <div class="h-2 w-20 rounded bg-slate-800"></div>
                                <div class="h-2 w-14 rounded bg-indigo-400"></div>
                            </div>
                            @for ($i = 0; $i < 4; $i++)
                                <div class="mt-2 flex gap-2">
                                    <div class="h-1.5 flex-1 rounded bg-slate-200"></div>
                                    <div class="h-1.5 w-8 rounded bg-slate-200"></div>
                                    <div class="h-1.5 w-10 rounded bg-slate-200"></div>
                                </div>
                            @endfor
                            <div class="mt-3 flex justify-end"><div class="h-2 w-16 rounded bg-slate-800"></div></div>
                        </div>
                    </div>
                    <div class="p-5">
                        <h2 class="font-semibold text-slate-900">{{ $tpl }}</h2>
                        <p class="mt-1.5 text-sm text-slate-600">{{ $desc }}</p>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-10 rounded-2xl border border-slate-200 p-6">
            <h2 class="font-semibold text-slate-900">Every template includes</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    'Your logo and signature', 'GSTIN, PAN and address', 'HSN/SAC per line',
                    'CGST/SGST or IGST split', 'Amount in words', 'e-Invoice QR code',
                    'Bank and UPI details', 'Terms and conditions', 'HSN summary table',
                ] as $f)
                    <p class="flex items-center gap-2 text-sm text-slate-600">
                        <span class="text-emerald-600">✓</span> {{ $f }}
                    </p>
                @endforeach
            </div>
        </div>
    </div>
</section>

<section class="bg-slate-900 py-14">
    <div class="mx-auto max-w-3xl px-4 text-center lg:px-6">
        <h2 class="text-2xl font-bold text-white">Make your first invoice free</h2>
        <a href="{{ route('register') }}" class="mt-6 inline-block rounded-xl bg-white px-6 py-3 text-sm font-semibold text-slate-900">Get started</a>
    </div>
</section>
@endsection
