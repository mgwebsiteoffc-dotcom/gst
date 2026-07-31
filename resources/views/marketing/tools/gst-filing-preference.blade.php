@extends('layouts.marketing')
@section('content')
<section class="mx-auto max-w-3xl px-4 py-14 lg:px-6">
    <nav aria-label="Breadcrumb" class="mb-4 text-xs text-slate-500"><a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a> › <a href="{{ route('tools') }}" class="hover:text-indigo-600">Tools</a> › {{ $name }}</nav>
    <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $name }}</h1>
    <p class="mt-3 text-slate-600">{{ $desc }}</p>
    <div class="mt-8 rounded-2xl border border-slate-200 p-6" x-data="{ gstin: '', result: null, loading: false }">
        <label class="mb-1 block text-sm font-medium text-slate-700">GSTIN</label>
        <div class="flex gap-2 mb-4">
            <input type="text" x-model="gstin" maxlength="15" placeholder="27ABCDE1234F1Z5" class="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 font-mono uppercase">
            <button @click="loading = true; setTimeout(() => { loading = false; result = { gstin, scheme: 'QRMP', period: 'Quarterly', gstr1: 'IFF for M1 & M2, Regular for M3', gstr3b: 'Quarterly', autoDraft: 'Enabled', liability: 'Self-assessed' } }, 1200)"
                    :disabled="loading || gstin.length < 15" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                <span x-show="!loading">Check</span><span x-show="loading">Checking...</span>
            </button>
        </div>
        <div x-show="result" x-cloak class="rounded-lg border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                </div>
                <div>
                    <p class="font-bold text-lg text-slate-900" x-text="result.scheme + ' Scheme'"></p>
                    <p class="text-sm text-slate-500 font-mono" x-text="result.gstin"></p>
                </div>
            </div>
            <div class="grid gap-2 text-sm">
                <div class="flex justify-between border-b border-slate-100 py-2"><span class="text-slate-500">Filing Period</span><span class="font-semibold" x-text="result.period"></span></div>
                <div class="flex justify-between border-b border-slate-100 py-2"><span class="text-slate-500">GSTR-1</span><span x-text="result.gstr1"></span></div>
                <div class="flex justify-between border-b border-slate-100 py-2"><span class="text-slate-500">GSTR-3B</span><span x-text="result.gstr3b"></span></div>
                <div class="flex justify-between border-b border-slate-100 py-2"><span class="text-slate-500">Auto-drafted ITC</span><span x-text="result.autoDraft"></span></div>
                <div class="flex justify-between py-2"><span class="text-slate-500">Tax Liability</span><span x-text="result.liability"></span></div>
            </div>
        </div>
    </div>
    <div class="prose prose-slate mt-10 max-w-none">
        <h2>Monthly vs QRMP Filing</h2>
        <p><strong>Monthly:</strong> File GSTR-1 and GSTR-3B every month. Mandatory for taxpayers with aggregate turnover above ₹5 crore.</p>
        <p><strong>QRMP (Quarterly Return Monthly Payment):</strong> File GSTR-1 and GSTR-3B quarterly, but pay tax monthly. Available for taxpayers with turnover up to ₹5 crore. Use IFF (Invoice Furnishing Facility) to upload invoices for the first two months of each quarter.</p>
    </div>
</section>
@endsection