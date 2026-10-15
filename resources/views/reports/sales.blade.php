@extends('layouts.app')
@section('title', __('Sales Register'))

@section('content')
@php
    $taxable = $documents->sum('taxable_value');
    $tax = $documents->sum(fn ($d) => (float) $d->cgst_total + (float) $d->sgst_total + (float) $d->igst_total + (float) $d->cess_total);
    $total = $documents->sum('grand_total');
    $paid = $documents->sum('amount_paid');
    $due = $documents->sum('balance_due');
@endphp

<x-page :title="__('Sales Register')" :subtitle="__('Every bill raised in this period.')" />
@include('reports._filter', ['export' => 'sales'])

<div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach ([
        [__('Bills'), number_format($documents->count()), 'text-slate-900'],
        [__('Taxable'), '₹'.number_format($taxable, 2), 'text-slate-900'],
        [__('Tax'), '₹'.number_format($tax, 2), 'text-slate-900'],
        [__('Outstanding'), '₹'.number_format($due, 2), $due > 0 ? 'text-rose-600' : 'text-slate-400'],
    ] as [$label, $value, $tone])
        <x-card>
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</div>
            <div class="mt-1 text-xl font-semibold {{ $tone }}">{{ $value }}</div>
        </x-card>
    @endforeach
</div>

<x-card class="!p-0 overflow-x-auto">
    @if ($documents->isEmpty())
        <x-empty :title="__('No sales in this period')" :message="__('Try widening the date range.')" />
    @else
        <table class="w-full min-w-[980px] text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">{{ __('Number') }}</th>
                    <th class="px-3 font-medium">{{ __('Date') }}</th>
                    <th class="px-3 font-medium">{{ __('Party') }}</th>
                    <th class="px-3 font-medium">GSTIN</th>
                    <th class="px-3 text-right font-medium">{{ __('Taxable') }}</th>
                    <th class="px-3 text-right font-medium">CGST</th>
                    <th class="px-3 text-right font-medium">SGST</th>
                    <th class="px-3 text-right font-medium">IGST</th>
                    <th class="px-3 text-right font-medium">{{ __('Total') }}</th>
                    <th class="px-3 text-right font-medium">{{ __('Balance due') }}</th>
                    <th class="px-5 font-medium">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($documents as $doc)
                <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/60">
                    <td class="px-5 py-3">
                        <a href="{{ route('documents.show', $doc) }}" class="font-medium text-indigo-600 hover:underline">{{ $doc->number }}</a>
                        <div class="text-xs text-slate-400">{{ $doc->title }}</div>
                    </td>
                    <td class="px-3 text-slate-600">{{ $doc->issue_date->format('d M Y') }}</td>
                    <td class="px-3">{{ $doc->party?->name ?? '—' }}</td>
                    <td class="px-3 font-mono text-xs">{{ $doc->party?->gstin ?: 'B2C' }}</td>
                    <td class="px-3 text-right tabular-nums">{{ number_format((float) $doc->taxable_value, 2) }}</td>
                    <td class="px-3 text-right tabular-nums text-slate-500">{{ number_format((float) $doc->cgst_total, 2) }}</td>
                    <td class="px-3 text-right tabular-nums text-slate-500">{{ number_format((float) $doc->sgst_total, 2) }}</td>
                    <td class="px-3 text-right tabular-nums text-slate-500">{{ number_format((float) $doc->igst_total, 2) }}</td>
                    <td class="px-3 text-right font-medium tabular-nums">{{ number_format((float) $doc->grand_total, 2) }}</td>
                    <td class="px-3 text-right tabular-nums {{ (float) $doc->balance_due > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                        {{ number_format((float) $doc->balance_due, 2) }}
                    </td>
                    <td class="px-5">
                        <x-badge tone="{{ ['paid' => 'emerald', 'partially_paid' => 'amber', 'overdue' => 'rose'][$doc->status] ?? 'slate' }}">
                            {{ __(ucfirst(str_replace('_', ' ', $doc->status))) }}
                        </x-badge>
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot class="border-t border-slate-200 bg-slate-50 text-sm">
                <tr>
                    <td class="px-5 py-3 font-medium" colspan="4">{{ __('Totals') }}</td>
                    <td class="px-3 py-3 text-right font-medium tabular-nums">{{ number_format($taxable, 2) }}</td>
                    <td class="px-3 py-3 text-right tabular-nums" colspan="3">{{ number_format($tax, 2) }}</td>
                    <td class="px-3 py-3 text-right font-semibold tabular-nums">{{ number_format($total, 2) }}</td>
                    <td class="px-3 py-3 text-right font-semibold tabular-nums {{ $due > 0 ? 'text-rose-600' : '' }}">{{ number_format($due, 2) }}</td>
                    <td class="px-5"></td>
                </tr>
            </tfoot>
        </table>
    @endif
</x-card>

<p class="mt-3 text-xs text-slate-500">
    {{ __('Collected in this period') }}: ₹{{ number_format($paid, 2) }}
</p>
@endsection
