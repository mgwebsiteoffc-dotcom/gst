@extends('layouts.app')
@section('title', __('Checkout'))

@section('content')
@php
    $gst = (int) round($amountPaise * 0.18);
    $total = $amountPaise + $gst;
@endphp

<x-page :title="__('Checkout')" :back="route('billing.plans', ['interval' => $interval])" />

<div class="grid gap-5 lg:grid-cols-2">
    <x-card :title="__('Order summary')">
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">{{ __('Plan') }}</dt><dd class="font-medium">{{ $plan->name }} ({{ $interval === 'yearly' ? __('yearly') : __('monthly') }})</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">{{ __('Business') }}</dt><dd>{{ $tenant->name }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">{{ __('Subtotal') }}</dt><dd>₹{{ number_format($amountPaise / 100, 2) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">GST 18%</dt><dd>₹{{ number_format($gst / 100, 2) }}</dd></div>
            <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold">
                <dt>{{ __('Total') }}</dt><dd>₹{{ number_format($total / 100, 2) }}</dd>
            </div>
        </dl>

        <p class="mt-3 text-xs text-slate-500">
            {{ __('Period') }}: {{ now()->format('d M Y') }} → {{ now()->addMonths($interval === 'yearly' ? 12 : 1)->format('d M Y') }}
            @if ($gstin) · GSTIN {{ $gstin }}@endif
        </p>

        <div class="mt-4">
            @if ($live && ! empty($order['id']))
                <button id="rzp-pay"
                        class="w-full rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
                    {{ __('Pay ₹:amount', ['amount' => number_format($total / 100, 2)]) }}
                </button>
                <p class="mt-2 text-center text-xs text-slate-500">{{ __('Secure payment via Razorpay. UPI, cards, netbanking and wallets.') }}</p>
            @else
                {{-- No gateway keys yet: still activate the plan so the business is unblocked. --}}
                <form method="POST" action="{{ route('billing.payment') }}">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                    <input type="hidden" name="interval" value="{{ $interval }}">
                    <input type="hidden" name="simulated" value="1">
                    <x-btn variant="primary" type="submit" class="w-full">
                        {{ __('Activate :plan', ['plan' => $plan->name]) }}
                    </x-btn>
                </form>
                <p class="mt-2 text-xs text-amber-700">
                    {{ __('Online payments are not configured yet. Continue to activate the plan now, or add Razorpay keys in Platform Admin → Businesses → Billing.') }}
                </p>
            @endif

            <form method="POST" action="{{ route('billing.cancel') }}" class="mt-3 text-center">
                @csrf
                <a href="{{ route('billing.plans') }}" class="text-xs text-slate-500 hover:underline">{{ __('Back to plans') }}</a>
            </form>
        </div>
    </x-card>

    <x-card :title="__('What happens next')">
        <ul class="space-y-2 text-sm text-slate-600">
            <li class="flex gap-2"><span class="text-emerald-600">✓</span> {{ __('Billing, reports and e-invoicing unlock immediately.') }}</li>
            @if ($plan->includes('multi_branch'))
                <li class="flex gap-2"><span class="text-emerald-600">✓</span> {{ __('Create branches / retail stores from Setup → Business & Branches.') }}</li>
            @else
                <li class="flex gap-2"><span class="text-slate-300">—</span> {{ __('Multi-branch is not included in this plan.') }}</li>
            @endif
            <li class="flex gap-2"><span class="text-emerald-600">✓</span> {{ __('You will get a reminder before the period ends — renew with one tap.') }}</li>
        </ul>
    </x-card>
</div>

@if ($live && ! empty($order['id']))
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        document.getElementById('rzp-pay').addEventListener('click', function () {
            var rzp = new Razorpay({
                key: @js($keyId),
                order_id: @js($order['id']),
                amount: @js($total),
                currency: 'INR',
                name: @js(config('app.name')),
                description: @js($plan->name.' · '.$interval),
                prefill: { email: @js(auth()->user()->email), contact: @js(auth()->user()->phone) },
                theme: { color: '#4f46e5' },
                handler: function (response) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = @js(route('billing.payment'));
                    var fields = {
                        _token: @js(csrf_token()),
                        plan_id: @js((string) $plan->id),
                        interval: @js($interval),
                        razorpay_payment_id: response.razorpay_payment_id || '',
                        razorpay_order_id: response.razorpay_order_id || '',
                        razorpay_signature: response.razorpay_signature || '',
                    };
                    Object.keys(fields).forEach(function (k) {
                        var i = document.createElement('input');
                        i.type = 'hidden'; i.name = k; i.value = fields[k];
                        form.appendChild(i);
                    });
                    document.body.appendChild(form);
                    form.submit();
                },
                modal: {
                    ondismiss: function () {
                        window.location.href = @js(route('billing.plans'));
                    }
                }
            });
            rzp.open();
        });
    </script>
@endif
@endsection
