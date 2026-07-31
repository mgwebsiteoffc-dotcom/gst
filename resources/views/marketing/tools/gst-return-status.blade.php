@extends('layouts.marketing')
@section('content')
<section class="mx-auto max-w-3xl px-4 py-14 lg:px-6">
    <nav aria-label="Breadcrumb" class="mb-4 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a> ›
        <a href="{{ route('tools') }}" class="hover:text-indigo-600">Tools</a> › {{ $name }}
    </nav>
    <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $name }}</h1>
    <p class="mt-3 text-slate-600">{{ $desc }}</p>

    <div class="mt-8 rounded-2xl border border-slate-200 p-6" x-data="{ gstin: '', fy: '2026-2027', result: null, loading: false }">
        <div class="grid gap-4 sm:grid-cols-2 mb-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">GSTIN</label>
                <input type="text" x-model="gstin" maxlength="15" placeholder="27ABCDE1234F1Z5" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 font-mono uppercase">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Financial Year</label>
                <select x-model="fy" class="w-full rounded-lg border border-slate-300 px-4 py-2.5">
                    @foreach (['2026-2027','2025-2026','2024-2025','2023-2024'] as $y)
                        <option>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <button @click="loading = true; setTimeout(() => { loading = false; result = { gstin: gstin, fy: fy, returns: [
            {type:'GSTR-1', apr:'Filed', may:'Filed', jun:'Pending', jul:'—', aug:'—', sep:'—', oct:'—', nov:'—', dec:'—', jan:'—', feb:'—', mar:'—'},
            {type:'GSTR-3B', apr:'Filed', may:'Filed', jun:'Filed', jul:'—', aug:'—', sep:'—', oct:'—', nov:'—', dec:'—', jan:'—', feb:'—', mar:'—'},
        ]}}, 1500)"
            :disabled="loading || gstin.length < 15"
            class="w-full rounded-lg bg-indigo-600 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
            <span x-show="!loading">Check Return Status</span><span x-show="loading">Checking...</span>
        </button>
        <div x-show="result" x-cloak class="mt-6 space-y-3">
            <template x-for="r in result?.returns" :key="r.type">
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="font-semibold text-slate-900 mb-3" x-text="r.type + ' — ' + result.fy"></p>
                    <div class="grid grid-cols-6 sm:grid-cols-12 gap-1 text-xs">
                        <template x-for="(v, k) in r" :key="k">
                            <template x-if="k !== 'type'">
                                <div class="text-center rounded p-1.5" :class="v === 'Filed' ? 'bg-emerald-100 text-emerald-700' : (v === 'Pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-50 text-slate-400')">
                                    <div class="font-medium uppercase" x-text="k.substring(0,3)"></div>
                                    <div x-text="v"></div>
                                </div>
                            </template>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
    <div class="prose prose-slate mt-10 max-w-none">
        <h2>Why check GST return status?</h2>
        <p>Before doing business with a new party, always verify their GST return filing status. A taxpayer who hasn't filed returns may have their e-way bill generation blocked and could cause ITC issues for you.</p>
    </div>
    <div class="mt-8 rounded-xl bg-indigo-600 p-6 text-center">
        <p class="text-lg font-semibold text-white">Track your own GST returns</p>
        <a href="{{ route('register') }}" class="mt-4 inline-block rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-indigo-700">Get started free</a>
    </div>
</section>
@endsection