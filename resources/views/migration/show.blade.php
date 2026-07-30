@extends('layouts.app')
@section('title', 'Import ' . ($job->isCompleted() ? 'Results' : ($job->isImporting() ? 'In Progress' : 'Details')))

@section('content')
<x-page title="Import {{ $job->isCompleted() ? 'Complete' : ($job->isImporting() ? 'In Progress' : 'Details') }}"
       :subtitle="$job->filename"
       :back="route('migration.index')">
    @if ($job->isCompleted())
        <x-badge tone="emerald">Completed</x-badge>
    @elseif ($job->isFailed())
        <x-badge tone="rose">Failed</x-badge>
    @elseif ($job->isImporting())
        <x-badge tone="indigo">Importing</x-badge>
    @elseif ($job->isReady())
        <x-badge tone="amber">Ready</x-badge>
    @else
        <x-badge tone="slate">{{ ucfirst($job->status) }}</x-badge>
    @endif
</x-page>

{{-- Importing Progress --}}
@if ($job->isImporting())
    <div x-data="importProgress()" x-init="start()" class="mb-8">
        <x-card title="Importing Data" subtitle="Please keep this page open while your data is being imported.">
            <div class="py-6">
                {{-- Progress bar --}}
                <div class="mb-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-700" x-text="currentSection || 'Preparing...'"></span>
                        <span class="text-slate-500">
                            <span x-text="processed"></span> / <span x-text="total"></span> records
                        </span>
                    </div>

                    <div class="mt-2 h-3 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-indigo-600 transition-all duration-500"
                             :style="`width: ${percent}%`">
                        </div>
                    </div>

                    <p class="mt-2 text-center text-2xl font-bold text-indigo-700" x-text="`${percent}%`"></p>
                </div>

                {{-- Animated dots --}}
                <div class="flex justify-center gap-1.5">
                    <div class="h-2 w-2 animate-bounce rounded-full bg-indigo-400" style="animation-delay: 0ms"></div>
                    <div class="h-2 w-2 animate-bounce rounded-full bg-indigo-400" style="animation-delay: 150ms"></div>
                    <div class="h-2 w-2 animate-bounce rounded-full bg-indigo-400" style="animation-delay: 300ms"></div>
                </div>
            </div>
        </x-card>
    </div>

    <script>
    function importProgress() {
        return {
            currentSection: '',
            processed: 0,
            total: 0,
            percent: 0,
            status: 'importing',

            start() {
                this.poll();
            },

            async poll() {
                try {
                    const resp = await fetch('{{ route("migration.progress", $job) }}');
                    const data = await resp.json();

                    this.currentSection = data.current_section;
                    this.processed = data.processed;
                    this.total = data.total;
                    this.percent = data.percent;
                    this.status = data.status;

                    if (data.status === 'completed' || data.status === 'failed') {
                        window.location.reload();
                        return;
                    }
                } catch (e) {
                    console.error('Progress poll failed:', e);
                }

                setTimeout(() => this.poll(), 2000);
            }
        }
    }
    </script>
@endif

{{-- Ready State — show a prompt to start --}}
@if ($job->isReady())
    <x-card title="Ready to Import">
        <div class="flex items-center gap-4 py-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="font-medium text-slate-900">Your backup is ready to import</p>
                <p class="text-sm text-slate-500">Review the mapping and start the import when ready.</p>
            </div>
            <div class="flex gap-2">
                <x-btn variant="secondary" :href="route('migration.review', $job)">Review Mapping</x-btn>
                <form action="{{ route('migration.execute', $job) }}" method="POST">
                    @csrf
                    <x-btn variant="primary" type="submit">Start Import</x-btn>
                </form>
            </div>
        </div>
    </x-card>
@endif

{{-- Completed State — show summary --}}
@if ($job->isCompleted())
    @php
        $summary = $job->summary ?? [];
        $imported = $summary['imported'] ?? 0;
        $updated = $summary['updated'] ?? 0;
        $skipped = $summary['skipped'] ?? 0;
        $failed = $summary['failed'] ?? 0;
        $total = $imported + $updated + $skipped + $failed;
    @endphp

    {{-- Summary Cards --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5 text-center">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Total Processed</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($total) }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-center">
            <p class="text-xs font-medium uppercase tracking-wider text-emerald-600">Imported</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700">{{ number_format($imported) }}</p>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 text-center">
            <p class="text-xs font-medium uppercase tracking-wider text-blue-600">Updated</p>
            <p class="mt-2 text-3xl font-bold text-blue-700">{{ number_format($updated) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-center">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Skipped</p>
            <p class="mt-2 text-3xl font-bold text-slate-600">{{ number_format($skipped) }}</p>
        </div>
        <div class="rounded-xl border {{ $failed > 0 ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-slate-50' }} p-5 text-center">
            <p class="text-xs font-medium uppercase tracking-wider {{ $failed > 0 ? 'text-rose-600' : 'text-slate-500' }}">Failed</p>
            <p class="mt-2 text-3xl font-bold {{ $failed > 0 ? 'text-rose-700' : 'text-slate-600' }}">{{ number_format($failed) }}</p>
        </div>
    </div>

    {{-- Import Details --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Import Details">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Filename</dt>
                    <dd class="font-medium text-slate-900">{{ $job->filename }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Backup Version</dt>
                    <dd class="font-medium text-slate-900">{{ $job->backup_version ?? 'Unknown' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Imported By</dt>
                    <dd class="font-medium text-slate-900">{{ $job->user?->name ?? 'Unknown' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Started</dt>
                    <dd class="font-medium text-slate-900">{{ $job->started_at?->format('d M Y, H:i') ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Completed</dt>
                    <dd class="font-medium text-slate-900">{{ $job->completed_at?->format('d M Y, H:i') ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Duration</dt>
                    <dd class="font-medium text-slate-900">{{ $job->duration_formatted }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card title="Sections Imported">
            @if ($job->mapping)
                <div class="space-y-2">
                    @foreach ($job->mapping as $key => $info)
                        @if (($info['action'] ?? 'skip') !== 'skip')
                            <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-6 w-6 items-center justify-center rounded bg-indigo-50 text-xs font-semibold text-indigo-700">
                                        {{ strtoupper(substr($info['label'], 0, 1)) }}
                                    </span>
                                    <span class="text-sm font-medium text-slate-700">{{ $info['label'] }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-slate-500">{{ $info['count'] }} records</span>
                                    @if ($info['action'] === 'merge')
                                        <x-badge tone="blue">Merge</x-badge>
                                    @else
                                        <x-badge tone="emerald">Import</x-badge>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-500">No mapping details available.</p>
            @endif
        </x-card>
    </div>

    {{-- Error Details --}}
    @if ($job->errors && count($job->errors) > 0)
        <x-card title="Error Details" class="mt-6">
            <div class="max-h-64 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
                            <th class="pb-2 pr-4">Section</th>
                            <th class="pb-2 pr-4">Record</th>
                            <th class="pb-2">Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($job->errors as $error)
                            <tr>
                                <td class="py-2 pr-4">
                                    <x-badge tone="rose">{{ $error['section'] ?? 'general' }}</x-badge>
                                </td>
                                <td class="py-2 pr-4 text-slate-500">
                                    {{ $error['record_id'] ?? '-' }}
                                </td>
                                <td class="py-2 text-slate-700">{{ $error['message'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
@endif

{{-- Failed State --}}
@if ($job->isFailed())
    <x-card title="Import Failed">
        <div class="flex items-center gap-4 py-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="font-medium text-rose-800">The import process encountered a critical error.</p>
                <p class="mt-1 text-sm text-slate-500">Check the error details below or try again with a different backup.</p>
            </div>
            <x-btn variant="secondary" :href="route('migration.index')">Upload New Backup</x-btn>
        </div>

        @if ($job->errors && count($job->errors) > 0)
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-4">
                @foreach ($job->errors as $error)
                    <p class="text-sm text-rose-800">
                        <span class="font-medium">{{ $error['section'] ?? 'general' }}:</span>
                        {{ $error['message'] }}
                    </p>
                @endforeach
            </div>
        @endif
    </x-card>
@endif

{{-- Actions --}}
<div class="mt-8 flex items-center justify-between border-t border-slate-200 pt-6">
    <x-btn variant="secondary" :href="route('migration.index')">
        &larr; Back to Migration
    </x-btn>

    <div class="flex gap-3">
        @if ($job->isCompleted() || $job->isFailed())
            <form action="{{ route('migration.destroy', $job) }}" method="POST"
                  onsubmit="return confirm('Delete this import record? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <x-btn variant="danger" type="submit">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                    Delete Record
                </x-btn>
            </form>
        @endif

        <x-btn variant="primary" :href="route('migration.index')">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 4.5v15m0 0 6.75-6.75M12 19.5l-6.75-6.75"/></svg>
            New Import
        </x-btn>
    </div>
</div>
@endsection
