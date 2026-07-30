@extends('layouts.app')
@section('title', 'GSTR-1')

@section('content')
<x-page title="GSTR-1" subtitle="Outward supplies — the data you file every month/quarter.">
    <x-btn variant="secondary"
           :href="route('reports.gstr1', ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'json' => 1])">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
        Download JSON (Offline Tool)
    </x-btn>
</x-page>

@include('reports._filter')

@php
    $b2b = $data['b2b'] ?? [];
    $b2cl = $data['b2cl'] ?? [];
    $b2cs = $data['b2cs'] ?? [];
    $cdnr = $data['cdnr'] ?? [];
    $exports = $data['exp'] ?? [];
    $hsn = $data['hsn']['data'] ?? [];

    $b2bCount = collect($b2b)->sum(fn ($g) => count($g['inv'] ?? []));
    $b2clCount = collect($b2cl)->sum(fn ($g) => count($g['inv'] ?? []));
    $b2csCount = count($b2cs);
    $cdnrCount = count($cdnr);
    $hsnCount = count($hsn);
@endphp

{{-- Section Tabs --}}
<div x-data="{ tab: 'summary' }" class="mb-5">
    <div class="flex flex-wrap gap-1 border-b border-slate-200">
        @foreach ([
            'summary' => 'Summary',
            'b2b' => '4. B2B (' . $b2bCount . ')',
            'b2cl' => '5. B2C Large (' . $b2clCount . ')',
            'b2cs' => '7. B2C Small (' . $b2csCount . ')',
            'cdnr' => '9B. CDNR (' . $cdnrCount . ')',
            'hsn' => '12. HSN (' . $hsnCount . ')',
        ] as $key => $label)
            <button @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'border-indigo-600 font-medium text-indigo-700' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800'"
                    class="-mb-px border-b-2 px-4 py-2.5 text-sm transition">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Summary --}}
    <div x-show="tab === 'summary'" class="mt-5">
        <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">
            <div class="grid gap-3 sm:grid-cols-3">
                <div><span class="text-slate-500">GSTIN:</span> <span class="font-mono font-medium">{{ $data['gstin'] ?? '—' }}</span></div>
                <div><span class="text-slate-500">Return Period:</span> <span class="font-medium">{{ $data['fp'] ?? '—' }}</span></div>
                <div><span class="text-slate-500">Version:</span> <span class="font-medium">{{ $data['version'] ?? '—' }}</span></div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['4. B2B Invoices', $b2bCount, 'Invoices to registered buyers with GSTIN', 'indigo'],
                ['5. B2C Large', $b2clCount, 'Inter-state invoices above ₹1,00,000', 'blue'],
                ['7. B2C Small', $b2csCount, 'Aggregated by state + rate', 'slate'],
                ['9B. Credit/Debit Notes', $cdnrCount, 'Notes against B2B invoices', 'amber'],
                ['12. HSN Summary', $hsnCount, 'HSN-wise taxable value & tax', 'emerald'],
            ] as [$title, $count, $desc, $tone])
                <x-card>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs uppercase tracking-wide text-slate-500">{{ $title }}</div>
                            <div class="mt-1 text-sm text-slate-500">{{ $desc }}</div>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-{{ $tone }}-50 text-lg font-bold text-{{ $tone }}-700">
                            {{ $count }}
                        </span>
                    </div>
                </x-card>
            @endforeach
        </div>

        @if ($b2bCount + $b2clCount + $b2csCount + $cdnrCount === 0)
            <x-card class="mt-5">
                <x-empty title="No GST invoices in this period"
                         message="GSTR-1 includes only GST invoices (not non-GST). Create some invoices to see data here." />
            </x-card>
        @endif
    </div>

    {{-- 4. B2B --}}
    <div x-show="tab === 'b2b'" class="mt-5">
        @if (empty($b2b))
            <x-card><x-empty title="No B2B invoices" message="B2B invoices are those issued to parties with a valid GSTIN." /></x-card>
        @else
            <div class="space-y-4">
                @foreach ($b2b as $group)
                    <x-card :title="'GSTIN: ' . ($group['ctin'] ?? 'Unknown')" class="!p-0">
                        <table class="w-full text-sm">
                            <thead class="border-b border-slate-100 text-left text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-4 py-2.5 font-medium">Invoice No</th>
                                    <th class="px-3 font-medium">Date</th>
                                    <th class="px-3 font-medium">POS</th>
                                    <th class="px-3 font-medium">RCM</th>
                                    <th class="px-3 text-right font-medium">Value</th>
                                    <th class="px-3 font-medium">IRN</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($group['inv'] ?? [] as $inv)
                                <tr class="border-b border-slate-50 last:border-0">
                                    <td class="px-4 py-2 font-medium text-slate-900">{{ $inv['inum'] }}</td>
                                    <td class="px-3 text-slate-500">{{ $inv['idt'] }}</td>
                                    <td class="px-3 font-mono text-xs">{{ $inv['pos'] ?? '—' }}</td>
                                    <td class="px-3 text-xs">{{ $inv['rchrg'] ?? 'N' }}</td>
                                    <td class="px-3 text-right font-medium tabular-nums">₹{{ number_format($inv['val'], 2) }}</td>
                                    <td class="px-3 font-mono text-xs text-slate-400 max-w-[120px] truncate" title="{{ $inv['irn'] ?? '' }}">{{ Str::limit($inv['irn'] ?? '—', 16) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </x-card>
                @endforeach
            </div>
        @endif
    </div>

    {{-- 5. B2C Large --}}
    <div x-show="tab === 'b2cl'" class="mt-5">
        @if (empty($b2cl))
            <x-card><x-empty title="No B2C Large invoices" message="Inter-state B2C invoices above ₹1,00,000 appear here." /></x-card>
        @else
            <div class="space-y-4">
                @foreach ($b2cl as $group)
                    <x-card :title="'Place of Supply: ' . ($group['pos'] ?? '—')" class="!p-0">
                        <table class="w-full text-sm">
                            <thead class="border-b border-slate-100 text-left text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-4 py-2.5 font-medium">Invoice No</th>
                                    <th class="px-3 font-medium">Date</th>
                                    <th class="px-3 text-right font-medium">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($group['inv'] ?? [] as $inv)
                                <tr class="border-b border-slate-50 last:border-0">
                                    <td class="px-4 py-2 font-medium text-slate-900">{{ $inv['inum'] }}</td>
                                    <td class="px-3 text-slate-500">{{ $inv['idt'] }}</td>
                                    <td class="px-3 text-right font-medium tabular-nums">₹{{ number_format($inv['val'], 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </x-card>
                @endforeach
            </div>
        @endif
    </div>

    {{-- 7. B2C Small --}}
    <div x-show="tab === 'b2cs'" class="mt-5">
        @if (empty($b2cs))
            <x-card><x-empty title="No B2C Small entries" message="Small B2C supplies aggregated by state and rate." /></x-card>
        @else
            <x-card class="!p-0">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-100 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Supply Type</th>
                            <th class="px-3 font-medium">POS</th>
                            <th class="px-3 text-right font-medium">Rate %</th>
                            <th class="px-3 text-right font-medium">Taxable Value</th>
                            <th class="px-3 text-right font-medium">IGST</th>
                            <th class="px-3 text-right font-medium">CGST</th>
                            <th class="px-5 text-right font-medium">SGST</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($b2cs as $row)
                        <tr class="border-b border-slate-50 last:border-0">
                            <td class="px-5 py-2.5">
                                <x-badge tone="{{ $row['sply_ty'] === 'INTER' ? 'blue' : 'slate' }}">{{ $row['sply_ty'] }}</x-badge>
                            </td>
                            <td class="px-3 font-mono text-xs">{{ $row['pos'] }}</td>
                            <td class="px-3 text-right tabular-nums">{{ rtrim(rtrim(number_format($row['rt'], 2), '0'), '.') }}%</td>
                            <td class="px-3 text-right tabular-nums font-medium">₹{{ number_format($row['txval'], 2) }}</td>
                            <td class="px-3 text-right tabular-nums text-slate-500">₹{{ number_format($row['iamt'], 2) }}</td>
                            <td class="px-3 text-right tabular-nums text-slate-500">₹{{ number_format($row['camt'], 2) }}</td>
                            <td class="px-5 text-right tabular-nums text-slate-500">₹{{ number_format($row['samt'], 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot class="border-t border-slate-200 bg-slate-50 text-sm font-semibold">
                        <tr>
                            <td class="px-5 py-3" colspan="3">Total</td>
                            <td class="px-3 text-right tabular-nums">₹{{ number_format(collect($b2cs)->sum('txval'), 2) }}</td>
                            <td class="px-3 text-right tabular-nums">₹{{ number_format(collect($b2cs)->sum('iamt'), 2) }}</td>
                            <td class="px-3 text-right tabular-nums">₹{{ number_format(collect($b2cs)->sum('camt'), 2) }}</td>
                            <td class="px-5 text-right tabular-nums">₹{{ number_format(collect($b2cs)->sum('samt'), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </x-card>
        @endif
    </div>

    {{-- 9B. CDNR --}}
    <div x-show="tab === 'cdnr'" class="mt-5">
        @if (empty($cdnr))
            <x-card><x-empty title="No credit/debit notes" message="Notes issued against B2B invoices appear here." /></x-card>
        @else
            <x-card class="!p-0">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-100 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Type</th>
                            <th class="px-3 font-medium">GSTIN</th>
                            <th class="px-3 font-medium">Note No</th>
                            <th class="px-3 font-medium">Date</th>
                            <th class="px-3 font-medium">Invoice</th>
                            <th class="px-3 text-right font-medium">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($cdnr as $note)
                        @php $nt = $note['nt'] ?? $note; @endphp
                        <tr class="border-b border-slate-50 last:border-0">
                            <td class="px-5 py-2.5">
                                <x-badge tone="{{ ($nt['ntty'] ?? '') === 'C' ? 'emerald' : 'amber' }}">
                                    {{ ($nt['ntty'] ?? '') === 'C' ? 'Credit Note' : 'Debit Note' }}
                                </x-badge>
                            </td>
                            <td class="px-3 font-mono text-xs">{{ $note['ctin'] ?? '—' }}</td>
                            <td class="px-3 font-medium text-slate-900">{{ $nt['nt_num'] ?? $nt['inum'] ?? '—' }}</td>
                            <td class="px-3 text-slate-500">{{ $nt['nt_dt'] ?? $nt['idt'] ?? '—' }}</td>
                            <td class="px-3 text-slate-500">{{ $nt['inum'] ?? '—' }}</td>
                            <td class="px-3 text-right font-medium tabular-nums">₹{{ number_format($nt['val'] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </x-card>
        @endif
    </div>

    {{-- 12. HSN Summary --}}
    <div x-show="tab === 'hsn'" class="mt-5">
        @if (empty($hsn))
            <x-card><x-empty title="No HSN data" message="HSN summary is computed from line items on your invoices." /></x-card>
        @else
            <x-card class="!p-0">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-100 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">HSN/SAC</th>
                            <th class="px-3 font-medium">UQC</th>
                            <th class="px-3 text-right font-medium">Quantity</th>
                            <th class="px-3 text-right font-medium">Rate %</th>
                            <th class="px-3 text-right font-medium">Taxable Value</th>
                            <th class="px-3 text-right font-medium">IGST</th>
                            <th class="px-3 text-right font-medium">CGST</th>
                            <th class="px-3 text-right font-medium">SGST</th>
                            <th class="px-5 text-right font-medium">Cess</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($hsn as $row)
                        <tr class="border-b border-slate-50 last:border-0">
                            <td class="px-5 py-2.5 font-mono text-xs font-medium">{{ $row['hsn_sc'] ?: '—' }}</td>
                            <td class="px-3 text-xs text-slate-500">{{ $row['uqc'] ?? 'NOS' }}</td>
                            <td class="px-3 text-right tabular-nums">{{ rtrim(rtrim(number_format($row['qty'], 3), '0'), '.') }}</td>
                            <td class="px-3 text-right tabular-nums">{{ rtrim(rtrim(number_format($row['rt'], 2), '0'), '.') }}%</td>
                            <td class="px-3 text-right tabular-nums font-medium">₹{{ number_format($row['txval'], 2) }}</td>
                            <td class="px-3 text-right tabular-nums text-slate-500">₹{{ number_format($row['iamt'], 2) }}</td>
                            <td class="px-3 text-right tabular-nums text-slate-500">₹{{ number_format($row['camt'], 2) }}</td>
                            <td class="px-3 text-right tabular-nums text-slate-500">₹{{ number_format($row['samt'], 2) }}</td>
                            <td class="px-5 text-right tabular-nums text-slate-500">₹{{ number_format($row['csamt'], 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot class="border-t border-slate-200 bg-slate-50 text-sm font-semibold">
                        <tr>
                            <td class="px-5 py-3" colspan="4">Total</td>
                            <td class="px-3 text-right tabular-nums">₹{{ number_format(collect($hsn)->sum('txval'), 2) }}</td>
                            <td class="px-3 text-right tabular-nums">₹{{ number_format(collect($hsn)->sum('iamt'), 2) }}</td>
                            <td class="px-3 text-right tabular-nums">₹{{ number_format(collect($hsn)->sum('camt'), 2) }}</td>
                            <td class="px-3 text-right tabular-nums">₹{{ number_format(collect($hsn)->sum('samt'), 2) }}</td>
                            <td class="px-5 text-right tabular-nums">₹{{ number_format(collect($hsn)->sum('csamt'), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </x-card>
        @endif
    </div>
</div>
@endsection