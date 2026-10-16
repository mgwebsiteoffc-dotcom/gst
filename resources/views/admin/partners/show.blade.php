@extends('layouts.app')
@section('title', $partner->name)

@section('content')
<x-page :title="$partner->name" :subtitle="$partner->typeLabel().' · '.$partner->email" :back="route('admin.partners.index')">
    @if ($partner->availableBalance() > 0)
        <x-btn variant="success" type="submit" form="payout-form">
            Pay ₹{{ number_format($partner->availableBalance() / 100, 2) }}
        </x-btn>
    @endif
</x-page>

<div class="mb-5 grid gap-4 sm:grid-cols-4">
    @foreach ([
        ['Referral code', $partner->referral_code],
        ['Lifetime earned', '₹'.number_format($partner->lifetimeEarned() / 100, 2)],
        ['Available now', '₹'.number_format($partner->availableBalance() / 100, 2)],
        ['Already paid', '₹'.number_format($partner->totalPaid() / 100, 2)],
    ] as [$label, $value])
        <x-card>
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</div>
            <div class="mt-1 text-lg font-semibold">{{ $value }}</div>
        </x-card>
    @endforeach
</div>

<div class="grid gap-5 lg:grid-cols-3">
    <x-card title="Partner settings" class="lg:col-span-1">
        <form method="POST" action="{{ route('admin.partners.update', $partner) }}" class="grid gap-4">
            @csrf @method('PUT')

            <x-field label="Status" name="status" required>
                <x-select name="status">
                    @foreach (['pending' => 'Pending review', 'approved' => 'Approved', 'suspended' => 'Suspended', 'rejected' => 'Rejected'] as $v => $l)
                        <option value="{{ $v }}" @selected($partner->status === $v)>{{ $l }}</option>
                    @endforeach
                </x-select>
            </x-field>

            <x-field label="Commission plan" name="commission_plan_id" hint="Required before any commission is earned.">
                <x-select name="commission_plan_id">
                    <option value="">— None —</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}" @selected($partner->commission_plan_id === $plan->id)>
                            {{ $plan->name }} ({{ $plan->describe() }})
                        </option>
                    @endforeach
                </x-select>
            </x-field>

            <x-field label="Tier" name="tier">
                <x-select name="tier">
                    @foreach (\App\Models\Partner::TIERS as $v => $l)
                        <option value="{{ $v }}" @selected($partner->tier === $v)>{{ $l }}</option>
                    @endforeach
                </x-select>
            </x-field>

            <x-field label="Internal note" name="admin_note">
                <textarea name="admin_note" rows="3"
                          class="block w-full rounded-lg border border-slate-300 p-2 text-sm">{{ $partner->admin_note }}</textarea>
            </x-field>

            <x-btn variant="primary" type="submit">Save</x-btn>
        </form>
    </x-card>

    <div class="space-y-5 lg:col-span-2">
        <x-card title="Application details">
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                @foreach ([
                    'Phone' => $partner->phone,
                    'Company' => $partner->company,
                    'City' => $partner->city,
                    'Territory' => $partner->territory,
                    'Current business' => $partner->current_business,
                    'Years in business' => $partner->years_experience,
                    'Website' => $partner->website,
                    'Audience size' => $partner->audience_size ? number_format($partner->audience_size) : null,
                    'GSTIN' => $partner->gstin,
                    'PAN' => $partner->pan,
                    'UPI' => $partner->upi,
                    'Bank A/c' => $partner->bank_account,
                    'IFSC' => $partner->bank_ifsc,
                    'Applied' => $partner->created_at->format('d M Y'),
                    'Approved' => $partner->approved_at?->format('d M Y'),
                ] as $k => $v)
                    @if ($v)
                        <div><dt class="text-xs text-slate-500">{{ $k }}</dt><dd class="text-slate-800">{{ $v }}</dd></div>
                    @endif
                @endforeach
            </dl>
            @if ($partner->pitch)
                <div class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">{{ $partner->pitch }}</div>
            @endif
        </x-card>

        <x-card title="Referred businesses" class="!p-0">
            @if ($partner->referrals->isEmpty())
                <x-empty title="No referrals yet" message="Businesses that sign up with this code will appear here." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-slate-100 text-left text-xs uppercase text-slate-500">
                            <tr><th class="px-5 py-2.5 font-medium">Business</th><th class="px-3 font-medium">Status</th>
                                <th class="px-3 text-right font-medium">Lifetime value</th><th class="px-5 font-medium">Joined</th></tr>
                        </thead>
                        <tbody>
                        @foreach ($partner->referrals as $r)
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="px-5 py-2.5">{{ $r->tenant?->name ?? '—' }}</td>
                                <td class="px-3"><x-badge tone="{{ $r->status === 'converted' ? 'emerald' : 'slate' }}">{{ ucfirst($r->status) }}</x-badge></td>
                                <td class="px-3 text-right tabular-nums">₹{{ number_format($r->lifetime_value_paise / 100, 2) }}</td>
                                <td class="px-5 text-slate-500">{{ $r->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <x-card title="Commission history" class="!p-0">
            @if ($commissions->isEmpty())
                <x-empty title="No commissions yet" />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-slate-100 text-left text-xs uppercase text-slate-500">
                            <tr><th class="px-5 py-2.5 font-medium">Business</th><th class="px-3 font-medium">Month</th>
                                <th class="px-3 text-right font-medium">Base</th><th class="px-3 text-right font-medium">Commission</th>
                                <th class="px-5 font-medium">Status</th></tr>
                        </thead>
                        <tbody>
                        @foreach ($commissions as $c)
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="px-5 py-2.5">{{ $c->referral?->tenant?->name ?? '—' }}</td>
                                <td class="px-3 text-slate-500">#{{ $c->month_number }}</td>
                                <td class="px-3 text-right tabular-nums text-slate-500">₹{{ number_format($c->base_paise / 100, 2) }}</td>
                                <td class="px-3 text-right font-medium tabular-nums">₹{{ number_format($c->amount_paise / 100, 2) }}</td>
                                <td class="px-5">
                                    <x-badge tone="{{ ['paid'=>'emerald','approved'=>'indigo','pending'=>'amber','reversed'=>'rose'][$c->status] }}">{{ ucfirst($c->status) }}</x-badge>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</div>

<form id="payout-form" method="POST" action="{{ route('admin.partners.payout', $partner) }}" class="hidden">@csrf</form>
@endsection
