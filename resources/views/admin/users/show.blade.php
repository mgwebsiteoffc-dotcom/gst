@extends('layouts.app')
@section('title', $user->name)

@section('content')
<x-page :title="$user->name" :subtitle="$user->email"
    :back="$user->tenant_id ? route('admin.tenants.show', $user->tenant_id) : route('admin.dashboard')" />

@if ($user->is_super_admin)
    <x-card class="mb-5 border-amber-200 bg-amber-50">
        <p class="text-sm text-amber-800">This is a platform staff account. Support actions (login-as, reset link, disable, sign-out) are disabled for staff.</p>
    </x-card>
@endif

<div class="grid gap-5 lg:grid-cols-2">
    <x-card title="Profile details">
        <dl class="divide-y divide-slate-100 text-sm">
            <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Name</dt><dd class="font-medium">{{ $user->name }}</dd></div>
            <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Email</dt><dd class="font-medium">{{ $user->email }}</dd></div>
            <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Email verified</dt><dd>{{ $user->email_verified_at ? $user->email_verified_at->format('d M Y') : 'Not verified' }}</dd></div>
            <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Phone</dt><dd>{{ $user->phone ?: '—' }}</dd></div>
            <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Role</dt><dd>{{ $roles->map(fn ($r) => str($r)->headline())->join(', ') ?: '—' }}</dd></div>
            <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Login status</dt>
                <dd><x-badge tone="{{ $user->is_active ? 'emerald' : 'rose' }}">{{ $user->is_active ? 'Enabled' : 'Disabled' }}</x-badge></dd></div>
            <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Last login</dt>
                <dd>{{ $user->last_login_at?->format('d M Y, H:i') ?? __('never') }} <span class="text-slate-400">({{ $user->last_login_at?->diffForHumans() }})</span></dd></div>
            <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Last login IP</dt>
                <dd class="font-mono text-xs">{{ $user->last_login_ip ?: '—' }}</dd></div>
            <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Device</dt>
                <dd class="max-w-[22rem] truncate text-xs" title="{{ $user->last_login_user_agent }}">{{ $user->last_login_user_agent ?: '—' }}</dd></div>
            <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Member since</dt><dd>{{ $user->created_at->format('d M Y') }}</dd></div>
            <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Active sessions</dt><dd>{{ $activeSessions }}</dd></div>
        </dl>
    </x-card>

    <div class="grid content-start gap-5">
        @if ($user->tenant)
            <x-card title="Business">
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Name</dt>
                        <dd><a href="{{ route('admin.tenants.show', $user->tenant) }}" class="font-medium text-indigo-600 hover:underline">{{ $user->tenant->name }}</a></dd></div>
                    <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">GSTIN</dt><dd class="font-mono">{{ $user->tenant->gstin ?: 'Non-GST' }}</dd></div>
                    <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Account</dt>
                        <dd><x-badge tone="{{ $user->tenant->account_status === 'active' ? 'emerald' : 'rose' }}">{{ ucfirst($user->tenant->account_status) }}</x-badge></dd></div>
                    <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Plan</dt>
                        <dd>{{ $user->tenant->subscription?->plan?->name ?? '—' }} ({{ $user->tenant->subscription?->status ?? 'no subscription' }})</dd></div>
                    <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Trial ends</dt>
                        <dd>{{ $user->tenant->trial_ends_at?->format('d M Y') ?? '—' }}</dd></div>
                </dl>
            </x-card>
        @endif

        @if ($partner)
            <x-card title="Partner profile">
                <p class="text-sm text-slate-600">
                    <a href="{{ route('admin.partners.show', $partner) }}" class="font-medium text-indigo-600 hover:underline">{{ $partner->name }}</a>
                    ({{ $partner->typeLabel() }}, code <span class="font-mono">{{ $partner->referral_code }}</span>)
                </p>
            </x-card>
        @endif

        @unless ($user->is_super_admin)
            <x-card title="Support actions">
                <div class="grid gap-3">
                    <form method="POST" action="{{ route('admin.users.impersonate', $user) }}"
                          onsubmit="return confirm('Log in as this user? This is recorded in the audit log.')">
                        @csrf
                        <x-btn variant="primary" type="submit" class="w-full">Login as this user</x-btn>
                    </form>
                    <form method="POST" action="{{ route('admin.users.reset-link', $user) }}">
                        @csrf
                        <x-btn type="submit" class="w-full">Send password-reset link</x-btn>
                    </form>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                            @csrf @method('PUT')
                            <x-btn type="submit" class="w-full">{{ $user->is_active ? 'Disable login' : 'Enable login' }}</x-btn>
                        </form>
                        <form method="POST" action="{{ route('admin.users.sessions', $user) }}"
                              onsubmit="return confirm('Sign this user out from all devices?')">
                            @csrf @method('DELETE')
                            <x-btn variant="danger" type="submit" class="w-full">Sign out everywhere</x-btn>
                        </form>
                    </div>
                    <p class="text-xs text-slate-500">
                        {{ __('Login-as, reset links and sign-outs are written to the application log with your admin id. Roles and branches are managed on the business page.') }}
                    </p>
                </div>
            </x-card>
        @endunless
    </div>
</div>
@endsection
