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
            <div class="grid grid-cols-2 gap-3">
                <x-field label="Status" name="status">
                    <x-select name="status">
                        @foreach (['trialing','active','past_due','canceled'] as $s)
                            <option value="{{ $s }}" @selected($subscription?->status === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Extend by (days)" name="trial_days"><x-input type="number" name="trial_days" placeholder="30" /></x-field>
            </div>
            <x-btn variant="primary" type="submit">Update subscription</x-btn>
        </form>
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
