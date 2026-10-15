@extends('layouts.app')
@section('title', $party->name)

@section('content')
<x-page :title="$party->name" :subtitle="$party->gstin ? 'GSTIN '.$party->gstin : ucfirst($party->gst_treatment)" :back="route('parties.index')">
    <x-btn variant="secondary" :href="route('reports.ledger', $party)">{{ __('View ledger') }}</x-btn>
    <x-btn variant="secondary" :href="route('parties.edit', $party)">{{ __('Edit') }}</x-btn>
    <x-btn variant="primary" :href="route('documents.create', ['type' => 'invoice'])">+ {{ __('New invoice') }}</x-btn>
</x-page>

<div class="mb-5 grid gap-4 sm:grid-cols-3">
    @foreach ([
        [__('Outstanding'), '₹'.number_format($party->outstanding(), 2)],
        [__('Documents'), $party->documents->count()],
        [__('Credit period'), $party->credit_days.' '.__('days')],
    ] as [$k, $v])
        <x-card><div class="text-xs uppercase tracking-wide text-slate-500">{{ $k }}</div>
            <div class="mt-1 text-lg font-semibold">{{ $v }}</div></x-card>
    @endforeach
</div>

<x-card :title="__('Recent documents')" class="!p-0 overflow-x-auto">
    @if ($party->documents->isEmpty())
        <x-empty :title="__('No documents yet.')" :message="__('Invoices for this contact will appear here.')"/>
    @else
        <table class="w-full min-w-[560px] text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-5 py-3 font-medium">{{ __('Number') }}</th><th class="px-3 font-medium">{{ __('Date') }}</th>
                    <th class="px-3 text-right font-medium">{{ __('Total') }}</th><th class="px-3 text-right font-medium">{{ __('Due') }}</th><th class="px-5 font-medium">{{ __('Status') }}</th></tr>
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
