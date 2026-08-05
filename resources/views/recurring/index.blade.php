@extends('layouts.app')
@section('title', 'Recurring Invoices')

@php
    $tones = ['active' => 'emerald', 'paused' => 'amber', 'completed' => 'indigo', 'cancelled' => 'rose'];
@endphp

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Recurring Invoices</h1>
            <p class="mt-0.5 text-sm text-slate-500">
                {{ $activeCount }} active schedule{{ $activeCount === 1 ? '' : 's' }}
                @if ($dueToday) · <span class="font-medium text-amber-600">{{ $dueToday }} due to run</span> @endif
            </p>
        </div>
        <x-btn variant="primary" :href="route('recurring.create')">+ New schedule</x-btn>
    </div>

    <form class="mb-4 flex gap-2 text-sm">
        <input name="q" value="{{ request('q') }}" placeholder="Search customer" class="rounded border-slate-300 px-3 py-2">
        <select name="status" class="rounded border-slate-300 px-3 py-2">
            <option value="">All statuses</option>
            @foreach (\App\Models\RecurringInvoice::STATUSES as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button class="rounded bg-slate-800 px-4 py-2 text-white">Filter</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="p-3">Customer</th><th>Document</th><th>Schedule</th>
                    <th class="text-right">Per invoice</th><th>Next run</th>
                    <th class="text-right">Generated</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($schedules as $s)
                <tr class="border-t">
                    <td class="p-3">
                        <a class="font-medium text-indigo-600" href="{{ route('recurring.show', $s) }}">{{ $s->party?->name ?? '—' }}</a>
                    </td>
                    <td>{{ \App\Models\RecurringInvoice::DOC_TYPES[$s->doc_type] ?? 'Invoice' }}
                        <span class="text-xs text-slate-400">({{ $s->tax_mode === 'gst' ? 'GST' : 'Non-GST' }})</span>
                    </td>
                    <td class="text-slate-600">{{ $s->scheduleLabel() }}</td>
                    <td class="text-right tabular-nums">₹ {{ number_format($s->estimatedTotal(), 2) }}</td>
                    <td>
                        @if ($s->status === 'active')
                            <span class="{{ $s->next_run_on->lte(now()) ? 'font-medium text-amber-600' : '' }}">
                                {{ $s->next_run_on->format('d M Y') }}
                            </span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="text-right">
                        {{ $s->generated_count }}{{ $s->max_occurrences ? ' / '.$s->max_occurrences : '' }}
                    </td>
                    <td><x-badge :tone="$tones[$s->status] ?? 'slate'">{{ ucfirst($s->status) }}</x-badge></td>
                    <td class="pr-3 text-right">
                        <a class="text-xs text-indigo-600" href="{{ route('recurring.edit', $s) }}">Edit</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="p-10 text-center text-slate-500">
                        <p class="font-medium text-slate-700">No recurring invoices yet</p>
                        <p class="mt-1 text-sm">Set up a schedule once and invoices will be created automatically —
                            perfect for monthly retainers, rent, AMCs and subscriptions.</p>
                        <p class="mt-3"><x-btn variant="primary" size="sm" :href="route('recurring.create')">Create your first schedule</x-btn></p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $schedules->links() }}</div>
@endsection
