@extends('layouts.app')
@section('title', 'Platform Admin')

@section('content')
<x-page title="Platform Admin" subtitle="Every business and partner on this installation.">
    <x-btn variant="secondary" :href="route('admin.tenants.index')">All businesses</x-btn>
    <x-btn variant="primary" :href="route('admin.partners.index')">Partners</x-btn>
</x-page>

<div class="mb-5 grid gap-4 sm:grid-cols-4">
    @foreach ($stats as $label => $value)
        <x-card>
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</div>
            <div class="mt-1 text-2xl font-semibold">{{ number_format($value) }}</div>
        </x-card>
    @endforeach
</div>

<div class="mb-5 grid gap-4 sm:grid-cols-4">
    @foreach ($partnerStats as $label => $value)
        <x-card>
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</div>
            <div class="mt-1 text-2xl font-semibold">{{ is_numeric($value) ? number_format($value) : $value }}</div>
        </x-card>
    @endforeach
</div>

<x-card title="Businesses" class="!p-0">
    <table class="w-full text-sm">
        <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-5 py-3 font-medium">Name</th>
                <th class="px-3 font-medium">GSTIN</th>
                <th class="px-3 font-medium">Mode</th>
                <th class="px-3 text-right font-medium">Users</th>
                <th class="px-5 font-medium">Trial ends</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($tenants as $t)
            <tr class="border-b border-slate-50 last:border-0">
                <td class="px-5 py-3">
                    <div class="font-medium text-slate-900">{{ $t->name }}</div>
                    <div class="text-xs text-slate-400">{{ $t->slug }}</div>
                </td>
                <td class="px-3 font-mono text-xs">{{ $t->gstin ?: '—' }}</td>
                <td class="px-3"><x-badge tone="{{ $t->tax_mode === 'gst' ? 'slate' : 'amber' }}">{{ $t->tax_mode === 'gst' ? 'GST' : 'Non-GST' }}</x-badge></td>
                <td class="px-3 text-right tabular-nums">{{ $t->users_count }}</td>
                <td class="px-5 text-slate-500">{{ $t->trial_ends_at?->format('d M Y') ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</x-card>
@endsection
