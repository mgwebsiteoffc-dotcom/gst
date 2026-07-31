@extends('layouts.app')
@section('title', 'Preview Import')
@section('content')
<div class="max-w-full overflow-x-hidden">
    <x-page title="Preview Import: {{ $definition['label'] }}" subtitle="Review data before importing." :back="route('bulk.index')" />
    {{-- Summary --}}
    <div class="mb-5 grid gap-3 grid-cols-2 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
            <div class="text-xs uppercase tracking-wide text-slate-500">Total</div>
            <div class="mt-1 text-xl font-bold text-slate-900">{{ $result['total'] }}</div>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-center">
            <div class="text-xs uppercase tracking-wide text-emerald-600">Valid</div>
            <div class="mt-1 text-xl font-bold text-emerald-700">{{ $result['valid'] }}</div>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-center">
            <div class="text-xs uppercase tracking-wide text-rose-600">Errors</div>
            <div class="mt-1 text-xl font-bold text-rose-700">{{ $result['invalid'] }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
            <div class="text-xs uppercase tracking-wide text-slate-500">Entity</div>
            <div class="mt-1 text-sm font-bold text-slate-900 truncate">{{ $definition['icon'] }} {{ $definition['label'] }}</div>
        </div>
    </div>
    {{-- Validation Errors --}}
    @if (!empty($result['errors']))
        <div x-data="{ show: false }" class="mb-5">
            <button @click="show = !show" class="w-full rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-left">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-rose-800">⚠️ {{ count($result['errors']) }} error(s) — these rows will be skipped</span>
                    <svg class="h-4 w-4 shrink-0 text-rose-400 transition" :class="show && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="m19 9-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="show" x-cloak x-transition class="mt-1 max-h-48 overflow-y-auto rounded-xl border border-rose-200 bg-white p-3 space-y-1">
                @foreach ($result['errors'] as $error)
                    <p class="text-xs text-rose-700 break-all">{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif
    {{-- Data Preview — card-based, no horizontal scroll --}}
    <div class="space-y-2">
        @foreach (array_slice($result['rows'], 0, 50) as $row)
            @php
                $data = $row['data'];
                $title = $data['name'] ?? $data['number'] ?? $data['party_name'] ?? ('Row ' . $row['line']);
                $summaryParts = [];
                foreach (['email', 'phone', 'gstin', 'type', 'doc_type', 'issue_date', 'grand_total', 'sale_price', 'sku'] as $sf) {
                    if (!empty($data[$sf])) $summaryParts[$sf] = $data[$sf];
                }
            @endphp
            <div x-data="{ open: false }"
                 class="rounded-xl border overflow-hidden {{ $row['status'] === 'invalid' ? 'border-rose-200 bg-rose-50/30' : 'border-slate-200 bg-white' }}">
                {{-- Header --}}
                <button @click="open = !open" class="flex w-full items-start gap-2 px-3 py-2.5 text-left sm:px-4 sm:py-3">
                    @if ($row['status'] === 'valid')
                        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs text-emerald-700">✓</span>
                    @else
                        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-rose-100 text-xs text-rose-700">✗</span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-medium text-slate-900 truncate max-w-full">{{ $title }}</span>
                            <span class="text-xs text-slate-400 shrink-0">#{{ $row['line'] }}</span>
                        </div>
                        @if (!empty($summaryParts))
                            <div class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-slate-500">
                                @foreach (array_slice($summaryParts, 0, 4, true) as $sk => $sv)
                                    <span class="truncate max-w-[180px]"><strong class="text-slate-600">{{ ucfirst(str_replace('_', ' ', $sk)) }}:</strong> {{ Str::limit((string)$sv, 20) }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if ($row['status'] === 'invalid' && !empty($row['errors']))
                            <p class="mt-1 text-xs text-rose-600 break-all">{{ $row['errors'][0] }}</p>
                        @endif
                    </div>
                    <svg class="mt-1 h-4 w-4 shrink-0 text-slate-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="m19 9-7 7-7-7"/></svg>
                </button>
                {{-- Expanded details --}}
                <div x-show="open" x-cloak x-transition class="border-t border-slate-100 px-3 py-3 sm:px-4">
                    <div class="grid gap-x-4 gap-y-1 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($data as $key => $value)
                            @if ($value === null || $value === '') @continue @endif
                            <div class="flex gap-2 py-1 border-b border-slate-50 min-w-0">
                                <span class="w-28 sm:w-32 shrink-0 text-xs font-medium uppercase tracking-wide text-slate-400">
                                    {{ ucfirst(str_replace('_', ' ', $key)) }}
                                </span>
                                <span class="text-sm text-slate-800 break-all min-w-0">
                                    @if (is_bool($value))
                                        {{ $value ? 'Yes' : 'No' }}
                                    @else
                                        {{ $value }}
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @if (count($result['rows']) > 50)
        <p class="mt-3 text-center text-xs text-slate-400">Showing first 50 of {{ count($result['rows']) }} rows.</p>
    @endif
    {{-- Import Actions --}}
    @if ($result['valid'] > 0)
        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-medium text-slate-900">Import {{ $result['valid'] }} valid record(s)</p>
                    @if ($result['invalid'] > 0)
                        <p class="text-xs text-slate-500">{{ $result['invalid'] }} row(s) with errors will be skipped.</p>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-btn variant="secondary" :href="route('bulk.index')">Cancel</x-btn>
                    <form method="POST" action="{{ route('bulk.execute', $entity) }}">
                        @csrf
                        <input type="hidden" name="mode" value="import">
                        <x-btn variant="primary" type="submit">Import as New</x-btn>
                    </form>
                    <form method="POST" action="{{ route('bulk.execute', $entity) }}">
                        @csrf
                        <input type="hidden" name="mode" value="merge">
                        <x-btn variant="success" type="submit">Merge</x-btn>
                    </form>
                </div>
            </div>
        </div>
    @else
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-center">
            <p class="font-medium text-amber-800">No valid records to import</p>
            <p class="mt-1 text-sm text-amber-600">Fix the errors and re-upload.</p>
            <x-btn variant="secondary" :href="route('bulk.index')" class="mt-3">← Back</x-btn>
        </div>
    @endif
</div>
@endsection

