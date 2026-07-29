@extends('layouts.app')
@section('title', 'Commission Plans')

@section('content')
<x-page title="Commission Plans" subtitle="What partners earn. Assign a plan on each partner's page." />

<div class="grid gap-5 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        @forelse ($plans as $plan)
            <x-card>
                <form method="POST" action="{{ route('admin.commission-plans.update', $plan) }}">
                    @csrf @method('PUT')
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <span class="font-medium text-slate-900">{{ $plan->name }}</span>
                            <x-badge tone="{{ $plan->partner_type === 'channel' ? 'indigo' : 'amber' }}" class="ml-2">
                                {{ \App\Models\Partner::TYPES[$plan->partner_type] }}
                            </x-badge>
                            <span class="ml-2 text-xs text-slate-500">{{ $plan->partners_count }} partner(s)</span>
                        </div>
                        <span class="text-sm font-medium text-emerald-600">{{ $plan->describe() }}</span>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-4">
                        <x-field label="Name" name="name"><x-input name="name" value="{{ $plan->name }}" /></x-field>
                        <x-field label="Model" name="model">
                            <x-select name="model">
                                @foreach (['percentage' => '% of payment', 'flat' => 'Flat per signup', 'hybrid' => 'Both'] as $v => $l)
                                    <option value="{{ $v }}" @selected($plan->model === $v)>{{ $l }}</option>
                                @endforeach
                            </x-select>
                        </x-field>
                        <x-field label="Percentage" name="percentage"><x-input type="number" step="0.01" name="percentage" value="{{ $plan->percentage }}" /></x-field>
                        <x-field label="Flat (₹)" name="flat_rupees"><x-input type="number" step="1" name="flat_rupees" value="{{ $plan->flat_paise / 100 }}" /></x-field>
                        <x-field label="Recurring months" name="recurring_months" hint="0 = first payment only">
                            <x-input type="number" name="recurring_months" value="{{ $plan->recurring_months }}" /></x-field>
                        <x-field label="Cookie days" name="cookie_days"><x-input type="number" name="cookie_days" value="{{ $plan->cookie_days }}" /></x-field>
                        <x-field label="Min payout (₹)" name="min_payout_rupees"><x-input type="number" name="min_payout_rupees" value="{{ $plan->min_payout_paise / 100 }}" /></x-field>
                        <x-field label="Hold days" name="hold_days" hint="Clawback window"><x-input type="number" name="hold_days" value="{{ $plan->hold_days }}" /></x-field>
                    </div>

                    <input type="hidden" name="partner_type" value="{{ $plan->partner_type }}">

                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="active" value="1" @checked($plan->active) class="rounded border-slate-300 text-indigo-600"> Active
                        </label>
                        <x-btn type="submit" size="sm" variant="primary">Save</x-btn>
                        <x-btn type="submit" size="sm" variant="danger" form="del-plan-{{ $plan->id }}"
                               onclick="return confirm('Delete this commission plan?')">Delete</x-btn>
                    </div>
                </form>
            </x-card>
            <form id="del-plan-{{ $plan->id }}" method="POST" action="{{ route('admin.commission-plans.destroy', $plan) }}" class="hidden">
                @csrf @method('DELETE')
            </form>
        @empty
            <x-card><x-empty title="No commission plans" message="Create one on the right before approving partners." /></x-card>
        @endforelse
    </div>

    <x-card title="New commission plan" class="h-fit">
        <form method="POST" action="{{ route('admin.commission-plans.store') }}" class="grid gap-3">
            @csrf
            <x-field label="Name" name="name" required><x-input name="name" required placeholder="Affiliate Standard" /></x-field>
            <x-field label="For" name="partner_type" required>
                <x-select name="partner_type">
                    @foreach (\App\Models\Partner::TYPES as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                </x-select>
            </x-field>
            <x-field label="Model" name="model" required>
                <x-select name="model">
                    <option value="percentage">% of every payment</option>
                    <option value="flat">Flat amount per signup</option>
                    <option value="hybrid">Percentage + flat bonus</option>
                </x-select>
            </x-field>
            <div class="grid grid-cols-2 gap-3">
                <x-field label="Percentage" name="percentage"><x-input type="number" step="0.01" name="percentage" value="20" /></x-field>
                <x-field label="Flat (₹)" name="flat_rupees"><x-input type="number" name="flat_rupees" value="0" /></x-field>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <x-field label="Recurring months" name="recurring_months"><x-input type="number" name="recurring_months" value="12" /></x-field>
                <x-field label="Cookie days" name="cookie_days"><x-input type="number" name="cookie_days" value="60" /></x-field>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <x-field label="Min payout (₹)" name="min_payout_rupees"><x-input type="number" name="min_payout_rupees" value="1000" /></x-field>
                <x-field label="Hold days" name="hold_days"><x-input type="number" name="hold_days" value="30" /></x-field>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="active" value="1" checked class="rounded border-slate-300 text-indigo-600"> Active
            </label>
            <x-btn variant="primary" type="submit">Create plan</x-btn>
        </form>
    </x-card>
</div>
@endsection
