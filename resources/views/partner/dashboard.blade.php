@extends('layouts.app')
@section('title', 'Partner Dashboard')

@section('content')
<x-page :title="'Welcome, '.$partner->name" :subtitle="$partner->typeLabel().' · '.($partner->commissionPlan?->describe() ?? 'No plan assigned yet')" />

{{-- Referral link --}}
<x-card title="Your referral link" class="mb-5">
    <div x-data="{ url: @js($partner->referralUrl()), copied: false }" class="flex flex-wrap items-center gap-2">
        <code class="flex-1 min-w-64 rounded-lg bg-slate-50 px-3 py-2.5 font-mono text-sm text-slate-700" x-text="url"></code>
        <x-btn type="button" variant="primary"
               @click="navigator.clipboard.writeText(url); copied = true; setTimeout(() => copied = false, 2000)">
            <span x-text="copied ? 'Copied!' : 'Copy link'"></span>
        </x-btn>
    </div>
    <p class="mt-3 text-sm text-slate-500">
        Anyone who signs up within {{ $partner->commissionPlan?->cookie_days ?? 60 }} days of clicking this link
        is credited to you. Your code is <span class="font-mono font-medium text-slate-700">{{ $partner->referral_code }}</span>.
    </p>
</x-card>

<div class="mb-5 grid gap-4 sm:grid-cols-4">
    @foreach ($stats as $label => $value)
        <x-card><div class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</div>
            <div class="mt-1 text-2xl font-semibold">{{ $value }}</div></x-card>
    @endforeach
</div>

<div class="mb-5 grid gap-4 sm:grid-cols-3">
    @foreach ([
        ['Available to withdraw', $partner->availableBalance(), 'emerald'],
        ['Pending (in hold)', $partner->pendingBalance(), 'amber'],
        ['Paid to date', $partner->totalPaid(), 'slate'],
    ] as [$label, $paise, $tone])
        <x-card>
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</div>
            <div class="mt-1 text-2xl font-semibold text-{{ $tone }}-600">₹{{ number_format($paise / 100, 2) }}</div>
        </x-card>
    @endforeach
</div>

<div class="grid gap-5 lg:grid-cols-3">
    <x-card title="Referred businesses" class="lg:col-span-2 !p-0">
        @if ($referrals->isEmpty())
            <x-empty title="No signups yet" message="Share your link to start earning." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-100 text-left text-xs uppercase text-slate-500">
                        <tr><th class="px-5 py-2.5 font-medium">Business</th><th class="px-3 font-medium">Status</th><th class="px-5 font-medium">Joined</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($referrals as $r)
                        <tr class="border-b border-slate-50 last:border-0">
                            <td class="px-5 py-2.5">{{ $r->tenant?->name ?? '—' }}</td>
                            <td class="px-3"><x-badge tone="{{ $r->status === 'converted' ? 'emerald' : 'slate' }}">{{ ucfirst($r->status) }}</x-badge></td>
                            <td class="px-5 text-slate-500">{{ $r->created_at->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-card title="Payout details">
        <form method="POST" action="{{ route('partner.profile') }}" class="grid gap-3">
            @csrf @method('PUT')
            <x-field label="Name" name="name"><x-input name="name" value="{{ $partner->name }}" required /></x-field>
            <x-field label="Phone" name="phone"><x-input name="phone" value="{{ $partner->phone }}" /></x-field>
            <x-field label="UPI ID" name="upi" hint="Fastest way to get paid."><x-input name="upi" value="{{ $partner->upi }}" /></x-field>
            <x-field label="Bank account" name="bank_account"><x-input name="bank_account" value="{{ $partner->bank_account }}" /></x-field>
            <x-field label="IFSC" name="bank_ifsc"><x-input name="bank_ifsc" class="font-mono uppercase" value="{{ $partner->bank_ifsc }}" /></x-field>
            <x-field label="PAN" name="pan" hint="Required for TDS compliance."><x-input name="pan" class="font-mono uppercase" value="{{ $partner->pan }}" /></x-field>
            <x-btn variant="primary" type="submit">Save details</x-btn>
        </form>
    </x-card>

    <x-card title="Recent commissions" class="lg:col-span-2 !p-0">
        @if ($commissions->isEmpty())
            <x-empty title="No commissions yet" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-100 text-left text-xs uppercase text-slate-500">
                        <tr><th class="px-5 py-2.5 font-medium">Date</th><th class="px-3 font-medium">Month</th>
                            <th class="px-3 text-right font-medium">Amount</th><th class="px-5 font-medium">Status</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($commissions as $c)
                        <tr class="border-b border-slate-50 last:border-0">
                            <td class="px-5 py-2.5 text-slate-500">{{ $c->created_at->format('d M Y') }}</td>
                            <td class="px-3 text-slate-500">#{{ $c->month_number }}</td>
                            <td class="px-3 text-right font-medium tabular-nums">₹{{ number_format($c->amount_paise / 100, 2) }}</td>
                            <td class="px-5"><x-badge tone="{{ ['paid'=>'emerald','approved'=>'indigo','pending'=>'amber','reversed'=>'rose'][$c->status] }}">{{ ucfirst($c->status) }}</x-badge></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-card title="Payout history" class="!p-0">
        @if ($payouts->isEmpty())
            <x-empty title="No payouts yet" />
        @else
            <ul class="divide-y divide-slate-50 text-sm">
                @foreach ($payouts as $po)
                    <li class="flex items-center justify-between px-5 py-3">
                        <div>
                            <div class="font-mono text-xs">{{ $po->reference }}</div>
                            <div class="text-xs text-slate-400">{{ $po->created_at->format('d M Y') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-medium">₹{{ number_format($po->amount, 2) }}</div>
                            <x-badge tone="{{ $po->status === 'paid' ? 'emerald' : 'amber' }}">{{ ucfirst($po->status) }}</x-badge>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</div>
@endsection
