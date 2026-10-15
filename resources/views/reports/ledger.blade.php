@extends('layouts.app')
@section('title', 'Ledger — '.$ledger['party']->name)

@section('content')
<x-page :title="$ledger['party']->name.' — Ledger'"
        :subtitle="'Statement of account'" :back="route('parties.show', $ledger['party'])" />
@include('reports._filter')

<x-card class="!p-0 overflow-x-auto">
    <table class="w-full min-w-[600px] text-sm">
        <thead class="border-b border-slate-100 text-left text-xs uppercase text-slate-500">
            <tr><th class="px-5 py-3 font-medium">Date</th><th class="px-3 font-medium">Particulars</th>
                <th class="px-3 text-right font-medium">Debit</th><th class="px-3 text-right font-medium">Credit</th>
                <th class="px-5 text-right font-medium">Balance</th></tr>
        </thead>
        <tbody>
            <tr class="border-b border-slate-50 bg-slate-50 text-slate-500">
                <td class="px-5 py-2.5" colspan="4">Opening balance</td>
                <td class="px-5 py-2.5 text-right tabular-nums">₹{{ number_format($ledger['opening'], 2) }}</td>
            </tr>
            @forelse ($ledger['rows'] as $row)
                <tr class="border-b border-slate-50">
                    <td class="px-5 py-2.5">{{ $row['date']->format('d M Y') }}</td>
                    <td class="px-3">{{ $row['particulars'] }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $row['debit'] ? number_format($row['debit'], 2) : '—' }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $row['credit'] ? number_format($row['credit'], 2) : '—' }}</td>
                    <td class="px-5 text-right tabular-nums">₹{{ number_format($row['balance'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No transactions in this period.</td></tr>
            @endforelse
            <tr class="bg-slate-50 font-semibold">
                <td class="px-5 py-3" colspan="2">Closing balance</td>
                <td class="px-3 text-right tabular-nums">{{ number_format($ledger['total_debit'], 2) }}</td>
                <td class="px-3 text-right tabular-nums">{{ number_format($ledger['total_credit'], 2) }}</td>
                <td class="px-5 text-right tabular-nums">₹{{ number_format($ledger['closing'], 2) }}</td>
            </tr>
        </tbody>
    </table>
</x-card>
@endsection
