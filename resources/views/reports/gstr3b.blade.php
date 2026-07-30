@extends('layouts.app')
@section('title', 'GSTR-3B')

@section('content')
<x-page title="GSTR-3B" subtitle="Monthly summary return — outward supplies, ITC and net tax payable." />
@include('reports._filter')

@php
    $outward = $data['table_3_1_a_outward_taxable'] ?? [];
    $exports = $data['table_3_1_b_zero_rated'] ?? [];
    $rcm = $data['table_3_1_d_inward_reverse_charge'] ?? [];
    $itc = $data['table_4_itc_available'] ?? [];
    $netPayable = $data['net_tax_payable'] ?? 0;
    $period = $data['period'] ?? '—';

    $outTotalTax = ($outward['igst'] ?? 0) + ($outward['cgst'] ?? 0) + ($outward['sgst'] ?? 0) + ($outward['cess'] ?? 0);
    $itcTotal = ($itc['igst'] ?? 0) + ($itc['cgst'] ?? 0) + ($itc['sgst'] ?? 0) + ($itc['other_expenses_gst'] ?? 0);
@endphp

{{-- Period + GSTIN --}}
<div class="mb-5 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">
    <div class="grid gap-3 sm:grid-cols-3">
        <div><span class="text-slate-500">Return Period:</span> <span class="font-medium">{{ $period }}</span></div>
        <div><span class="text-slate-500">GSTIN:</span> <span class="font-mono font-medium">{{ \App\Support\TenantContext::get()?->gstin ?? '—' }}</span></div>
        <div class="sm:text-right">
            <span class="text-slate-500">Net Tax Payable:</span>
            <span class="text-lg font-bold {{ $netPayable > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                ₹{{ number_format($netPayable, 2) }}
            </span>
        </div>
    </div>
</div>

{{-- Net Payable Card --}}
<div class="mb-5 grid gap-4 sm:grid-cols-3">
    <x-card>
        <div class="text-xs uppercase tracking-wide text-slate-500">Output Tax</div>
        <div class="mt-1 text-2xl font-semibold text-slate-900">₹{{ number_format($outTotalTax, 2) }}</div>
        <div class="mt-0.5 text-xs text-slate-400">Tax collected on sales</div>
    </x-card>
    <x-card>
        <div class="text-xs uppercase tracking-wide text-slate-500">Input Tax Credit</div>
        <div class="mt-1 text-2xl font-semibold text-emerald-600">₹{{ number_format($itcTotal, 2) }}</div>
        <div class="mt-0.5 text-xs text-slate-400">ITC from purchases &amp; expenses</div>
    </x-card>
    <x-card>
        <div class="text-xs uppercase tracking-wide text-slate-500">Net Payable</div>
        <div class="mt-1 text-2xl font-bold {{ $netPayable > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
            ₹{{ number_format($netPayable, 2) }}
        </div>
        <div class="mt-0.5 text-xs text-slate-400">Output − ITC = cash liability</div>
    </x-card>
</div>

{{-- Table 3.1 — Outward Supplies --}}
<x-card title="3.1 Details of outward supplies" class="mb-5 !p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">Nature of Supply</th>
                    <th class="px-3 text-right font-medium">Taxable Value</th>
                    <th class="px-3 text-right font-medium">IGST</th>
                    <th class="px-3 text-right font-medium">CGST</th>
                    <th class="px-3 text-right font-medium">SGST</th>
                    <th class="px-5 text-right font-medium">Cess</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-slate-50">
                    <td class="px-5 py-3 font-medium text-slate-900">(a) Outward taxable supplies (other than zero rated, nil, reverse charge)</td>
                    <td class="px-3 text-right tabular-nums font-medium">₹{{ number_format($outward['taxable_value'] ?? 0, 2) }}</td>
                    <td class="px-3 text-right tabular-nums">₹{{ number_format($outward['igst'] ?? 0, 2) }}</td>
                    <td class="px-3 text-right tabular-nums">₹{{ number_format($outward['cgst'] ?? 0, 2) }}</td>
                    <td class="px-3 text-right tabular-nums">₹{{ number_format($outward['sgst'] ?? 0, 2) }}</td>
                    <td class="px-5 text-right tabular-nums">₹{{ number_format($outward['cess'] ?? 0, 2) }}</td>
                </tr>
                <tr class="border-b border-slate-50">
                    <td class="px-5 py-3 font-medium text-slate-900">(b) Zero rated supply (exports / SEZ)</td>
                    <td class="px-3 text-right tabular-nums font-medium">₹{{ number_format($exports['taxable_value'] ?? 0, 2) }}</td>
                    <td class="px-3 text-right tabular-nums">₹{{ number_format($exports['igst'] ?? 0, 2) }}</td>
                    <td class="px-3 text-right tabular-nums text-slate-300">₹0.00</td>
                    <td class="px-3 text-right tabular-nums text-slate-300">₹0.00</td>
                    <td class="px-5 text-right tabular-nums text-slate-300">₹0.00</td>
                </tr>
                <tr class="border-b border-slate-50 bg-slate-50/50">
                    <td class="px-5 py-3 text-slate-400">(c) Other outward supplies (nil rated, exempted)</td>
                    <td class="px-3 text-right tabular-nums text-slate-300">₹0.00</td>
                    <td class="px-3 text-right tabular-nums text-slate-300">₹0.00</td>
                    <td class="px-3 text-right tabular-nums text-slate-300">₹0.00</td>
                    <td class="px-3 text-right tabular-nums text-slate-300">₹0.00</td>
                    <td class="px-5 text-right tabular-nums text-slate-300">₹0.00</td>
                </tr>
                <tr class="border-b border-slate-50">
                    <td class="px-5 py-3 font-medium text-slate-900">(d) Inward supplies (reverse charge)</td>
                    <td class="px-3 text-right tabular-nums font-medium">₹{{ number_format($rcm['taxable_value'] ?? 0, 2) }}</td>
                    <td class="px-3 text-right tabular-nums text-slate-300">₹0.00</td>
                    <td class="px-3 text-right tabular-nums text-slate-300">₹0.00</td>
                    <td class="px-3 text-right tabular-nums text-slate-300">₹0.00</td>
                    <td class="px-5 text-right tabular-nums text-slate-300">₹0.00</td>
                </tr>
                <tr class="bg-slate-50/50">
                    <td class="px-5 py-3 text-slate-400">(e) Non-GST outward supplies</td>
                    <td class="px-3 text-right tabular-nums text-slate-300">₹0.00</td>
                    <td class="px-3 text-right tabular-nums text-slate-300">₹0.00</td>
                    <td class="px-3 text-right tabular-nums text-slate-300">₹0.00</td>
                    <td class="px-3 text-right tabular-nums text-slate-300">₹0.00</td>
                    <td class="px-5 text-right tabular-nums text-slate-300">₹0.00</td>
                </tr>
            </tbody>
        </table>
    </div>
</x-card>

{{-- Table 4 — ITC --}}
<x-card title="4. Eligible ITC" class="mb-5 !p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">Details</th>
                    <th class="px-3 text-right font-medium">IGST</th>
                    <th class="px-3 text-right font-medium">CGST</th>
                    <th class="px-5 text-right font-medium">SGST</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-slate-50">
                    <td class="px-5 py-3 font-medium text-slate-900">(A) ITC on purchase bills (GST invoices)</td>
                    <td class="px-3 text-right tabular-nums">₹{{ number_format($itc['igst'] ?? 0, 2) }}</td>
                    <td class="px-3 text-right tabular-nums">₹{{ number_format($itc['cgst'] ?? 0, 2) }}</td>
                    <td class="px-5 text-right tabular-nums">₹{{ number_format($itc['sgst'] ?? 0, 2) }}</td>
                </tr>
                <tr class="border-b border-slate-50">
                    <td class="px-5 py-3 text-slate-700">ITC on ITC-eligible expenses</td>
                    <td class="px-3 text-right tabular-nums text-slate-300">₹0.00</td>
                    <td class="px-3 text-right tabular-nums text-slate-300">₹0.00</td>
                    <td class="px-5 text-right tabular-nums text-slate-300">₹0.00</td>
                </tr>
                <tr class="border-b border-slate-50 bg-emerald-50/40">
                    <td class="px-5 py-3 font-medium text-slate-900">ITC from expenses (GST amount)</td>
                    <td class="px-3 text-right tabular-nums" colspan="3">
                        ₹{{ number_format($itc['other_expenses_gst'] ?? 0, 2) }}
                        <span class="text-xs text-slate-400 ml-1">(lump sum)</span>
                    </td>
                </tr>
            </tbody>
            <tfoot class="border-t border-slate-200 bg-slate-50 text-sm font-semibold">
                <tr>
                    <td class="px-5 py-3 text-slate-700">Total ITC Available</td>
                    <td class="px-3 text-right tabular-nums">₹{{ number_format($itc['igst'] ?? 0, 2) }}</td>
                    <td class="px-3 text-right tabular-nums">₹{{ number_format($itc['cgst'] ?? 0, 2) }}</td>
                    <td class="px-5 text-right tabular-nums">₹{{ number_format($itc['sgst'] ?? 0, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-card>

{{-- Tax Payment Computation --}}
<x-card title="6. Payment of tax" class="!p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">Description</th>
                    <th class="px-3 text-right font-medium">IGST</th>
                    <th class="px-3 text-right font-medium">CGST</th>
                    <th class="px-3 text-right font-medium">SGST</th>
                    <th class="px-5 text-right font-medium">Cess</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-slate-50">
                    <td class="px-5 py-3">Output Tax</td>
                    <td class="px-3 text-right tabular-nums">₹{{ number_format($outward['igst'] ?? 0, 2) }}</td>
                    <td class="px-3 text-right tabular-nums">₹{{ number_format($outward['cgst'] ?? 0, 2) }}</td>
                    <td class="px-3 text-right tabular-nums">₹{{ number_format($outward['sgst'] ?? 0, 2) }}</td>
                    <td class="px-5 text-right tabular-nums">₹{{ number_format($outward['cess'] ?? 0, 2) }}</td>
                </tr>
                <tr class="border-b border-slate-50">
                    <td class="px-5 py-3">Less: ITC</td>
                    <td class="px-3 text-right tabular-nums text-emerald-600">-₹{{ number_format($itc['igst'] ?? 0, 2) }}</td>
                    <td class="px-3 text-right tabular-nums text-emerald-600">-₹{{ number_format($itc['cgst'] ?? 0, 2) }}</td>
                    <td class="px-3 text-right tabular-nums text-emerald-600">-₹{{ number_format($itc['sgst'] ?? 0, 2) }}</td>
                    <td class="px-5 text-right tabular-nums text-slate-300">₹0.00</td>
                </tr>
                <tr class="border-b border-slate-50">
                    <td class="px-5 py-3">Less: ITC on expenses</td>
                    <td class="px-3 text-right tabular-nums text-slate-300" colspan="4">
                        -₹{{ number_format($itc['other_expenses_gst'] ?? 0, 2) }}
                    </td>
                </tr>
                <tr class="bg-slate-50 font-semibold">
                    <td class="px-5 py-3 text-slate-800">Net Tax Payable (cash)</td>
                    @php
                        $netIgst = max(0, ($outward['igst'] ?? 0) - ($itc['igst'] ?? 0));
                        $netCgst = max(0, ($outward['cgst'] ?? 0) - ($itc['cgst'] ?? 0));
                        $netSgst = max(0, ($outward['sgst'] ?? 0) - ($itc['sgst'] ?? 0));
                    @endphp
                    <td class="px-3 text-right tabular-nums {{ $netIgst > 0 ? 'text-rose-600' : 'text-emerald-600' }}">₹{{ number_format($netIgst, 2) }}</td>
                    <td class="px-3 text-right tabular-nums {{ $netCgst > 0 ? 'text-rose-600' : 'text-emerald-600' }}">₹{{ number_format($netCgst, 2) }}</td>
                    <td class="px-3 text-right tabular-nums {{ $netSgst > 0 ? 'text-rose-600' : 'text-emerald-600' }}">₹{{ number_format($netSgst, 2) }}</td>
                    <td class="px-5 text-right tabular-nums">₹{{ number_format($outward['cess'] ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</x-card>

{{-- Filing Notes --}}
<div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
    <p class="font-medium">📋 Filing checklist</p>
    <ul class="mt-2 space-y-1 text-xs">
        <li>• Verify B2B invoice details match your GSTR-1 before filing.</li>
        <li>• Reconcile ITC with your GSTR-2B / GSTR-2A on the GST portal.</li>
        <li>• Pay the net tax liability via challan before filing 3B.</li>
        <li>• Use the JSON download from GSTR-1 to upload to the offline utility.</li>
    </ul>
</div>
@endsection