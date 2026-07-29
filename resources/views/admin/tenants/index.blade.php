@extends('layouts.app')
@section('title', 'Businesses')

@section('content')
<x-page title="Businesses" subtitle="Every account on this installation." />

<form class="mb-4 flex flex-wrap gap-2">
    <x-input name="q" value="{{ request('q') }}" placeholder="Search name or GSTIN" class="!w-64" />
    <x-select name="status" class="!w-44" onchange="this.form.submit()">
        <option value="">All statuses</option>
        @foreach (['active','suspended','closed'] as $s)
            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
        @endforeach
    </x-select>
    <x-btn type="submit">Search</x-btn>
</form>

<x-card class="!p-0">
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
                    <a href="{{ route('admin.tenants.show', $t) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $t->name }}</a>
                    <div class="font-mono text-xs text-slate-400">{{ $t->gstin ?: 'No GSTIN' }}</div>
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
                <td class="px-5 py-3 text-right"><x-btn variant="ghost" size="sm" :href="route('admin.tenants.show', $t)">Manage</x-btn></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</x-card>

<div class="mt-4">{{ $tenants->links() }}</div>
@endsection
