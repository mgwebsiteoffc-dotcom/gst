@extends('layouts.app')
@section('title', 'Recurring · '.($schedule->party?->name ?? 'Invoice'))

@php
    $tones = ['active' => 'emerald', 'paused' => 'amber', 'completed' => 'indigo', 'cancelled' => 'rose'];
    $template = $schedule->template ?? [];
@endphp

@section('content')
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-semibold">{{ $schedule->party?->name ?? 'Recurring invoice' }}</h1>
                <x-badge :tone="$tones[$schedule->status] ?? 'slate'">{{ ucfirst($schedule->status) }}</x-badge>
            </div>
            <p class="text-sm text-slate-500">
                {{ $schedule->scheduleLabel() }} ·
                {{ \App\Models\RecurringInvoice::DOC_TYPES[$schedule->doc_type] ?? 'Invoice' }}
                ({{ $schedule->tax_mode === 'gst' ? 'GST' : 'Non-GST' }})
            </p>
        </div>

        <div class="flex flex-wrap gap-2 text-sm">
            @if ($schedule->status === 'active')
                <form method="post" action="{{ route('recurring.run', $schedule) }}">@csrf
                    <x-btn variant="primary" type="submit">Generate now</x-btn>
                </form>
                <form method="post" action="{{ route('recurring.pause', $schedule) }}">@csrf
                    <x-btn variant="secondary" type="submit">Pause</x-btn>
                </form>
            @elseif ($schedule->status === 'paused')
                <form method="post" action="{{ route('recurring.resume', $schedule) }}">@csrf
                    <x-btn variant="primary" type="submit">Resume</x-btn>
                </form>
            @endif

            <x-btn variant="secondary" :href="route('recurring.edit', $schedule)">Edit</x-btn>

            @if ($schedule->status !== 'cancelled')
                <form method="post" action="{{ route('recurring.destroy', $schedule) }}"
                      onsubmit="return confirm('Cancel this schedule? Invoices already generated are kept.')">
                    @csrf @method('DELETE')
                    <x-btn variant="danger" type="submit">Cancel schedule</x-btn>
                </form>
            @endif
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        {{-- Schedule facts --}}
        <x-card title="Schedule" class="h-fit">
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Frequency</dt><dd class="font-medium">{{ $schedule->scheduleLabel() }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Started</dt><dd>{{ $schedule->starts_on->format('d M Y') }}</dd></div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Next run</dt>
                    <dd class="{{ $schedule->status === 'active' && $schedule->next_run_on->lte(now()) ? 'font-medium text-amber-600' : '' }}">
                        {{ $schedule->status === 'active' ? $schedule->next_run_on->format('d M Y') : '—' }}
                    </dd>
                </div>
                <div class="flex justify-between"><dt class="text-slate-500">Ends</dt><dd>{{ $schedule->ends_on?->format('d M Y') ?? 'Never' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Max invoices</dt><dd>{{ $schedule->max_occurrences ?? 'Unlimited' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Generated so far</dt><dd>{{ $schedule->generated_count }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Payment terms</dt><dd>{{ $schedule->due_days !== null ? $schedule->due_days.' days' : 'Customer default' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Issues as</dt><dd>{{ $schedule->auto_finalize ? 'Final invoice' : 'Draft (review first)' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Last generated</dt><dd>{{ $schedule->last_generated_at?->format('d M Y') ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Created by</dt><dd>{{ $schedule->creator?->name ?? '—' }}</dd></div>
            </dl>
        </x-card>

        {{-- What each invoice contains --}}
        <x-card class="lg:col-span-2 !p-0" title="Invoice template"
                subtitle="₹ {{ number_format($schedule->estimatedTotal(), 2) }} per invoice (estimated)">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="py-2 pl-5 font-medium">Item</th>
                            <th class="px-2 font-medium">HSN</th>
                            <th class="px-2 text-right font-medium">Qty</th>
                            <th class="px-2 text-right font-medium">Rate</th>
                            <th class="px-2 text-right font-medium">GST</th>
                            <th class="py-2 pr-5 text-right font-medium">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($template['lines'] ?? [] as $line)
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="py-2 pl-5">
                                    <div class="font-medium">{{ $line['name'] ?? '' }}</div>
                                    @if (! empty($line['description']))<div class="text-xs text-slate-400">{{ $line['description'] }}</div>@endif
                                </td>
                                <td class="px-2">{{ $line['hsn_sac'] ?? '—' }}</td>
                                <td class="px-2 text-right">{{ rtrim(rtrim(number_format((float) ($line['quantity'] ?? 1), 3), '0'), '.') }}</td>
                                <td class="px-2 text-right">{{ number_format((float) ($line['rate'] ?? 0), 2) }}</td>
                                <td class="px-2 text-right">{{ (float) ($line['gst_rate'] ?? 0) }}%</td>
                                <td class="py-2 pr-5 text-right tabular-nums">
                                    {{ number_format(((float) ($line['quantity'] ?? 1)) * ((float) ($line['rate'] ?? 0)) * (1 - ((float) ($line['discount_percent'] ?? 0)) / 100), 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (! empty($template['notes']) || ! empty($template['terms']))
                <div class="grid gap-4 border-t border-slate-100 px-5 py-4 text-xs text-slate-500 sm:grid-cols-2">
                    @if (! empty($template['notes']))<div><span class="font-medium text-slate-600">Notes:</span> {{ $template['notes'] }}</div>@endif
                    @if (! empty($template['terms']))<div><span class="font-medium text-slate-600">Terms:</span> {{ $template['terms'] }}</div>@endif
                </div>
            @endif
        </x-card>

        {{-- Generated documents --}}
        <x-card class="lg:col-span-3 !p-0" title="Generated invoices"
                subtitle="Created automatically by the daily scheduler, or manually with “Generate now”.">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="py-2 pl-5 font-medium">Number</th>
                            <th class="px-2 font-medium">Date</th>
                            <th class="px-2 text-right font-medium">Total</th>
                            <th class="px-2 text-right font-medium">Balance</th>
                            <th class="px-2 font-medium">Status</th>
                            <th class="py-2 pr-5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $doc)
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="py-2 pl-5"><a class="font-medium text-indigo-600" href="{{ route('documents.show', $doc) }}">{{ $doc->number }}</a></td>
                                <td class="px-2">{{ $doc->issue_date->format('d M Y') }}</td>
                                <td class="px-2 text-right tabular-nums">₹ {{ number_format($doc->grand_total, 2) }}</td>
                                <td class="px-2 text-right tabular-nums">₹ {{ number_format($doc->balance_due, 2) }}</td>
                                <td class="px-2"><span class="rounded bg-slate-100 px-2 py-0.5 text-xs">{{ str_replace('_', ' ', $doc->status) }}</span></td>
                                <td class="py-2 pr-5 text-right"><a class="text-xs text-slate-500" href="{{ route('documents.pdf', $doc) }}" target="_blank">PDF</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">No invoices generated yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">{{ $documents->links() }}</div>
        </x-card>
    </div>
@endsection
