@extends('layouts.marketing')
@section('content')
<section class="mx-auto max-w-3xl px-4 py-14 lg:px-6">
    <nav aria-label="Breadcrumb" class="mb-4 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a> ›
        <a href="{{ route('tools') }}" class="hover:text-indigo-600">Tools</a> › {{ $name }}
    </nav>
    <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $name }}</h1>
    <p class="mt-3 text-slate-600">{{ $desc }}</p>

    <div class="mt-8 rounded-2xl border border-slate-200 p-6" x-data="{ q: '', results: [] }">
        <input type="text" x-model="q" placeholder="Search by topic, section, or keyword..." class="w-full rounded-lg border border-slate-300 px-4 py-2.5" @input="results = q.length > 2 ? [
            {title:'ITC on construction of immovable property', section:'Section 17(5)(c)', court:'Supreme Court', year:'2024', summary:'ITC not available on goods/services used for construction of immovable property on own account.'},
            {title:'Refund of unutilized ITC on inverted duty structure', section:'Section 54(3)', court:'Supreme Court', year:'2024', summary:'Refund permissible where input tax rate exceeds output tax rate.'},
            {title:'Transitional credit under Section 140', section:'Section 140', court:'Supreme Court', year:'2023', summary:'Transitional credit is a vested right, time limit for claiming is directory.'},
            {title:'E-way bill penalty without intent to evade', section:'Section 129', court:'High Court', year:'2023', summary:'Minor discrepancies in e-way bill should not attract penalty without evidence of tax evasion.'},
            {title:'IGST on ocean freight — reverse charge', section:'Notification 10/2017', court:'Supreme Court', year:'2022', summary:'IGST on ocean freight under reverse charge held ultra vires.'},
        ].filter(r => r.title.toLowerCase().includes(q.toLowerCase()) || r.section.toLowerCase().includes(q.toLowerCase())) : []">
        <p class="mt-2 text-xs text-slate-400">Type at least 3 characters to search.</p>
        <div class="mt-4 space-y-3">
            <template x-for="r in results" :key="r.title">
                <div class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold text-slate-900" x-text="r.title"></h3>
                        <span class="shrink-0 rounded bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700" x-text="r.year"></span>
                    </div>
                    <div class="mt-1 text-xs text-slate-500"><span class="font-mono" x-text="r.section"></span> · <span x-text="r.court"></span></div>
                    <p class="mt-2 text-sm text-slate-600" x-text="r.summary"></p>
                </div>
            </template>
        </div>
    </div>
    <div class="prose prose-slate mt-10 max-w-none">
        <h2>Important GST Case Laws</h2>
        <p>GST case laws are evolving rapidly. The Supreme Court and various High Courts have delivered landmark judgements on ITC eligibility, refund claims, transitional credit, e-way bill penalties, and more. Stay updated with the latest rulings.</p>
    </div>
</section>
@endsection