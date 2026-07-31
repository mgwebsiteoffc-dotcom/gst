@extends('layouts.marketing')
@section('content')
<section class="mx-auto max-w-3xl px-4 py-14 lg:px-6">
    <nav aria-label="Breadcrumb" class="mb-4 text-xs text-slate-500"><a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a> › <a href="{{ route('tools') }}" class="hover:text-indigo-600">Tools</a> › {{ $name }}</nav>
    <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $name }}</h1>
    <p class="mt-3 text-slate-600">{{ $desc }}</p>
    <div class="mt-8 grid gap-4 sm:grid-cols-2">
        @foreach ([
            ['GSTR-1', 'Outward supplies', 'Monthly return of all sales invoices issued.'],
            ['GSTR-2A / 2B', 'Auto-drafted ITC', 'ITC statement based on your suppliers\' GSTR-1 filings.'],
            ['GSTR-3B', 'Summary return', 'Monthly summary of outward supplies, ITC and tax payment.'],
            ['GSTR-9', 'Annual return', 'Consolidated annual return for regular taxpayers.'],
            ['GSTR-4 (CMP-08)', 'Composition', 'Quarterly challan for composition scheme taxpayers.'],
            ['GSTR-7', 'TDS return', 'Return for GST TDS deductors.'],
        ] as [$title, $subtitle, $desc2])
        <div class="rounded-xl border border-slate-200 p-5 hover:border-indigo-300 transition">
            <div class="flex items-start justify-between">
                <div><h3 class="font-semibold text-slate-900">{{ $title }}</h3><p class="text-xs text-indigo-600">{{ $subtitle }}</p></div>
                <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            </div>
            <p class="mt-2 text-sm text-slate-600">{{ $desc2 }}</p>
            <a href="https://www.gst.gov.in" target="_blank" rel="noopener" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:underline">Download from gst.gov.in →</a>
        </div>
        @endforeach
    </div>
    <div class="mt-8 rounded-xl bg-indigo-600 p-6 text-center">
        <p class="text-lg font-semibold text-white">File GST returns without the hassle</p>
        <a href="{{ route('register') }}" class="mt-4 inline-block rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-indigo-700">Create free account</a>
    </div>
</section>
@endsection