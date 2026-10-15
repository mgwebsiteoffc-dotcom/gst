@extends('layouts.app')
@section('title', 'Profit & Loss')

@section('content')
<x-page title="Profit and Loss" subtitle="Revenue, costs and what you actually kept." />
@include('reports._filter', ['export' => 'daily'])

<div class="mb-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ([
        ['Revenue', $data['revenue'], 'slate'],
        ['Cost of goods', $data['cogs'], 'slate'],
        ['Gross profit', $data['gross_profit'], $data['gross_profit'] >= 0 ? 'emerald' : 'rose'],
        ['Net profit', $data['net_profit'], $data['net_profit'] >= 0 ? 'emerald' : 'rose'],
    ] as [$label, $value, $tone])
        <x-card>
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</div>
            <div class="mt-1 text-2xl font-semibold {{ $tone === 'emerald' ? 'text-emerald-600' : ($tone === 'rose' ? 'text-rose-600' : 'text-slate-900') }}">
                ₹{{ number_format($value, 2) }}
            </div>
        </x-card>
    @endforeach
</div>

<div class="grid gap-5 lg:grid-cols-3">
    <x-card title="Statement" class="lg:col-span-2 !p-0 overflow-x-auto">
        <table class="w-full min-w-[560px] text-sm">
            <tbody>
            @foreach ([
                ['Sales revenue (net of tax)', $data['revenue'], false],
                ['Less: sales returns', -$data['returns'], false],
                ['Less: cost of goods purchased', -$data['cogs'], false],
                ['Gross profit', $data['gross_profit'], true],
                ['Less: operating expenses', -$data['expenses'], false],
                ['Net profit', $data['net_profit'], true],
            ] as [$label, $value, $bold])
                <tr class="border-b border-slate-50 last:border-0 {{ $bold ? 'bg-slate-50 font-semibold' : '' }}">
                    <td class="px-5 py-2.5">{{ $label }}</td>
                    <td class="px-5 py-2.5 text-right tabular-nums {{ $value < 0 ? 'text-rose-600' : '' }}">
                        ₹{{ number_format($value, 2) }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </x-card>

    <x-card title="At a glance">
        <dl class="space-y-3 text-sm">
            @foreach ([
                'Invoices raised' => number_format($data['invoice_count']),
                'Average invoice' => '₹'.number_format($data['average_invoice'], 2),
                'Net margin' => $data['margin'].'%',
            ] as $k => $v)
                <div class="flex justify-between border-b border-slate-50 pb-2">
                    <dt class="text-slate-500">{{ $k }}</dt><dd class="font-medium">{{ $v }}</dd>
                </div>
            @endforeach
        </dl>
    </x-card>
</div>

<x-card title="Daily sales" class="mt-5 !p-0 overflow-x-auto">
    <div class="max-h-96 overflow-y-auto">
        <table class="w-full min-w-[560px] text-sm">
            <thead class="sticky top-0 border-b border-slate-100 bg-white text-left text-xs uppercase text-slate-500">
                <tr><th class="px-5 py-2.5 font-medium">Date</th><th class="px-3 text-right font-medium">Invoices</th>
                    <th class="px-3 text-right font-medium">Taxable</th><th class="px-3 text-right font-medium">Tax</th>
                    <th class="px-5 text-right font-medium">Total</th></tr>
            </thead>
            <tbody>
            @foreach ($daily as $row)
                <tr class="border-b border-slate-50 last:border-0 {{ $row['invoices'] === 0 ? 'text-slate-300' : '' }}">
                    <td class="px-5 py-2">{{ $row['date']->format('d M Y') }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $row['invoices'] }}</td>
                    <td class="px-3 text-right tabular-nums">{{ number_format($row['taxable'], 2) }}</td>
                    <td class="px-3 text-right tabular-nums">{{ number_format($row['tax'], 2) }}</td>
                    <td class="px-5 text-right font-medium tabular-nums">{{ number_format($row['total'], 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-card>
@endsection
