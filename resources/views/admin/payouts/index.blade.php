@extends('layouts.app')
@section('title', 'Payouts')

@section('content')
<x-page title="Payouts" :subtitle="'₹'.number_format($pendingTotal, 2).' pending across all partners'">
    <x-btn type="submit" form="approve-matured">Approve matured commissions</x-btn>
</x-page>

<form class="mb-4 flex gap-2">
    <x-select name="status" class="!w-48" onchange="this.form.submit()">
        <option value="">All statuses</option>
        @foreach (['pending','processing','paid','failed'] as $s)
            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
        @endforeach
    </x-select>
</form>

<x-card class="!p-0">
    @if ($payouts->isEmpty())
        <x-empty title="No payouts yet" message="Create one from a partner's page once they have cleared commissions." />
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Reference</th>
                        <th class="px-3 font-medium">Partner</th>
                        <th class="px-3 text-right font-medium">Amount</th>
                        <th class="px-3 font-medium">Method</th>
                        <th class="px-3 font-medium">Status</th>
                        <th class="px-5 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($payouts as $po)
                    <tr class="border-b border-slate-50 last:border-0 align-top">
                        <td class="px-5 py-3">
                            <div class="font-mono text-xs font-medium">{{ $po->reference }}</div>
                            <div class="text-xs text-slate-400">{{ $po->commission_count }} commission(s) · {{ $po->created_at->format('d M Y') }}</div>
                            @if ($po->utr)<div class="text-xs text-emerald-600">UTR {{ $po->utr }}</div>@endif
                        </td>
                        <td class="px-3 py-3">
                            <a href="{{ route('admin.partners.show', $po->partner) }}" class="text-indigo-600 hover:underline">{{ $po->partner->name }}</a>
                            <div class="text-xs text-slate-400">
                                {{ $po->method === 'upi' ? $po->partner->upi : $po->partner->bank_account }}
                            </div>
                        </td>
                        <td class="px-3 py-3 text-right font-medium tabular-nums">₹{{ number_format($po->amount, 2) }}</td>
                        <td class="px-3 py-3 uppercase text-xs text-slate-500">{{ $po->method }}</td>
                        <td class="px-3 py-3">
                            <x-badge tone="{{ ['paid'=>'emerald','processing'=>'indigo','pending'=>'amber','failed'=>'rose'][$po->status] }}">{{ ucfirst($po->status) }}</x-badge>
                        </td>
                        <td class="px-5 py-3">
                            @if ($po->status !== 'paid')
                                <form method="POST" action="{{ route('admin.payouts.update', $po) }}" class="flex flex-wrap items-center gap-1.5">
                                    @csrf @method('PUT')
                                    <input name="utr" placeholder="UTR / ref" class="w-28 rounded border-slate-300 text-xs">
                                    <button name="action" value="paid" class="rounded bg-emerald-600 px-2.5 py-1.5 text-xs font-medium text-white">Mark paid</button>
                                    <button name="action" value="failed" class="rounded border border-rose-200 px-2.5 py-1.5 text-xs text-rose-600">Failed</button>
                                </form>
                            @else
                                <span class="text-xs text-slate-400">{{ $po->paid_at?->format('d M Y') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-card>

<div class="mt-4">{{ $payouts->links() }}</div>

<form id="approve-matured" method="POST" action="{{ route('admin.commissions.approve') }}" class="hidden">@csrf</form>
@endsection
