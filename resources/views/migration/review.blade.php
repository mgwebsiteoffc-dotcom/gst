@extends('layouts.app')
@section('title', 'Review Import')

@section('content')
<x-page title="Review Backup" subtitle="Configure how each section should be imported." :back="route('migration.index')">
    <x-badge tone="slate">{{ $job->filename }}</x-badge>
</x-page>

{{-- Backup Info --}}
<div class="mb-6 grid gap-4 sm:grid-cols-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs text-slate-500">Backup Version</p>
        <p class="mt-1 text-lg font-semibold text-slate-900">{{ $job->backup_version ?? 'Unknown' }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs text-slate-500">Created Date</p>
        <p class="mt-1 text-lg font-semibold text-slate-900">
            {{ $job->backup_created_at ? $job->backup_created_at->format('d M Y') : 'Unknown' }}
        </p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs text-slate-500">Sections Detected</p>
        <p class="mt-1 text-lg font-semibold text-slate-900">{{ count($job->sections ?? []) }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs text-slate-500">Total Records</p>
        <p class="mt-1 text-lg font-semibold text-slate-900">
            {{ number_format(array_sum(array_column($job->sections ?? [], 'count'))) }}
        </p>
    </div>
</div>

{{-- Validation Warnings / Errors --}}
@if (($validation['warning_count'] ?? 0) > 0 || ($validation['error_count'] ?? 0) > 0)
    <div x-data="{ open: false }" class="mb-6">
        <button @click="open = !open"
                class="flex w-full items-center justify-between rounded-xl border px-5 py-3 text-left transition
                {{ ($validation['error_count'] ?? 0) > 0 ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50' }}">
            <div class="flex items-center gap-3">
                @if (($validation['error_count'] ?? 0) > 0)
                    <svg class="h-5 w-5 text-rose-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                    <span class="text-sm font-medium text-rose-800">
                        {{ $validation['error_count'] }} error(s) and {{ $validation['warning_count'] }} warning(s) found
                    </span>
                @else
                    <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                    <span class="text-sm font-medium text-amber-800">
                        {{ $validation['warning_count'] }} warning(s) found — import can continue
                    </span>
                @endif
            </div>
            <svg class="h-5 w-5 text-slate-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" d="m19 9-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="open" x-cloak x-transition class="mt-2 max-h-72 overflow-y-auto rounded-xl border border-slate-200 bg-white p-4">
            @foreach (($validation['errors'] ?? []) as $err)
                <div class="flex items-start gap-2 border-b border-slate-100 py-2 last:border-0">
                    <span class="mt-0.5 inline-block h-2 w-2 shrink-0 rounded-full bg-rose-500"></span>
                    <p class="text-sm text-slate-700">
                        <span class="font-medium text-rose-700">{{ $err['section'] ?? 'general' }}</span>
                        — {{ $err['message'] }}
                    </p>
                </div>
            @endforeach
            @foreach (($validation['warnings'] ?? []) as $warn)
                <div class="flex items-start gap-2 border-b border-slate-100 py-2 last:border-0">
                    <span class="mt-0.5 inline-block h-2 w-2 shrink-0 rounded-full bg-amber-500"></span>
                    <p class="text-sm text-slate-700">
                        <span class="font-medium text-amber-700">{{ $warn['section'] ?? 'general' }}</span>
                        @if (isset($warn['record_id']))
                            <span class="text-xs text-slate-400">#{{ $warn['record_id'] }}</span>
                        @endif
                        — {{ $warn['message'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- Section Mapping --}}
<form action="{{ route('migration.mapping', $job) }}" method="POST">
    @csrf
    @method('PUT')

    <x-card title="Section Mapping" subtitle="Choose how each data section should be imported into the new system.">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                        <th class="pb-3 pr-4">JSON Section</th>
                        <th class="pb-3 pr-4">Destination Table</th>
                        <th class="pb-3 pr-4 text-center">Records</th>
                        <th class="pb-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($mapping as $key => $info)
                        <tr class="group">
                            <td class="py-3 pr-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-xs font-semibold text-indigo-700">
                                        {{ strtoupper(substr($info['label'], 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $info['label'] }}</p>
                                        <p class="text-xs text-slate-400">{{ $key }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 pr-4">
                                <code class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $info['destination_table'] }}</code>
                            </td>
                            <td class="py-3 pr-4 text-center">
                                <span class="inline-flex min-w-[3rem] items-center justify-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                    {{ number_format($info['count']) }}
                                </span>
                            </td>
                            <td class="py-3">
                                <div class="flex gap-1.5">
                                    @foreach ($actions as $actionKey => $actionLabel)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="actions[{{ $key }}]" value="{{ $actionKey }}"
                                                   class="peer sr-only"
                                                   {{ ($info['action'] ?? 'import') === $actionKey ? 'checked' : '' }}>
                                            <span class="inline-flex items-center rounded-lg border px-3 py-1.5 text-xs font-medium transition
                                                peer-checked:border-indigo-300 peer-checked:bg-indigo-50 peer-checked:text-indigo-700
                                                border-slate-200 text-slate-500 hover:border-slate-300 group-hover:bg-white">
                                                @if ($actionKey === 'import')
                                                    <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 4.5v15m0 0 6.75-6.75M12 19.5l-6.75-6.75"/></svg>
                                                @elseif ($actionKey === 'merge')
                                                    <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                                                @else
                                                    <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                @endif
                                                {{ $actionLabel }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Action Legend --}}
    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
        <div class="grid gap-3 text-xs text-slate-600 sm:grid-cols-3">
            <div class="flex items-start gap-2">
                <span class="mt-0.5 inline-block h-2.5 w-2.5 shrink-0 rounded-full bg-indigo-500"></span>
                <div>
                    <span class="font-medium text-slate-800">Import as New</span> — Adds records. Skips if a matching record already exists.
                </div>
            </div>
            <div class="flex items-start gap-2">
                <span class="mt-0.5 inline-block h-2.5 w-2.5 shrink-0 rounded-full bg-blue-500"></span>
                <div>
                    <span class="font-medium text-slate-800">Merge with Existing</span> — Updates matching records, creates new ones.
                </div>
            </div>
            <div class="flex items-start gap-2">
                <span class="mt-0.5 inline-block h-2.5 w-2.5 shrink-0 rounded-full bg-slate-400"></span>
                <div>
                    <span class="font-medium text-slate-800">Skip</span> — Does not import this section at all.
                </div>
            </div>
        </div>
    </div>

    {{-- Buttons --}}
    <div class="mt-6 flex items-center justify-between">
        <x-btn variant="secondary" :href="route('migration.index')">
            &larr; Back to Upload
        </x-btn>

        <div class="flex gap-3">
            <x-btn variant="secondary" type="submit">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
                Save &amp; Re-validate
            </x-btn>

            <x-btn variant="primary" type="button"
                   onclick="if(confirm('Start the import? This will process all selected sections.')) { document.getElementById('execute-form').submit(); }">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/></svg>
                Start Import
            </x-btn>
        </div>
    </div>
</form>

{{-- Hidden form for executing --}}
<form id="execute-form" action="{{ route('migration.execute', $job) }}" method="POST" class="hidden">
    @csrf
</form>
@endsection
