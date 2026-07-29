@extends('layouts.app')
@section('title', 'Clients & Vendors')

@section('content')
<x-page title="Clients & Vendors" subtitle="Everyone you bill or buy from.">
    <x-btn variant="primary" :href="route('parties.create')">+ New contact</x-btn>
</x-page>

<form class="mb-4 flex flex-wrap gap-2">
    <x-input name="q" value="{{ request('q') }}" placeholder="Search name, email, phone or GSTIN" class="!w-72"/>
    <x-select name="type" class="!w-40" onchange="this.form.submit()">
        <option value="">All types</option>
        @foreach (['customer' => 'Clients', 'vendor' => 'Vendors', 'both' => 'Both'] as $v => $t)
            <option value="{{ $v }}" @selected(request('type') === $v)>{{ $t }}</option>
        @endforeach
    </x-select>
    <x-btn type="submit">Search</x-btn>
</form>

<x-card class="!p-0">
    @if ($parties->isEmpty())
        <x-empty title="No contacts yet" message="Add a client to start invoicing them.">
            <x-btn variant="primary" :href="route('parties.create')">+ New contact</x-btn>
        </x-empty>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Name</th>
                        <th class="px-3 font-medium">GSTIN</th>
                        <th class="px-3 font-medium">State</th>
                        <th class="px-3 font-medium">Contact</th>
                        <th class="px-3 text-right font-medium">Docs</th>
                        <th class="px-3 text-right font-medium">Outstanding</th>
                        <th class="px-5"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($parties as $p)
                    <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/60">
                        <td class="px-5 py-3">
                            <a href="{{ route('parties.show', $p) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $p->name }}</a>
                            <div class="text-xs text-slate-400">{{ ucfirst($p->type) }} · {{ str_replace('_', ' ', $p->gst_treatment) }}</div>
                        </td>
                        <td class="px-3 font-mono text-xs">{{ $p->gstin ?: '—' }}</td>
                        <td class="px-3 text-xs">{{ $p->billing_state ?: '—' }}</td>
                        <td class="px-3 text-xs">
                            <div>{{ $p->email ?: '—' }}</div>
                            <div class="text-slate-400">{{ $p->phone }}</div>
                        </td>
                        <td class="px-3 text-right tabular-nums">{{ $p->documents_count }}</td>
                        <td class="px-3 text-right tabular-nums {{ $p->outstanding() > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                            ₹{{ number_format($p->outstanding(), 2) }}
                        </td>
                        <td class="px-5 py-3 text-right">
                            <x-btn variant="ghost" size="sm" :href="route('parties.edit', $p)">Edit</x-btn>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-card>

<div class="mt-4">{{ $parties->links() }}</div>
@endsection
