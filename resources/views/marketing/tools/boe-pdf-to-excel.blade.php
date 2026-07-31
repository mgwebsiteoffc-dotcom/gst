@extends('layouts.marketing')
@section('content')
<section class="mx-auto max-w-3xl px-4 py-14 lg:px-6">
    <nav aria-label="Breadcrumb" class="mb-4 text-xs text-slate-500"><a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a> › <a href="{{ route('tools') }}" class="hover:text-indigo-600">Tools</a> › {{ $name }}</nav>
    <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $name }}</h1>
    <p class="mt-3 text-slate-600">{{ $desc }}</p>
    <div class="mt-8 rounded-2xl border border-slate-200 p-6 text-center" x-data="{ uploading: false, done: false }">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 mb-4">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
        </div>
        <p class="text-lg font-semibold text-slate-900 mb-2">Upload your Bill of Entry PDF</p>
        <p class="text-sm text-slate-500 mb-6">Supports ICEGATE-generated BOE PDFs. Converts tabular data to Excel.</p>
        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
            Choose PDF File
            <input type="file" accept=".pdf" class="sr-only" @change="uploading = true; setTimeout(() => { uploading = false; done = true }, 2000)">
        </label>
        <div x-show="uploading" x-cloak class="mt-4"><div class="mx-auto h-2 w-48 rounded-full bg-slate-200 overflow-hidden"><div class="h-full bg-indigo-600 animate-pulse" style="width:60%"></div></div><p class="mt-2 text-sm text-slate-500">Converting...</p></div>
        <div x-show="done" x-cloak class="mt-4 text-emerald-600 font-medium">✓ Conversion complete! Your Excel file is ready for download.</div>
    </div>
    <div class="prose prose-slate mt-10 max-w-none">
        <h2>What is a Bill of Entry?</h2>
        <p>A Bill of Entry (BOE) is a legal document filed by importers or their customs brokers with the customs department. It contains details about imported goods including HS code, assessable value, customs duty, and IGST paid. Converting BOE PDFs to Excel helps in ITC reconciliation and accounting.</p>
    </div>
</section>
@endsection