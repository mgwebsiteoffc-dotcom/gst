@extends('layouts.app')
@section('title', 'GSTR-1')

@section('content')
@php
    // Both GST return views summarise what the service already computed, and
    // label every figure with the portal's own table names so filing is easy.
    $itemTotals = function (array $inv): array {
        $t = ['txval' => 0.0, 'iamt' => 0.0, 'camt' => 0.0, 'samt' => 0.0, 'csamt' => 0.0];

        foreach ($inv['itms'] ?? [] as $it) {
            foreach ($t as $k => $_) {
                $t[$k] += (float) ($it['itm_det'][$k] ?? 0);
            }
        }

        return $t;
    };

    $b2bInvoices = collect($data['b2b'] ?? [])->flatMap(
        fn ($group) => collect($group['inv'] ?? [])->map(fn ($inv) => $inv + ['ctin' => $group['ctin'] ?? null])
    );
    $b2clInvoices = collect($data['b2cl'] ?? [])->flatMap(fn ($g) => collect($g['inv'] ?? []));
    $cdnrNotes = collect($data['cdnr'] ?? []);
    $exportInvoices = collect($data['exp'] ?? [])->flatMap(fn ($g) => collect($g['inv'] ?? [])->map(fn ($i) => $i + ['exp_typ' => $g['exp_typ'] ?? null]));
    $b2cs = collect($data['b2cs'] ?? []);
    $hsnRows = collect($data['hsn']['data'] ?? []);

    // Plain keyed loop: array_map() re-indexes keys numerically, which would
    // strip the named totals and break the summary card below.
    $b2bTotals = [
        'txval' => 0.0, 'iamt' => 0.0, 'camt' => 0.0, 'samt' => 0.0, 'csamt' => 0.0,
    ];

    foreach ($b2bInvoices as $inv) {
        foreach ($itemTotals($inv) as $key => $amount) {
            $b2bTotals[$key] = ($b2bTotals[$key] ?? 0) + $amount;
        }
    }
    $b2cSmallTotals = [
        'txval' => $b2cs->sum('txval'),
        'iamt' => $b2cs->sum('iamt'),
        'camt' => $b2cs->sum('camt'),
        'samt' => $b2cs->sum('samt'),
    ];
    $money = fn ($v) => number_format((float) $v, 2);
@endphp

<x-page title="GSTR-1" :subtitle="__('Outward supplies for the period — cross-check before filing.')">
    <x-btn variant="secondary" :href="route('reports.gstr1', ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'json' => 1])">
        {{ __('Download JSON') }}
    </x-btn>
</x-page>

@include('reports._filter', ['export' => 'sales'])

@if (! $data['gstin'])
    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        {{ __('Add your GSTIN in Settings before filing — the return cannot be uploaded without it.') }}
    </div>
@endif

<div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach ([
        ['GSTIN', $data['gstin'] ?: '—'],
        [__('Period'), $from->format('M Y')],
        [__('B2B invoices'), number_format($b2bInvoices->count())],
        [__('Taxable value (B2B)'), '₹'.$money($b2bTotals['txval'] ?? 0)],
    ] as [$label, $value])
        <x-card>
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</div>
            <div class="mt-1 text-lg font-semibold {{ $label === 'GSTIN' ? 'font-mono text-base' : '' }}">{{ $value }}</div>
        </x-card>
    @endforeach
</div>

{{-- Table 4: B2B --}}
<x-card :title="__('B2B invoices (registered buyers)')" class="mb-5 !p-0 overflow-x-auto">
    @if ($b2bInvoices->isEmpty())
        <div class="p-5"><p class="text-sm text-slate-500">{{ __('No B2B invoices in this period.') }}</p></div>
    @else
        <table class="w-full min-w-[1000px] text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">{{ __('Buyer GSTIN') }}</th>
                    <th class="px-3 font-medium">{{ __('Invoice') }}</th>
                    <th class="px-3 font-medium">{{ __('Date') }}</th>
                    <th class="px-3 text-right font-medium">{{ __('Taxable') }}</th>
                    <th class="px-3 text-right font-medium">IGST</th>
                    <th class="px-3 text-right font-medium">CGST</th>
                    <th class="px-3 text-right font-medium">SGST</th>
                    <th class="px-3 text-right font-medium">{{ __('Invoice value') }}</th>
                    <th class="px-5 font-medium">IRN</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($b2bInvoices as $inv)
                @php $t = $itemTotals($inv); @endphp
                <tr class="border-b border-slate-50 last:border-0">
                    <td class="px-5 py-3 font-mono text-xs">{{ $inv['ctin'] }}</td>
                    <td class="px-3">{{ $inv['inum'] }}</td>
                    <td class="px-3 text-slate-600">{{ $inv['idt'] }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $money($t['txval']) }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $money($t['iamt']) }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $money($t['camt']) }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $money($t['samt']) }}</td>
                    <td class="px-3 text-right font-medium tabular-nums">{{ $money($inv['val']) }}</td>
                    <td class="px-5 text-xs">{{ ! empty($inv['irn']) ? __('Yes') : '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</x-card>

{{-- Table 5/7: B2C small --}}
<x-card :title="__('B2C small (unregistered buyers, by rate)')" class="mb-5 !p-0 overflow-x-auto">
    @if ($b2cs->isEmpty())
        <div class="p-5"><p class="text-sm text-slate-500">{{ __('No B2C supplies in this period.') }}</p></div>
    @else
        <table class="w-full min-w-[760px] text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">{{ __('Place of supply') }}</th>
                    <th class="px-3 font-medium">{{ __('Supply type') }}</th>
                    <th class="px-3 text-right font-medium">{{ __('Rate') }}</th>
                    <th class="px-3 text-right font-medium">{{ __('Taxable') }}</th>
                    <th class="px-3 text-right font-medium">IGST</th>
                    <th class="px-3 text-right font-medium">CGST</th>
                    <th class="px-5 text-right font-medium">SGST</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($b2cs as $row)
                <tr class="border-b border-slate-50 last:border-0">
                    <td class="px-5 py-3">{{ $row['pos'] }}</td>
                    <td class="px-3 text-slate-600">{{ $row['sply_ty'] === 'INTER' ? __('Inter-state') : __('Intra-state') }}</td>
                    <td class="px-3 text-right tabular-nums">{{ rtrim(rtrim(number_format((float) $row['rt'], 2), '0'), '.') }}%</td>
                    <td class="px-3 text-right tabular-nums">{{ $money($row['txval']) }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $money($row['iamt']) }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $money($row['camt']) }}</td>
                    <td class="px-5 text-right tabular-nums">{{ $money($row['samt']) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot class="border-t border-slate-200 bg-slate-50">
                <tr>
                    <td class="px-5 py-3 font-medium" colspan="3">{{ __('Totals') }}</td>
                    <td class="px-3 py-3 text-right font-medium tabular-nums">{{ $money($b2cSmallTotals['txval']) }}</td>
                    <td class="px-3 py-3 text-right tabular-nums">{{ $money($b2cSmallTotals['iamt']) }}</td>
                    <td class="px-3 py-3 text-right tabular-nums">{{ $money($b2cSmallTotals['camt']) }}</td>
                    <td class="px-5 py-3 text-right tabular-nums">{{ $money($b2cSmallTotals['samt']) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
</x-card>

{{-- B2C large --}}
@if ($b2clInvoices->isNotEmpty())
    <x-card :title="__('B2C large (inter-state above ₹1,00,000)')" class="mb-5 !p-0 overflow-x-auto">
        <table class="w-full min-w-[760px] text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">{{ __('Invoice') }}</th>
                    <th class="px-3 font-medium">{{ __('Date') }}</th>
                    <th class="px-3 font-medium">{{ __('Place of supply') }}</th>
                    <th class="px-3 text-right font-medium">IGST</th>
                    <th class="px-5 text-right font-medium">{{ __('Invoice value') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($b2clInvoices as $inv)
                @php $t = $itemTotals($inv); @endphp
                <tr class="border-b border-slate-50 last:border-0">
                    <td class="px-5 py-3">{{ $inv['inum'] }}</td>
                    <td class="px-3 text-slate-600">{{ $inv['idt'] }}</td>
                    <td class="px-3">{{ $inv['pos'] }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $money($t['iamt']) }}</td>
                    <td class="px-5 text-right font-medium tabular-nums">{{ $money($inv['val']) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </x-card>
@endif

{{-- Credit / debit notes --}}
@if ($cdnrNotes->isNotEmpty())
    <x-card :title="__('Credit and debit notes')" class="mb-5 !p-0 overflow-x-auto">
        <table class="w-full min-w-[860px] text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">{{ __('Buyer GSTIN') }}</th>
                    <th class="px-3 font-medium">{{ __('Type') }}</th>
                    <th class="px-3 font-medium">{{ __('Number') }}</th>
                    <th class="px-3 font-medium">{{ __('Date') }}</th>
                    <th class="px-3 text-right font-medium">IGST</th>
                    <th class="px-3 text-right font-medium">CGST</th>
                    <th class="px-3 text-right font-medium">SGST</th>
                    <th class="px-5 text-right font-medium">{{ __('Value') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($cdnrNotes as $note)
                @php $nt = $note['nt'] ?? []; $t = $itemTotals($nt); @endphp
                <tr class="border-b border-slate-50 last:border-0">
                    <td class="px-5 py-3 font-mono text-xs">{{ $note['ctin'] ?: __('Unregistered') }}</td>
                    <td class="px-3">{{ ($nt['ntty'] ?? 'C') === 'C' ? __('Credit note') : __('Debit note') }}</td>
                    <td class="px-3">{{ $nt['nt_num'] ?? $nt['inum'] ?? '—' }}</td>
                    <td class="px-3 text-slate-600">{{ $nt['nt_dt'] ?? $nt['idt'] ?? '—' }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $money($t['iamt']) }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $money($t['camt']) }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $money($t['samt']) }}</td>
                    <td class="px-5 text-right font-medium tabular-nums">{{ $money($nt['val'] ?? 0) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </x-card>
@endif

{{-- Exports --}}
@if ($exportInvoices->isNotEmpty())
    <x-card :title="__('Exports')" class="mb-5 !p-0 overflow-x-auto">
        <table class="w-full min-w-[760px] text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">{{ __('Invoice') }}</th>
                    <th class="px-3 font-medium">{{ __('Date') }}</th>
                    <th class="px-3 font-medium">{{ __('Tax treatment') }}</th>
                    <th class="px-3 text-right font-medium">{{ __('Taxable') }}</th>
                    <th class="px-5 text-right font-medium">IGST</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($exportInvoices as $inv)
                @php $t = $itemTotals($inv); @endphp
                <tr class="border-b border-slate-50 last:border-0">
                    <td class="px-5 py-3">{{ $inv['inum'] }}</td>
                    <td class="px-3 text-slate-600">{{ $inv['idt'] }}</td>
                    <td class="px-3">{{ ($inv['exp_typ'] ?? '') === 'WPAY' ? __('With payment of tax') : __('Without payment (LUT)') }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $money($t['txval']) }}</td>
                    <td class="px-5 text-right tabular-nums">{{ $money($t['iamt']) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </x-card>
@endif

{{-- Table 12: HSN summary --}}
<x-card :title="__('HSN summary (Table 12)')" class="!p-0 overflow-x-auto">
    @if ($hsnRows->isEmpty())
        <div class="p-5"><p class="text-sm text-slate-500">{{ __('No HSN data in this period.') }}</p></div>
    @else
        <table class="w-full min-w-[900px] text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">HSN/SAC</th>
                    <th class="px-3 font-medium">{{ __('Description') }}</th>
                    <th class="px-3 font-medium">{{ __('Unit') }}</th>
                    <th class="px-3 text-right font-medium">{{ __('Quantity') }}</th>
                    <th class="px-3 text-right font-medium">{{ __('Rate') }}</th>
                    <th class="px-3 text-right font-medium">{{ __('Taxable') }}</th>
                    <th class="px-3 text-right font-medium">IGST</th>
                    <th class="px-3 text-right font-medium">CGST</th>
                    <th class="px-5 text-right font-medium">SGST</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($hsnRows as $row)
                <tr class="border-b border-slate-50 last:border-0">
                    <td class="px-5 py-3 font-mono text-xs">{{ $row['hsn_sc'] ?: '—' }}</td>
                    <td class="px-3 text-slate-500">{{ $hsnLookup[$row['hsn_sc']] ?? '' }}</td>
                    <td class="px-3 text-xs">{{ $row['uqc'] }}</td>
                    <td class="px-3 text-right tabular-nums">{{ rtrim(rtrim(number_format((float) $row['qty'], 3), '0'), '.') }}</td>
                    <td class="px-3 text-right tabular-nums">{{ rtrim(rtrim(number_format((float) $row['rt'], 2), '0'), '.') }}%</td>
                    <td class="px-3 text-right tabular-nums">{{ $money($row['txval']) }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $money($row['iamt']) }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $money($row['camt']) }}</td>
                    <td class="px-5 text-right tabular-nums">{{ $money($row['samt']) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</x-card>

<p class="mt-3 text-xs text-slate-500">
    {{ __('Download the JSON and upload it to the GST offline utility, or use it to tally with your filing. Figures exclude cancelled and draft documents.') }}
</p>
@endsection
