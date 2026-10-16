<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'hinglish' ? 'hi-Latn' : app()->getLocale() }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.gtm-head')
    @include('partials.pwa-head')
</head>
<body class="h-full text-slate-800 antialiased">
    @include('partials.gtm-body')
<div class="flex min-h-full">
    {{-- Form side --}}
    <div class="flex w-full flex-col justify-center px-6 py-12 lg:w-1/2 lg:px-16">
        <div class="mx-auto w-full max-w-md">
            <div class="mb-4 flex justify-end">@include('partials.locale-switcher')</div>
            <a href="{{ url('/') }}" class="mb-8 inline-flex items-center gap-2">
                <img src="{{ asset('images/brand/mark-256.png') }}" alt="{{ config('app.name') }}" width="72" height="72" class="h-9 w-9">
                <span class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
            </a>

            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">@yield('heading')</h1>
            <p class="mt-1 text-sm text-slate-500">@yield('subheading')</p>

            @if (session('success'))
                <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <ul class="list-inside list-disc space-y-0.5">
                        @foreach ($errors->unique() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-6">@yield('form')</div>

            <p class="mt-8 text-center text-sm text-slate-500">@yield('footer')</p>
        </div>
    </div>

    {{-- Marketing side --}}
    <div class="hidden bg-slate-900 lg:flex lg:w-1/2 lg:flex-col lg:justify-center lg:px-16">
        <blockquote class="max-w-md">
            <p class="text-2xl font-medium leading-snug text-white">
                {{ __('GST invoicing, e-Way Bills and IRN generation — without the spreadsheet gymnastics.') }}
            </p>
            <footer class="mt-8 space-y-3 text-sm text-slate-300">
                @foreach ([
                    __('GST and non-GST invoices from one place'),
                    __('e-Invoice (IRN) and e-Way Bill via NIC'),
                    __('GSTR-1 and GSTR-3B ready for filing'),
                    __('Shopify orders billed automatically'),
                ] as $point)
                    <div class="flex items-start gap-2.5">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/>
                        </svg>
                        <span>{{ $point }}</span>
                    </div>
                @endforeach
            </footer>
        </blockquote>
    </div>
</div>
@include('partials.pwa-install')
</body>
</html>
