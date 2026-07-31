@extends('layouts.app')
@section('title', 'Imported Data')
@section('content')
<x-page title="Imported Data" subtitle="Browse and search all data from your imported backups." :back="route('migration.index')">
    <x-btn variant="secondary" :href="route('migration.index')">
        ← Back to Migration
    </x-btn>
</x-page>
{{-- Filters --}}
<x-card class="mb-5">
    <form method="GET" action="{{ route('migration.data') }}" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Section</label>
            <select name="section" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">All Sections</option>
                @foreach ($sections as $section => $count)
                    <option value="{{ $section }}" @selected($currentSection === $section)>
                        {{ ucfirst(str_replace('_', ' ', $section)) }} ({{ $count }})
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Status</label>
            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">All</option>
                @foreach (['mapped' => 'Mapped', 'imported' => 'Imported', 'skipped' => 'Skipped', 'failed' => 'Failed'] as $val => $label)
                    <option value="{{ $val }}" @selected($currentStatus === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="mb-1 block text-xs font-medium text-slate-600">Search</label>
            <input type="text" name="q" value="{{ $search }}" placeholder="Search all fields..."
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <x-btn variant="primary" type="submit">Filter</x-btn>
        @if ($currentSection || $search || $currentStatus)
            <x-btn variant="ghost" :href="route('migration.data')">Clear</x-btn>
        @endif
    </form>
</x-card>
{{-- Results --}}
@if ($records->isEmpty())
    <x-card>
        <x-empty title="No records found"
                 message="{{ $search ? 'Try a different search term.' : 'Import data first from the Migration page.' }}" />
    </x-card>
@else
    <div class="space-y-4">
        @foreach ($records as $record)
            @php
                $data = $record->data ?? [];
                // Get a display title from common name fields
                $title = $data['name'] ?? $data['client_name'] ?? $data['number'] ?? $data['title'] ?? $data['KEY'] ?? ('Record #' . ($record->legacy_id ?? $record->id));
                // Separate important fields from the rest
                $priorityKeys = ['id', 'name', 'client_name', 'number', 'email', 'phone', 'gstin', 'gst_number',
                    'issue_date', 'pay_date', 'date', 'amount', 'total_all', 'price', 'status',
                    'type', 'client_id', 'invoice_id', 'product_id', 'payment_id', 'client_billing_city',
                    'client_billing_address', 'hsn', 'sac', 'sku', 'tax_id', 'description'];
                $priorityFields = [];
                $otherFields = [];
                foreach ($data as $key => $value) {
                    if (is_array($value)) $value = json_encode($value);
                    if ($value === null || $value === '') continue;
                    if (in_array($key, $priorityKeys)) {
                        $priorityFields[$key] = $value;
                    } else {
                        $otherFields[$key] = $value;
                    }
                }
            @endphp
            <x-card x-data="{ expanded: false }" class="!p-0">
                {{-- Header row --}}
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <button @click="expanded = !expanded" class="shrink-0 text-indigo-600 hover:text-indigo-800">
                            <svg class="h-5 w-5 transition" :class="expanded && 'rotate-90'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" d="m8 4 8 8-8 8"/>
                            </svg>
                        </button>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-medium text-slate-900 truncate">{{ $title }}</span>
                                <x-badge tone="indigo">{{ ucfirst(str_replace('_', ' ', $record->section)) }}</x-badge>
                                @if ($record->status === 'mapped')
                                    <x-badge tone="emerald">Mapped</x-badge>
                                @elseif ($record->status === 'imported')
                                    <x-badge tone="blue">Imported</x-badge>
                                @elseif ($record->status === 'skipped')
                                    <x-badge tone="amber">Skipped</x-badge>
                                @elseif ($record->status === 'failed')
                                    <x-badge tone="rose">Failed</x-badge>
                                @endif
                            </div>
                            <div class="flex gap-3 text-xs text-slate-400 mt-0.5">
                                @if ($record->legacy_id) <span>ID: {{ $record->legacy_id }}</span> @endif
                                @if ($record->source_file) <span>{{ $record->source_file }}</span> @endif
                                <span>{{ $record->created_at->diffForHumans() }}</span>
                                @if ($record->notes) <span class="{{ $record->status === 'failed' ? 'text-rose-500' : 'text-slate-400' }}">{{ Str::limit($record->notes, 60) }}</span> @endif
                            </div>
                        </div>
                    </div>
                    {{-- Quick preview: show first 3 priority fields inline --}}
                    <div class="hidden sm:flex gap-4 text-xs text-slate-500 shrink-0">
                        @foreach (array_slice($priorityFields, 0, 4, true) as $k => $v)
                            <span><strong class="text-slate-600">{{ ucfirst(str_replace('_', ' ', $k)) }}:</strong> {{ Str::limit($v, 25) }}</span>
                        @endforeach
                    </div>
                </div>
                {{-- Expanded detail --}}
                <div x-show="expanded" x-cloak x-transition class="px-5 py-4">
                    {{-- Priority fields as a nice grid --}}
                    @if (!empty($priorityFields))
                        <div class="grid gap-x-6 gap-y-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($priorityFields as $key => $value)
                                <div class="flex gap-2 border-b border-slate-50 py-1.5">
                                    <span class="w-36 shrink-0 text-xs font-medium uppercase tracking-wide text-slate-400">
                                        {{ ucfirst(str_replace('_', ' ', $key)) }}
                                    </span>
                                    <span class="text-sm text-slate-800 break-all">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    {{-- Other fields --}}
                    @if (!empty($otherFields))
                        <div class="mt-4">
                            <button @click="showAll = !showAll" x-data="{ showAll: false }"
                                    class="text-xs font-medium text-indigo-600 hover:text-indigo-800 mb-2">
                                <span x-show="!showAll">Show all {{ count($otherFields) }} other fields ↓</span>
                                <span x-show="showAll">Hide other fields ↑</span>
                            </button>
                            <div x-data="{ showAll: false }">
                                <div x-show="showAll" x-cloak x-transition
                                     class="grid gap-x-6 gap-y-2 sm:grid-cols-2 lg:grid-cols-3 border-t border-slate-200 pt-3">
                                    @foreach ($otherFields as $key => $value)
                                        <div class="flex gap-2 border-b border-slate-50 py-1.5">
                                            <span class="w-36 shrink-0 text-xs font-medium uppercase tracking-wide text-slate-400">
                                                {{ ucfirst(str_replace('_', ' ', $key)) }}
                                            </span>
                                            <span class="text-sm text-slate-600 break-all">{{ $value }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </x-card>
        @endforeach
    </div>
    {{-- Pagination --}}
    <div class="mt-5">
        {{ $records->links() }}
    </div>
@endif
@endsection