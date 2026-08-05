@extends('layouts.app')
@section('title', 'HSN / SAC Codes')

@section('content')
<x-page title="HSN / SAC Codes" subtitle="Master list used for search-and-select on every business's item master." />

<form class="mb-4 flex flex-wrap gap-2">
    <x-input name="q" value="{{ request('q') }}" placeholder="Search code or description" class="!w-64" />
    <x-select name="type" class="!w-44" onchange="this.form.submit()">
        <option value="">All types</option>
        @foreach (['goods' => 'Goods (HSN)', 'service' => 'Service (SAC)'] as $v => $t)
            <option value="{{ $v }}" @selected(request('type') === $v)>{{ $t }}</option>
        @endforeach
    </x-select>
    <x-btn type="submit">Search</x-btn>
</form>

<div class="grid gap-5 lg:grid-cols-3">
    <x-card class="!p-0 lg:col-span-2">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">Code</th>
                    <th class="px-3 font-medium">Description</th>
                    <th class="px-3 font-medium">Type</th>
                    <th class="px-3 text-right font-medium">GST %</th>
                    <th class="px-5"></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($hsnCodes as $h)
                <tr class="border-b border-slate-50 last:border-0" x-data="{ editing: false }">
                    <td class="px-5 py-3 align-top" colspan="5" x-show="editing" x-cloak>
                        <form method="POST" action="{{ route('admin.hsn-codes.update', $h) }}" class="grid gap-3 sm:grid-cols-5 sm:items-end">
                            @csrf @method('PUT')
                            <x-field label="Code" name="code" class="sm:col-span-1">
                                <x-input name="code" value="{{ $h->code }}" class="font-mono" maxlength="8" required/>
                            </x-field>
                            <x-field label="Description" name="description" class="sm:col-span-2">
                                <x-input name="description" value="{{ $h->description }}" required/>
                            </x-field>
                            <x-field label="Type" name="type" class="sm:col-span-1">
                                <x-select name="type">
                                    @foreach (['goods' => 'Goods', 'service' => 'Service'] as $v => $t)
                                        <option value="{{ $v }}" @selected($h->type === $v)>{{ $t }}</option>
                                    @endforeach
                                </x-select>
                            </x-field>
                            <x-field label="GST %" name="gst_rate" class="sm:col-span-1">
                                <x-select name="gst_rate">
                                    <option value="">—</option>
                                    @foreach (config('gst.slabs') as $slab)
                                        <option value="{{ $slab }}" @selected($h->gst_rate !== null && (float) $h->gst_rate === (float) $slab)>
                                            {{ rtrim(rtrim(number_format($slab, 2), '0'), '.') }}%
                                        </option>
                                    @endforeach
                                </x-select>
                            </x-field>
                            <div class="flex gap-2 sm:col-span-5">
                                <x-btn type="submit" size="sm" variant="primary">Save</x-btn>
                                <x-btn type="button" size="sm" variant="secondary" @click="editing = false">Cancel</x-btn>
                            </div>
                        </form>
                    </td>

                    <td class="px-5 py-3 font-mono" x-show="!editing">{{ $h->code }}</td>
                    <td class="px-3" x-show="!editing">{{ $h->description }}</td>
                    <td class="px-3" x-show="!editing">
                        <x-badge tone="{{ $h->type === 'goods' ? 'indigo' : 'amber' }}">{{ ucfirst($h->type) }}</x-badge>
                    </td>
                    <td class="px-3 text-right tabular-nums" x-show="!editing">
                        {{ $h->gst_rate !== null ? rtrim(rtrim(number_format($h->gst_rate, 2), '0'), '.').'%' : '—' }}
                    </td>
                    <td class="px-5 py-3 text-right whitespace-nowrap" x-show="!editing">
                        <x-btn variant="ghost" size="sm" type="button" @click="editing = true">Edit</x-btn>
                        <x-btn variant="ghost" size="sm" type="submit" form="del-{{ $h->id }}"
                               onclick="return confirm('Delete this code?')">Delete</x-btn>
                    </td>
                </tr>
                <form id="del-{{ $h->id }}" method="POST" action="{{ route('admin.hsn-codes.destroy', $h) }}" class="hidden">@csrf @method('DELETE')</form>
            @empty
                <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">No HSN/SAC codes yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </x-card>

    <x-card title="New HSN/SAC code" class="h-fit">
        <form method="POST" action="{{ route('admin.hsn-codes.store') }}" class="grid gap-3">
            @csrf
            <x-field label="Code" name="code" required>
                <x-input name="code" maxlength="8" class="font-mono" required placeholder="9983"/>
            </x-field>
            <x-field label="Description" name="description" required>
                <x-input name="description" required placeholder="Information technology services"/>
            </x-field>
            <x-field label="Type" name="type" required>
                <x-select name="type">
                    @foreach (['goods' => 'Goods (HSN)', 'service' => 'Service (SAC)'] as $v => $t)
                        <option value="{{ $v }}">{{ $t }}</option>
                    @endforeach
                </x-select>
            </x-field>
            <x-field label="GST rate" name="gst_rate" hint="Optional — suggested rate, shown when picked on the item form.">
                <x-select name="gst_rate">
                    <option value="">—</option>
                    @foreach (config('gst.slabs') as $slab)
                        <option value="{{ $slab }}">{{ rtrim(rtrim(number_format($slab, 2), '0'), '.') }}%</option>
                    @endforeach
                </x-select>
            </x-field>
            <x-btn variant="primary" type="submit">Add code</x-btn>
        </form>
    </x-card>
</div>

<div class="mt-4">{{ $hsnCodes->links() }}</div>
@endsection
