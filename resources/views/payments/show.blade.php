@extends('layouts.app')
@section('title', 'Payments — ' . $document->number)
@section('content')
<div class="max-w-full overflow-x-hidden">
    <x-page :title="$document->number" :subtitle="($document->party?->name ?: 'Walk-in') . ' · ' . $document->issue_date->format('d M Y')" :back="route('payments.index')">
        <x-badge :status="$document->status" />
    </x-page>
    {{-- Balance Bar --}}
    @php
        $pct = $document->grand_total > 0 ? min(100, round(((float)$document->amount_paid / (float)$document->grand_total) * 100)) : 0;
        $balance = (float)$document->balance_due;
    @endphp
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <div class="text-xs font-medium uppercase tracking-wider text-slate-400">Invoice Total</div>
                <div class="mt-1 text-3xl font-bold text-slate-900">₹{{ number_format((float)$document->grand_total, 2) }}</div>
            </div>
            <div class="text-right">
                <div class="text-xs font-medium uppercase tracking-wider text-emerald-600">Paid: ₹{{ number_format((float)$document->amount_paid, 2) }}</div>
                <div class="mt-1 text-xl font-bold {{ $balance > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                    Balance: ₹{{ number_format($balance, 2) }}
                </div>
            </div>
        </div>
        <div class="mt-4 h-3 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $pct }}%"></div>
        </div>
        <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
            <span>{{ $pct }}% collected</span>
            @if ($document->due_date && $balance > 0)
                <span class="{{ $document->due_date->isPast() ? 'text-rose-600 font-medium' : '' }}">
                    {{ $document->due_date->isPast() ? 'Overdue by ' . $document->due_date->diffInDays(now()) . ' days' : 'Due ' . $document->due_date->format('d M Y') }}
                </span>
            @endif
        </div>
    </div>
    <div class="grid gap-6 lg:grid-cols-5">
        {{-- Payment History --}}
        <div class="lg:col-span-3">
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <h2 class="text-sm font-medium text-slate-900">Payment History</h2>
                    <span class="text-xs text-slate-400">{{ $document->payments->count() }} payment(s)</span>
                </div>
                @if ($document->payments->isEmpty())
                    <div class="px-4 py-10 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-3">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg>
                        </div>
                        <p class="text-sm font-medium text-slate-700">No payments yet</p>
                        <p class="mt-1 text-xs text-slate-400">Record the first payment using the form.</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($document->payments as $payment)
                            <div class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-slate-50/60">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-slate-900">₹{{ number_format((float)$payment->amount, 2) }}</span>
                                            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500">{{ ucfirst($payment->method) }}</span>
                                        </div>
                                        <div class="mt-0.5 flex items-center gap-2 text-xs text-slate-400">
                                            <span>{{ $payment->paid_on->format('d M Y') }}</span>
                                            @if ($payment->reference)
                                                <span>·</span>
                                                <span class="font-mono truncate max-w-[120px]">{{ $payment->reference }}</span>
                                            @endif
                                        </div>
                                        @if ($payment->note)
                                            <p class="mt-0.5 text-xs text-slate-500 truncate max-w-[200px]">{{ $payment->note }}</p>
                                        @endif
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('payments.destroy', [$document, $payment]) }}"
                                      onsubmit="return confirm('Delete this payment? Invoice balance will be recalculated.')"
                                      class="shrink-0">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="rounded p-1.5 text-slate-300 hover:bg-rose-50 hover:text-rose-500 transition">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                    <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-600">Total Paid</span>
                        <span class="font-bold text-emerald-700 tabular-nums">₹{{ number_format($document->payments->sum('amount'), 2) }}</span>
                    </div>
                @endif
            </div>
        </div>
        {{-- Record Payment Form --}}
        <div class="lg:col-span-2">
            @if ($balance > 0)
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <h2 class="text-sm font-medium text-slate-900">Record Payment</h2>
                    </div>
                    <form method="POST" action="{{ route('payments.store', $document) }}" class="p-4 space-y-4">
                        @csrf
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Amount</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">₹</span>
                                <input type="number" name="amount" step="0.01" min="0.01" max="{{ $balance }}"
                                       value="{{ old('amount', $balance) }}" required
                                       class="w-full rounded-lg border border-slate-300 py-2.5 pl-7 pr-3 text-sm font-semibold focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <p class="mt-1 text-xs text-slate-400">Max: ₹{{ number_format($balance, 2) }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Date</label>
                                <input type="date" name="paid_on" value="{{ old('paid_on', now()->toDateString()) }}" required
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Method</label>
                                <select name="method" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                                    @foreach (['bank' => 'Bank/NEFT', 'upi' => 'UPI', 'cash' => 'Cash', 'card' => 'Card', 'cheque' => 'Cheque'] as $v => $l)
                                        <option value="{{ $v }}" @selected(old('method') === $v)>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Reference / UTR</label>
                            <input type="text" name="reference" value="{{ old('reference') }}" placeholder="Transaction ID..."
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Note</label>
                            <textarea name="note" rows="2" placeholder="Optional..."
                                      class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">{{ old('note') }}</textarea>
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                            Record Payment
                        </button>
                    </form>
                </div>
            @else
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-8 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 mb-3">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </div>
                    <p class="text-lg font-semibold text-emerald-800">Fully Paid</p>
                    <p class="mt-1 text-sm text-emerald-600">This invoice has no outstanding balance.</p>
                </div>
            @endif
            {{-- Quick Invoice Info --}}
            <div class="mt-5 rounded-xl border border-slate-200 bg-white p-4">
                <h3 class="text-xs font-medium uppercase tracking-wider text-slate-400 mb-3">Invoice Details</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Party</span>
                        <span class="font-medium text-slate-800">{{ $document->party?->name ?: '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Issue Date</span>
                        <span class="text-slate-700">{{ $document->issue_date->format('d M Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Due Date</span>
                        <span class="text-slate-700">{{ $document->due_date?->format('d M Y') ?: '—' }}</span>
                    </div>
                    <div class="flex justify-between border-t border-slate-100 pt-2 mt-2">
                        <span class="text-slate-500">Taxable</span>
                        <span class="tabular-nums text-slate-700">₹{{ number_format((float)$document->taxable_value, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tax</span>
                        <span class="tabular-nums text-slate-700">₹{{ number_format((float)$document->tax_total, 2) }}</span>
                    </div>
                </div>
                <div class="mt-3 border-t border-slate-100 pt-3">
                    <a href="{{ route('documents.show', $document) }}" class="text-sm text-indigo-600 hover:underline font-medium">View full invoice →</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection