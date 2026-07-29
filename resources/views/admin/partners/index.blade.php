@extends('layouts.app')
@section('title', 'Partners')

@section('content')
<x-page title="Partner Program" subtitle="Channel partners and affiliates who bring you business." />

<div class="mb-5 grid gap-4 sm:grid-cols-4">
    @foreach ($stats as $label => $value)
        <x-card>
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</div>
            <div class="mt-1 text-2xl font-semibold">{{ $label === 'Owed now' ? '₹'.number_format($value, 2) : number_format($value) }}</div>
        </x-card>
    @endforeach
</div>

<form class="mb-4 flex flex-wrap gap-2">
    <x-input name="q" value="{{ request('q') }}" placeholder="Search name, email or code" class="!w-64" />
    <x-select name="type" class="!w-48" onchange="this.form.submit()">
        <option value="">All types</option>
        @foreach (\App\Models\Partner::TYPES as $v => $l)
            <option value="{{ $v }}" @selected(request('type') === $v)>{{ $l }}</option>
        @endforeach
    </x-select>
    <x-select name="status" class="!w-40" onchange="this.form.submit()">
        <option value="">All statuses</option>
        @foreach (['pending','approved','suspended','rejected'] as $s)
            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
        @endforeach
    </x-select>
    <x-btn type="submit">Search</x-btn>
</form>

<x-card class="!p-0">
    @if ($partners->isEmpty())
        <x-empty title="No partners yet" message="Applications from the partner pages will appear here." />
    @else
        <table class="w-full text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">Partner</th>
                    <th class="px-3 font-medium">Type</th>
                    <th class="px-3 font-medium">Code</th>
                    <th class="px-3 text-right font-medium">Referrals</th>
                    <th class="px-3 text-right font-medium">Owed</th>
                    <th class="px-3 font-medium">Status</th>
                    <th class="px-5"></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($partners as $p)
                <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/60">
                    <td class="px-5 py-3">
                        <a href="{{ route('admin.partners.show', $p) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $p->name }}</a>
                        <div class="text-xs text-slate-400">{{ $p->email }}</div>
                    </td>
                    <td class="px-3">
                        <x-badge tone="{{ $p->type === 'channel' ? 'indigo' : 'amber' }}">{{ $p->typeLabel() }}</x-badge>
                    </td>
                    <td class="px-3 font-mono text-xs">{{ $p->referral_code }}</td>
                    <td class="px-3 text-right tabular-nums">{{ $p->referrals_count }}</td>
                    <td class="px-3 text-right tabular-nums {{ $p->availableBalance() > 0 ? 'font-medium text-emerald-600' : 'text-slate-400' }}">
                        ₹{{ number_format($p->availableBalance() / 100, 2) }}
                    </td>
                    <td class="px-3">
                        <x-badge tone="{{ ['approved'=>'emerald','pending'=>'amber','suspended'=>'rose','rejected'=>'slate'][$p->status] }}">
                            {{ ucfirst($p->status) }}
                        </x-badge>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <x-btn variant="ghost" size="sm" :href="route('admin.partners.show', $p)">Manage</x-btn>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</x-card>

<div class="mt-4">{{ $partners->links() }}</div>
@endsection
