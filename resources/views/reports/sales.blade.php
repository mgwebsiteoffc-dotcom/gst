@extends('layouts.app')
@section('title', 'Sales Report')

@section('content')
<x-page title="Sales Register" subtitle="All sales documents in the selected period." />
@include('reports._filter', ['export' => 'sales'])

@php
    $totalTaxable = $documents->sum('taxable_value');
    $totalTax = $documents->sum(fn ($d) => (float) $d->cgst_total + (float) $d->sgst_total + (float) $d->igst_total + (float) $d->cess_total);
    $totalAmount = $documents->sum('grand_total');
    $totalBalance = $documents->sum('balance_due');
    $count = $documents->count();
@endphp

{{-- Summary Cards --}}
<div class="mb-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
    <x-card>
        <div class="text-xs uppercase tracking-wide text-slate-500">Documents</div>
        <div class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($count) }}</div>
    </x-card>
    <x-card>
        <div class="text-xs uppercase tracking-wide text-slate-500">Taxable Value</div>
        <div class="mt-1 text-2xl font-semibold text-slate-900">₹{{ number_format($totalTaxable, 2) }}</div>
    </x-card>
    <x-card>
        <div class="text-xs uppercase tracking-wide text-slate-500">Tax Collected</div>
        <div class="mt-1 text-2xl font-semibold text-slate-900">₹{{ number_format($totalTax, 2) }}</div>
    </x-card>
    <x-card>
        <div class="text-xs uppercase tracking-wide text-slate-500">Total Sales</div>
        <div class="mt-1 text-2xl font-semibold text-indigo-700">₹{{ number_format($totalAmount, 2) }}</div>
    </x-card>
    <x-card>
        <div class="text-xs uppercase tracking-wide text-slate-500">Outstanding</div>
        <div class="mt-1 text-2xl font-semibold {{ $totalBalance > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
            ₹{{ number_format($totalBalance, 2) }}
        </div>
    </x-card>
</div>

{{-- Sales Table --}}
<x-card class="!p-0">
    @if ($documents->isEmpty())
        <x-empty title="No sales in this period" message="Try widening the date range." />
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 border-b border-slate-100 bg-white text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Number</th>
                        <th class="px-3 font-medium">Date</th>
                        <th class="px-3 font-medium">Type</th>
                        <th class="px-3 font-medium">Party</th>
                        <th class="px-3 font-medium">GSTIN</th>
                        <th class="px-3 text-right font-medium">Taxable</th>
                        <th class="px-3 text-right font-medium">CGST</th>
                        <th class="px-3 text-right font-medium">SGST</th>
                        <th class="px-3 text-right font-medium">IGST</th>
                        <th class="px-3 text-right font-medium">Total</th>
                        <th class="px-3 text-right font-medium">Balance</th>
                        <th class="px-5 text-center font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($documents as $doc)
                    <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/60">
                        <td class="px-5 py-2.5">
                            <a href="{{ route('documents.show', $doc) }}" class="font-medium text-indigo-600 hover:underline">
                                {{ $doc->number }}
                            </a>
                        </td>
                        <td class="px-3 whitespace-nowrap text-slate-500">{{ $doc->issue_date->format('d M Y') }}</td>
                        <td class="px-3 text-xs text-slate-500">{{ $doc->title }}</td>
                        <td class="px-3 font-medium text-slate-700 max-w-[180px] truncate">{{ $doc->party?->name ?: '—' }}</td>
                        <td class="px-3 font-mono text-xs text-slate-400">{{ $doc->party?->gstin ?: '—' }}</td>
                        <td class="px-3 text-right tabular-nums">{{ number_format((float) $doc->taxable_value, 2) }}</td>
                        <td class="px-3 text-right tabular-nums text-slate-500">{{ number_format((float) $doc->cgst_total, 2) }}</td>
                        <td class="px-3 text-right tabular-nums text-slate-500">{{ number_format((float) $doc->sgst_total, 2) }}</td>
                        <td class="px-3 text-right tabular-nums text-slate-500">{{ number_format((float) $doc->igst_total, 2) }}</td>
                        <td class="px-3 text-right font-medium tabular-nums">₹{{ number_format((float) $doc->grand_total, 2) }}</td>
                        <td class="px-3 text-right tabular-nums {{ (float) $doc->balance_due > 0 ? 'text-rose-600 font-medium' : 'text-slate-300' }}">
                            ₹{{ number_format((float) $doc->balance_due, 2) }}
                        </td>
                        <td class="px-5 text-center">
                            <x-badge :status="$doc->status" />
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot class="border-t border-slate-200 bg-slate-50 text-sm font-semibold">
                    <tr>
                        <td class="px-5 py-3 text-slate-700" colspan="5">Total ({{ $count }} documents)</td>
                        <td class="px-3 text-right tabular-nums">{{ number_format($totalTaxable, 2) }}</td>
                        <td class="px-3 text-right tabular-nums">{{ number_format($documents->sum('cgst_total'), 2) }}</td>
                        <td class="px-3 text-right tabular-nums">{{ number_format($documents->sum('sgst_total'), 2) }}</td>
                        <td class="px-3 text-right tabular-nums">{{ number_format($documents->sum('igst_total'), 2) }}</td>
                        <td class="px-3 text-right tabular-nums">₹{{ number_format($totalAmount, 2) }}</td>
                        <td class="px-3 text-right tabular-nums {{ $totalBalance > 0 ? 'text-rose-600' : '' }}">₹{{ number_format($totalBalance, 2) }}</td>
                        <td class="px-5"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</x-card>
@endsection