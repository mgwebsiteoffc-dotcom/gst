@extends('layouts.marketing')
@section('content')
<section class="mx-auto max-w-3xl px-4 py-14 lg:px-6">
    <nav aria-label="Breadcrumb" class="mb-4 text-xs text-slate-500"><a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a> › <a href="{{ route('tools') }}" class="hover:text-indigo-600">Tools</a> › {{ $name }}</nav>
    <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $name }}</h1>
    <p class="mt-3 text-slate-600">{{ $desc }}</p>
    <div class="mt-8 rounded-2xl border border-slate-200 p-6" x-data="{ mode: 'name', query: '', result: null, loading: false }">
        <div class="flex gap-4 mb-4">
            <label class="flex items-center gap-2 cursor-pointer"><input type="radio" value="name" x-model="mode" class="text-indigo-600"> By Company Name</label>
            <label class="flex items-center gap-2 cursor-pointer"><input type="radio" value="cin" x-model="mode" class="text-indigo-600"> By CIN</label>
        </div>
        <div class="flex gap-2 mb-4">
            <input type="text" x-model="query" :placeholder="mode === 'name' ? 'e.g. Tata Consultancy Services' : 'e.g. U72200MH1995PLC092070'" class="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm" :class="mode === 'cin' ? 'font-mono uppercase' : ''">
            <button @click="loading = true; setTimeout(() => { loading = false; result = { name: 'Sample Company Pvt Ltd', cin: 'U72200MH1995PLC092070', regDate: '15-Mar-1995', status: 'Active', class: 'Private', category: 'Company limited by Shares', authorizedCapital: '5,00,00,000', paidUpCapital: '3,50,00,000', roc: 'RoC Mumbai', address: 'Bandra Kurla Complex, Mumbai - 400051', directors: ['Rajesh Kumar', 'Priya Sharma', 'Anil Mehta'] } }, 1500)"
                    :disabled="loading || query.length < 3" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                <span x-show="!loading">Search</span><span x-show="loading">Searching...</span>
            </button>
        </div>
        <div x-show="result" x-cloak class="rounded-lg border border-slate-200 p-5">
            <h3 class="font-semibold text-lg text-slate-900" x-text="result?.name"></h3>
            <div class="mt-1 text-xs"><span class="rounded bg-emerald-50 px-2 py-0.5 text-emerald-700 font-medium" x-text="result?.status"></span></div>
            <div class="mt-4 grid gap-2 text-sm">
                <div class="flex justify-between border-b border-slate-100 py-2"><span class="text-slate-500">CIN</span><span class="font-mono text-xs" x-text="result?.cin"></span></div>
                <div class="flex justify-between border-b border-slate-100 py-2"><span class="text-slate-500">Incorporated</span><span x-text="result?.regDate"></span></div>
                <div class="flex justify-between border-b border-slate-100 py-2"><span class="text-slate-500">Class</span><span x-text="result?.class"></span></div>
                <div class="flex justify-between border-b border-slate-100 py-2"><span class="text-slate-500">Authorized Capital</span><span>₹<span x-text="result?.authorizedCapital"></span></span></div>
                <div class="flex justify-between border-b border-slate-100 py-2"><span class="text-slate-500">Paid-up Capital</span><span>₹<span x-text="result?.paidUpCapital"></span></span></div>
                <div class="flex justify-between border-b border-slate-100 py-2"><span class="text-slate-500">ROC</span><span x-text="result?.roc"></span></div>
                <div class="flex justify-between py-2"><span class="text-slate-500">Address</span><span x-text="result?.address"></span></div>
            </div>
            <div class="mt-4"><p class="text-sm font-medium text-slate-700 mb-2">Directors</p>
                <div class="flex flex-wrap gap-2"><template x-for="d in result?.directors" :key="d"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700" x-text="d"></span></template></div>
            </div>
        </div>
    </div>
    <div class="prose prose-slate mt-10 max-w-none">
        <h2>About MCA Company Search</h2>
        <p>The Ministry of Corporate Affairs (MCA) maintains a registry of all companies incorporated in India. You can search for any company by name or Corporate Identity Number (CIN) to find registration details, directors, authorized capital, and compliance status.</p>
    </div>
</section>
@endsection