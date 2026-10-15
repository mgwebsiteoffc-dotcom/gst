@extends('layouts.app')
@section('title', 'Subscription Plans')

@section('content')
<x-page title="Subscription Plans" subtitle="What you charge businesses. Changes apply to the public pricing page immediately." />

<div class="grid gap-5 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        @foreach ($plans as $plan)
            <x-card>
                <form method="POST" action="{{ route('admin.plans.update', $plan) }}">
                    @csrf @method('PUT')
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <span class="font-medium text-slate-900">{{ $plan->name }}</span>
                        <span class="text-sm text-slate-500">{{ $plan->subscriptions_count }} subscriber(s)</span>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-4">
                        <x-field label="Name" name="name"><x-input name="name" value="{{ $plan->name }}" /></x-field>
                        <x-field label="Monthly (₹)" name="price_rupees"><x-input type="number" name="price_rupees" value="{{ $plan->price_paise / 100 }}" /></x-field>
                        <x-field label="Yearly (₹)" name="yearly_price_rupees"><x-input type="number" name="yearly_price_rupees" value="{{ $plan->yearly_price_paise ? $plan->yearly_price_paise / 100 : '' }}" /></x-field>
                        <x-field label="Users" name="user_limit"><x-input type="number" name="user_limit" value="{{ $plan->user_limit }}" /></x-field>
                        <x-field label="Invoice limit" name="invoice_limit" hint="Blank = unlimited">
                            <x-input type="number" name="invoice_limit" value="{{ $plan->invoice_limit }}" /></x-field>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-4">
                        @foreach (['einvoice' => 'e-Invoice', 'shopify' => 'Shopify', 'multi_branch' => 'Multi-branch', 'active' => 'Active'] as $flag => $label)
                            <label class="flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="{{ $flag }}" value="1" @checked($plan->$flag) class="rounded border-slate-300 text-indigo-600"> {{ $label }}
                            </label>
                        @endforeach
                        <x-btn type="submit" size="sm" variant="primary">Save</x-btn>
                        <x-btn type="submit" size="sm" variant="danger" form="del-{{ $plan->id }}"
                               onclick="return confirm('Delete this plan?')">Delete</x-btn>
                    </div>
                </form>
            </x-card>
            <form id="del-{{ $plan->id }}" method="POST" action="{{ route('admin.plans.destroy', $plan) }}" class="hidden">@csrf @method('DELETE')</form>
        @endforeach
    </div>

    <x-card title="New plan" class="h-fit">
        <form method="POST" action="{{ route('admin.plans.store') }}" class="grid gap-3">
            @csrf
            <x-field label="Name" name="name" required><x-input name="name" required placeholder="Professional" /></x-field>
            <div class="grid grid-cols-2 gap-3">
                <x-field label="Monthly (₹)" name="price_rupees" required><x-input type="number" name="price_rupees" value="999" required /></x-field>
                <x-field label="Yearly (₹)" name="yearly_price_rupees"><x-input type="number" name="yearly_price_rupees" value="9990" /></x-field>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <x-field label="Users" name="user_limit" required><x-input type="number" name="user_limit" value="5" required /></x-field>
                <x-field label="Invoice limit" name="invoice_limit"><x-input type="number" name="invoice_limit" placeholder="Unlimited" /></x-field>
            </div>
            <div class="flex flex-wrap gap-4">
                @foreach (['einvoice' => 'e-Invoice', 'shopify' => 'Shopify', 'multi_branch' => 'Multi-branch', 'active' => 'Active'] as $flag => $label)
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="{{ $flag }}" value="1" @checked($flag === 'active') class="rounded border-slate-300 text-indigo-600"> {{ $label }}
                    </label>
                @endforeach
            </div>
            <x-btn variant="primary" type="submit">Create plan</x-btn>
        </form>
    </x-card>
</div>
@endsection
