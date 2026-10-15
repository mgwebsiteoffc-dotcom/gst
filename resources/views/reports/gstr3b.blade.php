@extends('layouts.app')
@section('title', 'GSTR-3B')

@section('content')
@php
    $outward = $data['table_3_1_a_outward_taxable'] ?? [];
    $zeroRated = $data['table_3_1_b_zero_rated'] ?? [];
    $reverse = $data['table_3_1_d_inward_reverse_charge'] ?? [];
    $itc = $data['table_4_itc_available'] ?? [];
    $netPayable = (float) ($data['net_tax_payable'] ?? 0);
    $money = fn ($v) => number_format((float) $v, 2);
    $outputTax = (float) ($outward['igst'] ?? 0) + (float) ($outward['cgst'] ?? 0) + (float) ($outward['sgst'] ?? 0) + (float) ($outward['cess'] ?? 0);
    $totalItc = (float) ($itc['igst'] ?? 0) + (float) ($itc['cgst'] ?? 0) + (float) ($itc['sgst'] ?? 0) + (float) ($itc['other_expenses_gst'] ?? 0);
@endphp

<x-page title="GSTR-3B" :subtitle="__('Monthly summary return — cross-check before filing.')" />
@include('reports._filter', ['export' => 'sales'])

<div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach ([
        [__('Output tax'), '₹'.$money($outputTax), 'text-slate-900'],
        [__('ITC available'), '₹'.$money($totalItc), 'text-emerald-700'],
        [__('Net tax payable'), '₹'.$money(max($netPayable, 0)), $netPayable > 0 ? 'text-rose-600' : 'text-emerald-700'],
        [__('Period'), $from->format('M Y'), 'text-slate-900'],
    ] as [$label, $value, $tone])
        <x-card>
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</div>
            <div class="mt-1 text-xl font-semibold {{ $tone }}">{{ $value }}</div>
        </x-card>
    @endforeach
</div>

@if ($netPayable < 0)
    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ __('Input credit is higher than your output tax this period — carry the balance forward, no cash payment is due.') }}
    </div>
@endif

<x-card :title="__('3.1 Outward supplies')" class="mb-5 !p-0 overflow-x-auto">
    <table class="w-full min-w-[820px] text-sm">
        <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-5 py-3 font-medium">{{ __('Nature of supply') }}</th>
                <th class="px-3 text-right font-medium">{{ __('Taxable value') }}</th>
                <th class="px-3 text-right font-medium">IGST</th>
                <th class="px-3 text-right font-medium">CGST</th>
                <th class="px-3 text-right font-medium">SGST</th>
                <th class="px-5 text-right font-medium">{{ __('Cess') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-slate-50">
                <td class="px-5 py-3">
                    <span class="font-medium">(a)</span> {{ __('Taxable supplies (other than zero-rated)') }}
                </td>
                <td class="px-3 py-3 text-right tabular-nums">{{ $money($outward['taxable_value'] ?? 0) }}</td>
                <td class="px-3 py-3 text-right tabular-nums">{{ $money($outward['igst'] ?? 0) }}</td>
                <td class="px-3 py-3 text-right tabular-nums">{{ $money($outward['cgst'] ?? 0) }}</td>
                <td class="px-3 py-3 text-right tabular-nums">{{ $money($outward['sgst'] ?? 0) }}</td>
                <td class="px-5 py-3 text-right tabular-nums">{{ $money($outward['cess'] ?? 0) }}</td>
            </tr>
            <tr class="border-b border-slate-50">
                <td class="px-5 py-3">
                    <span class="font-medium">(b)</span> {{ __('Zero-rated supplies (exports, SEZ)') }}
                </td>
                <td class="px-3 py-3 text-right tabular-nums">{{ $money($zeroRated['taxable_value'] ?? 0) }}</td>
                <td class="px-3 py-3 text-right tabular-nums">{{ $money($zeroRated['igst'] ?? 0) }}</td>
                <td class="px-3 py-3 text-right tabular-nums text-slate-300">—</td>
                <td class="px-3 py-3 text-right tabular-nums text-slate-300">—</td>
                <td class="px-5 py-3 text-right tabular-nums text-slate-300">—</td>
            </tr>
            <tr>
                <td class="px-5 py-3">
                    <span class="font-medium">(d)</span> {{ __('Inward supplies liable to reverse charge') }}
                </td>
                <td class="px-3 py-3 text-right tabular-nums">{{ $money($reverse['taxable_value'] ?? 0) }}</td>
                <td class="px-3 py-3 text-right tabular-nums text-slate-300">—</td>
                <td class="px-3 py-3 text-right tabular-nums text-slate-300">—</td>
                <td class="px-3 py-3 text-right tabular-nums text-slate-300">—</td>
                <td class="px-5 py-3 text-right tabular-nums text-slate-300">—</td>
            </tr>
        </tbody>
        <tfoot class="border-t border-slate-200 bg-slate-50">
            <tr>
                <td class="px-5 py-3 font-medium">{{ __('Total output tax') }}</td>
                <td class="px-3 py-3"></td>
                <td class="px-3 py-3 text-right font-medium tabular-nums">{{ $money($outward['igst'] ?? 0) }}</td>
                <td class="px-3 py-3 text-right font-medium tabular-nums">{{ $money($outward['cgst'] ?? 0) }}</td>
                <td class="px-3 py-3 text-right font-medium tabular-nums">{{ $money($outward['sgst'] ?? 0) }}</td>
                <td class="px-5 py-3 text-right font-medium tabular-nums">{{ $money($outward['cess'] ?? 0) }}</td>
            </tr>
        </tfoot>
    </table>
</x-card>

<x-card :title="__('4. Input tax credit')" class="mb-5 !p-0 overflow-x-auto">
    <table class="w-full min-w-[720px] text-sm">
        <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-5 py-3 font-medium">{{ __('Source') }}</th>
                <th class="px-3 text-right font-medium">IGST</th>
                <th class="px-3 text-right font-medium">CGST</th>
                <th class="px-5 text-right font-medium">SGST</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-slate-50">
                <td class="px-5 py-3">{{ __('Purchases (purchase bills)') }}</td>
                <td class="px-3 py-3 text-right tabular-nums">{{ $money($itc['igst'] ?? 0) }}</td>
                <td class="px-3 py-3 text-right tabular-nums">{{ $money($itc['cgst'] ?? 0) }}</td>
                <td class="px-5 py-3 text-right tabular-nums">{{ $money($itc['sgst'] ?? 0) }}</td>
            </tr>
            <tr>
                <td class="px-5 py-3">{{ __('ITC-eligible expenses') }}</td>
                <td class="px-3 py-3 text-right tabular-nums text-slate-300">—</td>
                <td class="px-3 py-3 text-right tabular-nums text-slate-300">—</td>
                <td class="px-5 py-3 text-right tabular-nums">{{ $money($itc['other_expenses_gst'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>
</x-card>

<x-card :title="__('Net tax payable')">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">
            {{ __('Output tax minus input tax credit for the period. Verify with your CA before paying on the portal.') }}
        </p>
        <div class="text-right">
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ __('Payable') }}</div>
            <div class="text-2xl font-semibold {{ $netPayable > 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                ₹{{ $money(abs($netPayable)) }}
            </div>
            <div class="text-xs text-slate-500">
                {{ $netPayable > 0 ? __('payable in cash / credit ledger') : __('credit carried forward') }}
            </div>
        </div>
    </div>
</x-card>

<p class="mt-3 text-xs text-slate-500">
    {{ __('Figures exclude cancelled and draft documents. Reverse charge purchases are reported from purchase bills marked as reverse charge.') }}
</p>
@endsection
