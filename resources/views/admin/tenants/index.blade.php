@extends('layouts.app')
@section('title', 'Businesses')

@section('content')
<x-page title="Businesses" subtitle="Every account on this installation." />

<form class="mb-4 grid gap-2 sm:flex sm:flex-wrap">
    <x-input name="q" value="{{ request('q') }}" placeholder="{{ __('Search name or GSTIN') }}" class="w-full sm:!w-64" />
    <x-select name="scope" class="w-full sm:!w-48" onchange="this.form.submit()">
        @foreach (['' => __('All businesses'), 'main' => __('Head offices only'), 'branches' => __('Branches / stores only')] as $value => $label)
            <option value="{{ $value }}" @selected(request('scope') === $value)>{{ $label }}</option>
        @endforeach
    </x-select>
    <x-select name="status" class="w-full sm:!w-44" onchange="this.form.submit()">
        <option value="">All statuses</option>
        @foreach (['active','suspended','closed'] as $s)
            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
        @endforeach
    </x-select>
    <x-btn type="submit" class="w-full sm:w-auto">Search</x-btn>
</form>

<x-card class="!p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">Business</th>
                    <th class="px-3 font-medium">Plan</th>
                    <th class="px-3 text-right font-medium">Users</th>
                    <th class="px-3 text-right font-medium">Docs</th>
                    <th class="px-3 font-medium">Status</th>
                    <th class="px-5"></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($tenants as $t)
                <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/60">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            @if ($t->parent_tenant_id)<span class="text-slate-300"></span>@endif
                            <a href="{{ route('admin.tenants.show', $t) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $t->name }}</a>
                            <x-badge tone="{{ $t->parent_tenant_id ? 'slate' : 'indigo' }}">
                                {{ $t->parent_tenant_id ? __('Branch') : __('Head office') }}
                            </x-badge>
                        </div>
                        <div class="font-mono text-xs text-slate-400">{{ $t->gstin ?: 'No GSTIN' }}</div>
                        @if ($t->parent)
                            <div class="text-xs text-slate-400">
                                {{ __('Part of') }}
                                <a href="{{ route('admin.tenants.show', $t->parent) }}" class="text-indigo-600 hover:underline">{{ $t->parent->name }}</a>
                            </div>
                        @endif
                        @if ($t->branches_count)
                            <div class="mt-1 text-xs text-slate-500">
                                {{ __('Branches') }}:
                                @foreach ($t->branches as $branch)
                                    <a href="{{ route('admin.tenants.show', $branch) }}" class="text-indigo-600 hover:underline">{{ $branch->name }}</a>@if (! $loop->last),@endif
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="px-3">
                        {{ $t->subscription?->plan?->name ?? '—' }}
                        <div class="text-xs text-slate-400">{{ $t->subscription?->status }}</div>
                    </td>
                    <td class="px-3 text-right tabular-nums">{{ $t->users_count }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $t->documents_count }}</td>
                    <td class="px-3">
                        <x-badge tone="{{ ['active'=>'emerald','suspended'=>'rose','closed'=>'slate'][$t->account_status] }}">{{ ucfirst($t->account_status) }}</x-badge>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-3">
                            @php $firstUser = $t->users->firstWhere('is_super_admin', false) ?? $t->users->first(); @endphp
                            @if ($firstUser && ! $firstUser->is_super_admin)
                                <form method="POST" action="{{ route('admin.users.impersonate', $firstUser) }}" class="inline"
                                      onsubmit="return confirm('Log in as this user? This is recorded in the audit log.')">
                                    @csrf
                                    <button class="text-xs text-amber-600 hover:underline" title="{{ $firstUser->email }}">{{ __('Login as') }}</button>
                                </form>
                            @endif
                            <span class="text-xs text-slate-400" title="{{ __('Last activity') }}">
                                {{ $t->users->max('last_login_at')?->diffForHumans() ?? __('never') }}
                            </span>
                            <x-btn variant="ghost" size="sm" :href="route('admin.tenants.show', $t)">Manage</x-btn>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-card>

<div class="mt-4">{{ $tenants->links() }}</div>
@endsection
