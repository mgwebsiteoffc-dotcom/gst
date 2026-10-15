@extends('layouts.app')
@section('title', $tenant->name)

@section('content')
<x-page :title="$tenant->name" :subtitle="$tenant->gstin ?: 'Non-GST business'" :back="route('admin.tenants.index')" />

<div class="mb-5 grid gap-4 sm:grid-cols-4">
    @foreach (['Users' => $tenant->users_count, 'Documents' => $tenant->documents_count,
               'Customers' => $tenant->parties_count, 'Items' => $tenant->items_count] as $k => $v)
        <x-card><div class="text-xs uppercase tracking-wide text-slate-500">{{ $k }}</div>
            <div class="mt-1 text-2xl font-semibold">{{ number_format($v) }}</div></x-card>
    @endforeach
</div>

<div class="grid gap-5 lg:grid-cols-2">
    <x-card title="Account status">
        <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}" class="grid gap-4">
            @csrf @method('PUT')
            <x-field label="Status" name="account_status" hint="Suspending locks out the whole team immediately.">
                <x-select name="account_status">
                    @foreach (['active' => 'Active', 'suspended' => 'Suspended', 'closed' => 'Closed'] as $v => $l)
                        <option value="{{ $v }}" @selected($tenant->account_status === $v)>{{ $l }}</option>
                    @endforeach
                </x-select>
            </x-field>
            <x-field label="Reason" name="suspension_reason">
                <x-input name="suspension_reason" value="{{ $tenant->suspension_reason }}" placeholder="Non-payment, abuse…" />
            </x-field>
            <x-btn variant="primary" type="submit">Update status</x-btn>
        </form>
    </x-card>

    <x-card title="Subscription">
        <form method="POST" action="{{ route('admin.tenants.subscription', $tenant) }}" class="grid gap-4">
            @csrf @method('PUT')
            <x-field label="Plan" name="plan_id">
                <x-select name="plan_id">
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}" @selected($subscription?->plan_id === $plan->id)>
                            {{ $plan->name }} — ₹{{ number_format($plan->price_paise / 100) }}/mo
                        </option>
                    @endforeach
                </x-select>
            </x-field>
            <div class="grid grid-cols-3 gap-3">
                <x-field label="Status" name="status">
                    <x-select name="status">
                        @foreach (['trialing','active','past_due','canceled'] as $s)
                            <option value="{{ $s }}" @selected($subscription?->status === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Interval" name="interval">
                    <x-select name="interval">
                        @foreach (['monthly','yearly'] as $i)
                            <option value="{{ $i }}" @selected($subscription?->interval === $i)>{{ ucfirst($i) }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Period (days)" name="period_days" hint="30 = monthly, 365 = yearly"><x-input type="number" name="period_days" placeholder="30" /></x-field>
            </div>
            <x-btn variant="primary" type="submit">Update subscription</x-btn>
        </form>
    </x-card>

    <x-card title="Paid capabilities">
        <p class="mb-3 text-sm text-slate-500">Give this business a feature without changing its plan — handy for demos and promotions.</p>
        <div class="grid gap-3">
            @foreach (['multi_branch' => 'Multiple branches / stores', 'einvoice' => 'e-Invoice (IRN)', 'shopify' => 'Shopify'] as $cap => $label)
                @php $current = $capabilities[$cap] ?? null; @endphp
                <form method="POST" action="{{ route('admin.tenants.capability', $tenant) }}"
                      class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2">
                    @csrf @method('PUT')
                    <input type="hidden" name="capability" value="{{ $cap }}">
                    <div class="text-sm">
                        <span class="font-medium">{{ $label }}</span>
                        <span class="ml-2 text-xs {{ $current === true ? 'text-emerald-600' : ($current === false ? 'text-rose-600' : 'text-slate-400') }}">
                            {{ $current === true ? 'Enabled (override)' : ($current === false ? 'Disabled (override)' : 'Plan default') }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($cap === 'multi_branch')
                            <x-input type="number" name="extra_branches" class="!w-24 !py-1 text-xs"
                                     value="{{ $capabilities['extra_branches'] ?? 0 }}" title="Extra branches beyond the first" />
                        @endif
                        <x-select name="enabled" class="!w-28 !py-1 text-xs">
                            <option value="1">Enable</option>
                            <option value="0">Disable</option>
                        </x-select>
                        <button class="text-xs font-medium text-indigo-600 hover:underline">Apply</button>
                    </div>
                </form>
            @endforeach
        </div>
        <p class="mt-3 text-xs text-slate-500">Branches for this business: <span class="font-medium">{{ $branchCount }}</span> · allowance {{ $tenant->branchAllowance() }}</p>
    </x-card>

    <x-card title="Subscription payments (Razorpay)">
        <p class="mb-3 text-sm text-slate-500">Leave blank to use the platform .env account. Keys stored here are used for this business's checkout.</p>
        <form method="POST" action="{{ route('admin.tenants.subscription', $tenant) }}" class="grid gap-3">
            @csrf @method('PUT')
            <input type="hidden" name="plan_id" value="{{ $subscription?->plan_id ?? $plans->first()->id }}">
            <input type="hidden" name="status" value="{{ $subscription?->status ?? 'trialing' }}">
            <input type="hidden" name="period_days" value="30">
            <x-field label="Key ID" name="razorpay_key">
                <x-input name="razorpay_key" value="{{ $billingSettings['razorpay_key'] ?? '' }}" placeholder="rzp_live_..." />
            </x-field>
            <x-field label="Key secret" name="razorpay_secret">
                <x-input type="password" name="razorpay_secret" value="{{ $billingSettings['razorpay_secret'] ?? '' }}" />
            </x-field>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="razorpay_sandbox" value="1" @checked($billingSettings['razorpay_sandbox'] ?? true) class="rounded border-slate-300 text-indigo-600">
                Test mode (sandbox)
            </label>
            <div><x-btn variant="primary" type="submit">Save payment settings</x-btn></div>
        </form>
    </x-card>

    @if ($tenant->parent_tenant_id)
        <x-card title="{{ __('Branch') }}" class="lg:col-span-2">
            <p class="text-sm text-slate-600">
                {{ __('Part of') }}
                <a href="{{ route('admin.tenants.show', $tenant->parent) }}" class="font-medium text-indigo-600 hover:underline">{{ $tenant->parent?->name }}</a>
                — {{ __('billing runs on the main business plan.') }}
            </p>
        </x-card>
    @else
        <x-card title="{{ __('Branches') }}" class="lg:col-span-2">
            @php $branches = $tenant->branches()->withCount('users', 'documents')->get(); @endphp
            @if ($branches->isEmpty())
                <p class="text-sm text-slate-500">{{ __('No branches yet.') }}</p>
            @else
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($branches as $branch)
                        <li class="flex items-center justify-between gap-3 py-2">
                            <div>
                                <a href="{{ route('admin.tenants.show', $branch) }}" class="font-medium text-indigo-600 hover:underline">{{ $branch->name }}</a>
                                <span class="ml-2 font-mono text-xs text-slate-500">{{ $branch->gstin ?: 'Non-GST' }}</span>
                            </div>
                            <span class="flex items-center gap-3">
                                <span class="text-xs text-slate-500">{{ $branch->users_count }} {{ __('Users') }} · {{ $branch->documents_count }} {{ __('Documents') }}</span>
                                @php $branchUser = $branch->users()->orderBy('id')->first(); @endphp
                                @if ($branchUser)
                                    <form method="POST" action="{{ route('admin.users.impersonate', $branchUser) }}"
                                          onsubmit="return confirm('Log in as this user? This is recorded in the audit log.')">
                                        @csrf
                                        <button class="text-xs font-medium text-amber-600 hover:underline">{{ __('Login as') }}</button>
                                    </form>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    @endif

    <x-card title="{{ __('Subscription payments') }}" class="lg:col-span-2 !p-0 overflow-x-auto">
        @if ($payments->isEmpty())
            <div class="p-5"><p class="text-sm text-slate-500">{{ __('No subscription payments recorded yet.') }}</p></div>
        @else
            <table class="w-full min-w-[680px] text-sm">
                <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">{{ __('Invoice') }}</th>
                        <th class="px-3 font-medium">{{ __('Date') }}</th>
                        <th class="px-3 font-medium">{{ __('Plan') }}</th>
                        <th class="px-3 font-medium">Period</th>
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
                        <td class="px-3 text-xs text-slate-500">{{ $payment->period_start?->format('d M Y') }} → {{ $payment->period_end?->format('d M Y') }}</td>
                        <td class="px-3 text-right tabular-nums">{{ $payment->amountLabel() }}</td>
                        <td class="px-3"><x-badge tone="{{ $payment->status === 'paid' ? 'emerald' : 'amber' }}">{{ ucfirst($payment->status) }}</x-badge></td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.tenants.payment', [$tenant, $payment]) }}" class="text-xs font-medium text-indigo-600 hover:underline">{{ __('Invoice') }}</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </x-card>

    @if ($referral)
        <x-card title="Referred by" class="lg:col-span-2">
            <p class="text-sm text-slate-600">
                <a href="{{ route('admin.partners.show', $referral->partner) }}" class="font-medium text-indigo-600 hover:underline">{{ $referral->partner->name }}</a>
                ({{ $referral->partner->typeLabel() }}, code <span class="font-mono">{{ $referral->partner->referral_code }}</span>)
                · lifetime value ₹{{ number_format($referral->lifetime_value_paise / 100, 2) }}
            </p>
        </x-card>
    @endif

    <x-card title="Team" class="lg:col-span-2 !p-0">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase text-slate-500">
                <tr><th class="px-5 py-2.5 font-medium">Name</th><th class="px-3 font-medium">Email</th>
                    <th class="px-3 font-medium">Last login</th><th class="px-3 font-medium">Status</th><th class="px-5"></th></tr>
            </thead>
            <tbody>
            @foreach ($users as $u)
                <tr class="border-b border-slate-50 last:border-0">
                    <td class="px-5 py-2.5 font-medium">{{ $u->name }}</td>
                    <td class="px-3 text-slate-600">{{ $u->email }}</td>
                    <td class="px-3 text-slate-500">{{ $u->last_login_at?->diffForHumans() ?? 'never' }}</td>
                    <td class="px-3"><x-badge tone="{{ $u->is_active ? 'emerald' : 'rose' }}">{{ $u->is_active ? 'Enabled' : 'Disabled' }}</x-badge></td>
                    <td class="px-5 py-2.5 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.users.show', $u) }}" class="text-xs text-indigo-600 hover:underline">View</a>
                            <form method="POST" action="{{ route('admin.users.impersonate', $u) }}" class="inline"
                                  onsubmit="return confirm('Log in as this user? This is recorded in the audit log.')">
                                @csrf
                                <button class="text-xs text-amber-600 hover:underline">Login as</button>
                            </form>
                            <form method="POST" action="{{ route('admin.users.toggle', $u) }}" class="inline">
                                @csrf @method('PUT')
                                <button class="text-xs {{ $u->is_active ? 'text-rose-600' : 'text-emerald-600' }} hover:underline">
                                    {{ $u->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </x-card>
</div>
@endsection
