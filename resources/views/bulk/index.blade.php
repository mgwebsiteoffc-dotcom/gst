@extends('layouts.app')
@section('title', 'Bulk Import & Export')
@section('content')
<div class="max-w-full overflow-x-hidden">
    <x-page title="Bulk Import & Export" subtitle="Import and export data using Excel spreadsheets." />
    <div class="space-y-5">
        @foreach ($definitions as $entity => $def)
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                {{-- Header --}}
                <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-4 py-3 sm:px-5">
                    <span class="text-xl">{{ $def['icon'] }}</span>
                    <h2 class="font-medium text-slate-900">{{ $def['label'] }}</h2>
                    <x-badge tone="slate">{{ number_format($counts[$entity] ?? 0) }} records</x-badge>
                </div>
                {{-- Body --}}
                <div class="p-4 sm:p-5">
                    <div class="grid gap-4 sm:grid-cols-3">
                        {{-- Sample --}}
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 sm:p-4">
                            <p class="text-sm font-medium text-slate-800">📥 Sample File</p>
                            <p class="mt-1 text-xs text-slate-500">Pre-formatted Excel with example data.</p>
                            <a href="{{ route('bulk.sample', $entity) }}"
                               class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">
                                Download Sample
                            </a>
                        </div>
                        {{-- Import --}}
                        <div class="rounded-lg border border-slate-200 p-3 sm:p-4">
                            <p class="text-sm font-medium text-slate-800">📤 Import</p>
                            <p class="mt-1 text-xs text-slate-500">Upload .xlsx to preview & import.</p>
                            <form method="POST" action="{{ route('bulk.preview', $entity) }}" enctype="multipart/form-data" class="mt-3" x-data="{ file: null }">
                                @csrf
                                <label class="block cursor-pointer">
                                    <div class="flex items-center gap-2 rounded-lg border border-dashed border-slate-300 px-2 py-2 text-xs hover:border-indigo-400">
                                        <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z"/></svg>
                                        <span x-text="file ? file.name : 'Choose file...'" class="truncate text-slate-600"></span>
                                    </div>
                                    <input type="file" name="file" accept=".xlsx,.xls,.csv" class="sr-only" required @change="file = $event.target.files[0]">
                                </label>
                                <x-btn variant="primary" size="sm" type="submit" class="mt-2 w-full" ::disabled="!file">Preview & Import</x-btn>
                            </form>
                        </div>
                        {{-- Export --}}
                        <div class="rounded-lg border {{ ($counts[$entity] ?? 0) > 0 ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50' }} p-3 sm:p-4">
                            <p class="text-sm font-medium {{ ($counts[$entity] ?? 0) > 0 ? 'text-emerald-800' : 'text-slate-500' }}">📊 Export</p>
                            @if (($counts[$entity] ?? 0) > 0)
                                <p class="mt-1 text-xs text-emerald-600">{{ number_format($counts[$entity]) }} records.</p>
                                <a href="{{ route('bulk.export', $entity) }}"
                                   class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700">
                                    Export .xlsx
                                </a>
                            @else
                                <p class="mt-1 text-xs text-slate-400">No records yet.</p>
                            @endif
                        </div>
                    </div>
                    {{-- Column Reference --}}
                    <div x-data="{ show: false }" class="mt-4 border-t border-slate-100 pt-3">
                        <button @click="show = !show" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                            <span x-show="!show">Show {{ count($def['columns']) }} columns ↓</span>
                            <span x-show="show">Hide columns ↑</span>
                        </button>
                        <div x-show="show" x-cloak x-transition class="mt-2 max-h-48 overflow-y-auto">
                            <div class="grid gap-x-4 gap-y-1 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($def['columns'] as $key => $col)
                                    <div class="flex items-baseline gap-1 text-xs py-0.5 min-w-0">
                                        <span class="font-medium text-slate-700 shrink-0">
                                            {{ $col['label'] }}@if ($col['required']) <span class="text-rose-500">*</span> @endif
                                        </span>
                                        <span class="text-slate-400 truncate">e.g. {{ $col['example'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    {{-- How it works --}}
    <div class="mt-6 rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="border-b border-slate-100 px-4 py-3 sm:px-5">
            <h2 class="font-medium text-slate-900">How it works</h2>
        </div>
        <div class="p-4 sm:p-5">
            <div class="grid gap-4 text-sm text-slate-600 sm:grid-cols-3">
                <div class="flex gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">1</span>
                    <div><p class="font-medium text-slate-800">Download Sample</p><p class="text-xs text-slate-500">Pre-formatted Excel with correct columns.</p></div>
                </div>
                <div class="flex gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">2</span>
                    <div><p class="font-medium text-slate-800">Fill Your Data</p><p class="text-xs text-slate-500">Replace example data. Required fields marked *.</p></div>
                </div>
                <div class="flex gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">3</span>
                    <div><p class="font-medium text-slate-800">Upload & Preview</p><p class="text-xs text-slate-500">Upload, review, fix errors, then import.</p></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection