@extends('layouts.app')
@section('title', 'Payments Received')
@section('content')
<div class="max-w-full overflow-x-hidden">
    <x-page title="Payments Received" subtitle="Track invoice-wise payments and outstanding balances." />
    {{-- Summary --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-400">Total Invoiced</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">₹{{ number_format($totalInvoiced, 0) }}</div>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
            <div class="text-xs font-medium uppercase tracking-wider text-emerald-600">Total Received</div>
            <div class="mt-2 text-2xl font-bold text-emerald-700">₹{{ number_format($totalReceived, 0) }}</div>
            <div class="mt-2 h-1.5 rounded-full bg-emerald-200 overflow-hidden">
                @php $pct = $totalInvoiced > 0 ? round(($totalReceived / $totalInvoiced) * 100) : 0; @endphp
                <div class="h-full rounded-full bg-emerald-500" style="width: {{ $pct }}%"></div>
            </div>
            <div class="mt-1 text-xs text-emerald-600">{{ $pct }}% collected</div>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
            <div class="text-xs font-medium uppercase tracking-wider text-amber-600">Outstanding</div>
            <div class="mt-2 text-2xl font-bold text-amber-700">₹{{ number_format($totalOutstanding, 0) }}</div>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-5">
            <div class="text-xs font-medium uppercase tracking-wider text-rose-600">Overdue</div>
            <div class="mt-2 text-2xl font-bold text-rose-700">{{ $overdueCount }}</div>
            <div class="text-xs text-rose-500">₹{{ number_format($overdueAmount, 0) }} pending</div>
        </div>
    </div>
    {{-- Filters --}}
    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[160px] flex-1">
                <label class="mb-1 block text-xs font-medium text-slate-500">Search</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Invoice # or party name..."
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Status</label>
                <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">All</option>
                    @foreach (['sent' => 'Sent', 'partially_paid' => 'Partial', 'paid' => 'Paid', 'overdue' => 'Overdue'] as $v => $l)
                        <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">From</label>
                <input type="date" name="from" value="{{ request('from') }}"
                       class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">To</label>
                <input type="date" name="to" value="{{ request('to') }}"
                       class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Party</label>
                <select name="party_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm max-w-[160px]">
                    <option value="">All Parties</option>
                    @foreach ($parties as $p)
                        <option value="{{ $p->id }}" @selected(request('party_id') == $p->id)>{{ Str::limit($p->name, 18) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Filter</button>
                @if (request()->hasAny(['q', 'status', 'party_id', 'from', 'to']))
                    <a href="{{ route('payments.index') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">Clear</a>
                @endif
            </div>
        </form>
    </div>
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main: Invoice List --}}
        <div class="lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                {{-- Invoice Rows --}}
                <div class="divide-y divide-slate-100">
                    @forelse ($documents as $doc)
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 hover:bg-slate-50/60 sm:flex-nowrap">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('payments.show', $doc) }}" class="font-medium text-indigo-600 hover:underline">{{ $doc->number }}</a>
                                    <x-badge :status="$doc->status" />
                                </div>
                                <div class="mt-0.5 flex items-center gap-2 text-xs text-slate-500">
                                    <span class="truncate">{{ $doc->party?->name ?: 'Walk-in' }}</span>
                                    <span>·</span>
                                    <span class="whitespace-nowrap">{{ $doc->issue_date->format('d M Y') }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-sm sm:gap-6">
                                <div class="text-right">
                                    <div class="text-xs text-slate-400">Total</div>
                                    <div class="tabular-nums text-slate-700">₹{{ number_format((float)$doc->grand_total, 0) }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-slate-400">Paid</div>
                                    <div class="tabular-nums text-emerald-600">₹{{ number_format((float)$doc->amount_paid, 0) }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-slate-400">Due</div>
                                    <div class="tabular-nums font-semibold {{ (float)$doc->balance_due > 0 ? 'text-rose-600' : 'text-slate-300' }}">
                                        ₹{{ number_format((float)$doc->balance_due, 0) }}
                                    </div>
                                </div>
                                @if ((float)$doc->balance_due > 0)
                                    <a href="{{ route('payments.show', $doc) }}"
                                       class="shrink-0 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700">
                                        + Pay
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-12 text-center text-sm text-slate-400">No invoices found.</div>
                    @endforelse
                </div>
                {{-- Pagination at bottom --}}
                @if ($documents->hasPages())
                    <div class="border-t border-slate-100 px-4 py-3">
                        {{ $documents->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
        {{-- Sidebar: Recent Payments --}}
        <div>
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h2 class="text-sm font-medium text-slate-900">Recent Payments</h2>
                </div>
                <div class="divide-y divide-slate-50">
                    @forelse ($recentPayments as $p)
                        <div class="px-4 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-emerald-700">₹{{ number_format((float)$p->amount, 0) }}</span>
                                <span class="text-xs text-slate-400 whitespace-nowrap">{{ $p->paid_on->format('d M') }}</span>
                            </div>
                            <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                                @if ($p->document)
                                    <a href="{{ route('payments.show', $p->document) }}" class="text-indigo-600 hover:underline">{{ $p->document->number }}</a>
                                    <span>·</span>
                                @endif
                                <span class="truncate">{{ $p->party?->name ?: '—' }}</span>
                            </div>
                            <div class="mt-1 flex items-center gap-2 text-xs">
                                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-slate-500">{{ ucfirst($p->method) }}</span>
                                @if ($p->reference)
                                    <span class="text-slate-400 truncate">{{ $p->reference }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-slate-400">No payments yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection