@extends('layouts.app')
@section('title', __('Subscription Plans'))

@section('content')
@php
    $currentPlanId = $subscription?->plan_id;
    $lapsed = ! $subscription?->isActive() && ! ($tenant->trial_ends_at && $tenant->trial_ends_at->isFuture());
    $branchCount = $tenant->branches()->count();
@endphp

<x-page :title="__('Subscription Plans')" />

@if (session('error'))
    <x-card class="mb-5 border-rose-200 bg-rose-50">
        <p class="text-sm text-rose-800">{{ session('error') }}</p>
    </x-card>
@endif

{{-- Where the subscription stands right now --}}
<x-card class="mb-5">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Current plan') }}</p>
            <p class="mt-1 text-lg font-semibold">
                {{ $subscription?->plan?->name ?? __('Trial') }}
                @if ($subscription)
                    <span class="text-sm font-normal text-slate-500">· {{ ucfirst(str_replace('_', ' ', $subscription->status)) }}</span>
                @endif
            </p>
            <p class="mt-0.5 text-sm text-slate-500">
                @if ($tenant->trial_ends_at?->isFuture())
                    {{ __('Trial ends') }} {{ $tenant->trial_ends_at->format('d M Y') }}
                @elseif ($subscription?->current_period_end)
                    {{ $subscription->status === 'canceled' ? __('Access until') : __('Renews on') }}
                    {{ $subscription->current_period_end->format('d M Y') }}
                @endif
                · {{ $branchCount }} {{ $branchCount === 1 ? __('Branch') : __('Branches') }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('billing.plans', ['interval' => $interval === 'yearly' ? 'monthly' : 'yearly']) }}"
               class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50">
                {{ $interval === 'yearly' ? __('Switch to monthly') : __('Switch to yearly') }}
                @if ($interval !== 'yearly')<span class="text-emerald-600">· {{ __('save ~2 months') }}</span>@endif
            </a>

            @if ($subscription && $subscription->isActive())
                <form method="POST" action="{{ route('billing.cancel') }}"
                      onsubmit="return confirm('{{ __('Cancel the subscription at the end of the paid period?') }}')">
                    @csrf
                    <button class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm text-rose-600 hover:bg-rose-50">
                        {{ __('Cancel subscription') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-card>

@if ($lapsed)
    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        {{ __('Your subscription has expired. Pick a plan below to unlock billing, reports and e-invoicing again.') }}
    </div>
@endif

{{-- Plan chooser --}}
<div class="grid gap-5 lg:grid-cols-3">
    @foreach ($plans as $plan)
        @php
            $isCurrent = (int) $currentPlanId === (int) $plan->id;
            $isUpgrade = $currentPlanId && $plan->price_paise > ($subscription?->plan?->price_paise ?? 0);
            $price = $interval === 'yearly'
                ? (int) ($plan->yearly_price_paise ?: $plan->price_paise * 12)
                : (int) $plan->price_paise;
            $blocked = $branchCount > 0 && ! $plan->includes('multi_branch');
        @endphp

        <x-card class="{{ $isCurrent ? 'ring-2 ring-indigo-500' : '' }}">
            <div class="flex items-start justify-between gap-2">
                <h3 class="text-lg font-semibold text-slate-900">{{ $plan->name }}</h3>
                @if ($isCurrent)
                    <x-badge tone="indigo">{{ __('Current') }}</x-badge>
                @endif
            </div>

            <p class="mt-2">
                <span class="text-2xl font-semibold">₹{{ number_format($price / 100) }}</span>
                <span class="text-sm text-slate-500">/ {{ $interval === 'yearly' ? __('year') : __('month') }}</span>
            </p>

            <ul class="mt-3 space-y-1.5 text-sm text-slate-600">
                <li class="flex gap-2"><span class="text-emerald-600">✓</span> {{ $plan->invoice_limit ? __(':count invoices / month', ['count' => $plan->invoice_limit]) : __('Unlimited invoices') }}</li>
                <li class="flex gap-2"><span class="text-emerald-600">✓</span> {{ __(':count team users', ['count' => $plan->user_limit]) }}</li>
                <li class="flex gap-2 {{ $plan->includes('einvoice') ? '' : 'text-slate-400' }}">
                    <span class="{{ $plan->includes('einvoice') ? 'text-emerald-600' : 'text-slate-300' }}">{{ $plan->includes('einvoice') ? '✓' : '—' }}</span> {{ __('e-Invoice (IRN) & e-Way Bill') }}</li>
                <li class="flex gap-2 {{ $plan->includes('shopify') ? '' : 'text-slate-400' }}">
                    <span class="{{ $plan->includes('shopify') ? 'text-emerald-600' : 'text-slate-300' }}">{{ $plan->includes('shopify') ? '✓' : '—' }}</span> Shopify</li>
                <li class="flex gap-2 {{ $plan->includes('multi_branch') ? '' : 'text-slate-400' }}">
                    <span class="{{ $plan->includes('multi_branch') ? 'text-emerald-600' : 'text-slate-300' }}">{{ $plan->includes('multi_branch') ? '✓' : '—' }}</span> {{ __('Multiple branches / retail stores') }}</li>

                @foreach ((array) ($plan->features ?? []) as $feature)
                    <li class="flex gap-2"><span class="text-emerald-600">✓</span> {{ $feature }}</li>
                @endforeach
            </ul>

            <div class="mt-4">
                @if ($isCurrent && $subscription?->isActive())
                    <span class="block rounded-lg border border-slate-200 px-4 py-2 text-center text-sm text-slate-500">{{ __('Active') }}</span>
                @elseif ($blocked)
                    <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                        {{ __('You have :count branch(es). This plan does not include multi-branch.', ['count' => $branchCount]) }}
                    </p>
                @elseif ($currentPlanId && $isUpgrade)
                    <form method="POST" action="{{ route('billing.checkout') }}">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <input type="hidden" name="interval" value="{{ $interval }}">
                        <x-btn variant="primary" type="submit" class="w-full">{{ __('Upgrade') }}</x-btn>
                    </form>
                @elseif ($currentPlanId)
                    <form method="POST" action="{{ route('billing.downgrade') }}">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <input type="hidden" name="interval" value="{{ $interval }}">
                        <x-btn variant="secondary" type="submit" class="w-full">{{ __('Switch to this plan') }}</x-btn>
                    </form>
                @else
                    <form method="POST" action="{{ route('billing.checkout') }}">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <input type="hidden" name="interval" value="{{ $interval }}">
                        <x-btn variant="primary" type="submit" class="w-full">{{ __('Choose plan') }}</x-btn>
                    </form>
                @endif
            </div>
        </x-card>
    @endforeach
</div>

{{-- What the business has paid, with downloadable GST invoices --}}
<x-card :title="__('Payments & invoices')" class="mt-6 !p-0 overflow-x-auto"
        :subtitle="__('Every subscription payment with its GST invoice.')">
    @if ($payments->isEmpty())
        <div class="p-5">
            <x-empty :title="__('No payments yet')"
                     :message="__('Your subscription bills will appear here with a GST invoice you can download.')" />
        </div>
    @else
        <table class="w-full min-w-[720px] text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">{{ __('Invoice') }}</th>
                    <th class="px-3 font-medium">{{ __('Date') }}</th>
                    <th class="px-3 font-medium">{{ __('Plan') }}</th>
                    <th class="px-3 font-medium">{{ __('Period') }}</th>
                    <th class="px-3 text-right font-medium">{{ __('Total') }}</th>
                    <th class="px-3 font-medium">{{ __('Status') }}</th>
                    <th class="px-5"></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($payments as $payment)
                <tr class="border-b border-slate-50 last:border-0">
                    <td class="px-5 py-3 font-mono text-xs">{{ $payment->invoice_number }}</td>
                    <td class="px-3 text-slate-600">{{ ($payment->paid_at ?? $payment->created_at)->format('d M Y') }}</td>
                    <td class="px-3">{{ $payment->plan_name }} <span class="text-xs text-slate-400">· {{ $payment->interval }}</span></td>
                    <td class="px-3 text-xs text-slate-500">
                        {{ $payment->period_start?->format('d M Y') }} → {{ $payment->period_end?->format('d M Y') }}
                    </td>
                    <td class="px-3 text-right tabular-nums">{{ $payment->amountLabel() }}</td>
                    <td class="px-3">
                        <x-badge tone="{{ $payment->status === 'paid' ? 'emerald' : 'amber' }}">{{ ucfirst($payment->status) }}</x-badge>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('billing.invoices.pdf', $payment) }}" class="text-xs font-medium text-indigo-600 hover:underline">{{ __('Invoice') }}</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</x-card>

<p class="mt-5 text-xs text-slate-500">
    {{ __('GST at 18% applies on subscriptions and is added at checkout. Renewals are not automatic — you will be reminded before the period ends.') }}
    @if (! $gatewayLive)
        <span class="text-amber-700">{{ __('Online payments are not configured yet; continue to see the payment step.') }}</span>
    @endif
</p>
@endsection
