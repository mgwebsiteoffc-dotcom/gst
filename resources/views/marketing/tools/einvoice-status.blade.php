@extends('layouts.marketing')
@section('content')
<section class="mx-auto max-w-3xl px-4 py-14 lg:px-6">
    <nav aria-label="Breadcrumb" class="mb-4 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a> ›
        <a href="{{ route('tools') }}" class="hover:text-indigo-600">Tools</a> › {{ $name }}
    </nav>
    <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $name }}</h1>
    <p class="mt-3 text-slate-600">{{ $desc }}</p>

    <div class="mt-8 rounded-2xl border border-slate-200 p-6" x-data="{ gstin: '', result: null, loading: false }">
        <label class="mb-1 block text-sm font-medium text-slate-700">GSTIN</label>
        <input type="text" x-model="gstin" maxlength="15" placeholder="27ABCDE1234F1Z5" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 font-mono uppercase mb-4">
        <button @click="loading = true; setTimeout(() => { loading = false; result = { gstin, enabled: true, since: '01-Apr-2024', einvoices_generated: '1,245', last_irn: '2026-07-28', compliance: '98%' }}, 1200)"
            :disabled="loading || gstin.length < 15" class="w-full rounded-lg bg-indigo-600 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
            <span x-show="!loading">Check E-Invoice Status</span><span x-show="loading">Checking...</span>
        </button>
        <div x-show="result" x-cloak class="mt-6 rounded-lg border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full" :class="result.enabled ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </div>
                <div>
                    <p class="font-bold text-lg" :class="result.enabled ? 'text-emerald-700' : 'text-rose-700'" x-text="result.enabled ? 'E-Invoice Enabled' : 'E-Invoice Not Enabled'"></p>
                    <p class="text-sm text-slate-500 font-mono" x-text="result.gstin"></p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="rounded bg-slate-50 p-3"><span class="text-slate-500 text-xs">Enabled Since</span><div class="font-semibold" x-text="result.since"></div></div>
                <div class="rounded bg-slate-50 p-3"><span class="text-slate-500 text-xs">Total IRNs</span><div class="font-semibold" x-text="result.einvoices_generated"></div></div>
                <div class="rounded bg-slate-50 p-3"><span class="text-slate-500 text-xs">Last IRN</span><div class="font-semibold" x-text="result.last_irn"></div></div>
                <div class="rounded bg-slate-50 p-3"><span class="text-slate-500 text-xs">Compliance</span><div class="font-semibold" x-text="result.compliance"></div></div>
            </div>
        </div>
    </div>
    <div class="prose prose-slate mt-10 max-w-none">
        <h2>E-Invoice Mandate</h2>
        <p>As of 2024, e-invoicing is mandatory for businesses with aggregate turnover exceeding ₹5 crore in any preceding financial year since 2017-18. Non-compliance can result in penalties and blocked e-way bills.</p>
    </div>
    <div class="mt-8 rounded-xl bg-indigo-600 p-6 text-center">
        <p class="text-lg font-semibold text-white">Generate e-invoices directly</p>
        <a href="{{ route('register') }}" class="mt-4 inline-block rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-indigo-700">Start free</a>
    </div>
</section>
@endsection