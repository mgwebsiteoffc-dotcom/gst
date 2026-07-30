@extends('layouts.app')
@section('title', 'Data Migration')

@section('content')
<x-page title="Data Migration" subtitle="Import data from a previous version of the application." />

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Upload Card --}}
    <div class="lg:col-span-2">
        <x-card title="Upload Backup File" subtitle="Select a JSON backup file to begin the import process.">
            <form action="{{ route('migration.upload') }}" method="POST" enctype="multipart/form-data"
                  x-data="{ file: null, dragging: false }"
                  @dragover.prevent="dragging = true"
                  @dragleave.prevent="dragging = false"
                  @drop.prevent="dragging = false; file = $event.dataTransfer.files[0]; $refs.fileInput.files = $event.dataTransfer.files">

                @csrf

                <div class="relative">
                    {{-- Dropzone --}}
                    <div :class="dragging ? 'border-indigo-400 bg-indigo-50' : 'border-slate-300 bg-slate-50'"
                         class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-12 text-center transition">

                        <svg class="mb-3 h-10 w-10 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z"/>
                        </svg>

                        <p class="text-sm font-medium text-slate-700">
                            <span x-show="!file">Drag & drop your JSON backup file here, or</span>
                            <span x-show="file" x-text="file?.name" class="text-indigo-700"></span>
                        </p>

                        <label class="mt-3 cursor-pointer">
                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                Browse Files
                            </span>
                            <input x-ref="fileInput" type="file" name="backup_file" accept=".json"
                                   class="sr-only"
                                   @change="file = $event.target.files[0]" required>
                        </label>

                        <p class="mt-3 text-xs text-slate-500">Supported format: .json (up to 50 MB)</p>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <x-btn variant="primary" type="submit" ::disabled="!file">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                        Upload &amp; Analyze
                    </x-btn>
                </div>
            </form>
        </x-card>

        {{-- Quick Info --}}
        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </div>
                <h3 class="text-sm font-medium text-slate-900">Safe Import</h3>
                <p class="mt-1 text-xs text-slate-500">Existing data is never deleted. New records are added alongside.</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3"/></svg>
                </div>
                <h3 class="text-sm font-medium text-slate-900">Idempotent</h3>
                <p class="mt-1 text-xs text-slate-500">Running the same import twice won't create duplicates.</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m9.86-2.556a4.5 4.5 0 0 0-1.242-7.244l4.5-4.5a4.5 4.5 0 0 1 6.364 6.364l-1.757 1.757" transform="translate(-1 4)"/></svg>
                </div>
                <h3 class="text-sm font-medium text-slate-900">Relationships Preserved</h3>
                <p class="mt-1 text-xs text-slate-500">Invoices, payments, and items stay linked correctly.</p>
            </div>
        </div>
    </div>

    {{-- Sidebar: Import History --}}
    <div>
        <x-card title="Import History">
            @if ($history->isEmpty())
                <x-empty title="No imports yet" message="Upload a backup file to get started." />
            @else
                <div class="space-y-3">
                    @foreach ($history as $job)
                        <a href="{{ route('migration.show', $job) }}"
                           class="block rounded-lg border border-slate-200 p-3 transition hover:border-indigo-200 hover:bg-indigo-50/30">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $job->filename }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        {{ $job->created_at->diffForHumans() }}
                                        @if ($job->user) · {{ $job->user->name }} @endif
                                    </p>
                                </div>
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
                            </div>

                            @if ($job->isCompleted() && $job->summary)
                                <div class="mt-2 flex gap-3 text-xs text-slate-500">
                                    <span class="text-emerald-600">+{{ $job->summary['imported'] ?? 0 }}</span>
                                    <span class="text-blue-600">~{{ $job->summary['updated'] ?? 0 }}</span>
                                    <span class="text-slate-400">⊘{{ $job->summary['skipped'] ?? 0 }}</span>
                                    @if (($job->summary['failed'] ?? 0) > 0)
                                        <span class="text-rose-600">✕{{ $job->summary['failed'] }}</span>
                                    @endif
                                </div>
                            @endif

                            @if ($job->duration_seconds)
                                <p class="mt-1 text-xs text-slate-400">{{ $job->duration_formatted }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</div>
@endsection
