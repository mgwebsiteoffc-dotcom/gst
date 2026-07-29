@extends('layouts.app')
@section('title', $party->name)

@section('content')
<x-page :title="$party->name" :subtitle="$party->gstin ? 'GSTIN '.$party->gstin : ucfirst($party->gst_treatment)" :back="route('parties.index')">
    <x-btn variant="secondary" :href="route('reports.ledger', $party)">View ledger</x-btn>
    <x-btn variant="secondary" :href="route('parties.edit', $party)">Edit</x-btn>
    <x-btn variant="primary" :href="route('documents.create', ['type' => 'invoice'])">+ New invoice</x-btn>
</x-page>

<div class="mb-5 grid gap-4 sm:grid-cols-3">
    @foreach ([
        ['Outstanding', '₹'.number_format($party->outstanding(), 2)],
        ['Documents', $party->documents->count()],
        ['Credit period', $party->credit_days.' days'],
    ] as [$k, $v])
        <x-card><div class="text-xs uppercase tracking-wide text-slate-500">{{ $k }}</div>
            <div class="mt-1 text-lg font-semibold">{{ $v }}</div></x-card>
    @endforeach
</div>

<x-card title="Recent documents" class="!p-0">
    @if ($party->documents->isEmpty())
        <x-empty title="No documents yet" message="Invoices for this contact will appear here."/>
    @else
        <table class="w-full text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-5 py-3 font-medium">Number</th><th class="px-3 font-medium">Date</th>
                    <th class="px-3 text-right font-medium">Total</th><th class="px-3 text-right font-medium">Due</th><th class="px-5 font-medium">Status</th></tr>
            </thead>
            <tbody>
            @foreach ($party->documents as $d)
                <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/60">
                    <td class="px-5 py-3"><a href="{{ route('documents.show', $d) }}" class="font-medium text-indigo-600">{{ $d->number }}</a></td>
                    <td class="px-3">{{ $d->issue_date->format('d M Y') }}</td>
                    <td class="px-3 text-right tabular-nums">₹{{ number_format($d->grand_total, 2) }}</td>
                    <td class="px-3 text-right tabular-nums">₹{{ number_format($d->balance_due, 2) }}</td>
                    <td class="px-5"><x-badge :status="$d->status"/></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</x-card>
@endsection
