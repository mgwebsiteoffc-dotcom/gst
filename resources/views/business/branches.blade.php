@extends('layouts.app')
@section('title', __('Business & Branches'))

@section('content')
<x-page :title="__('Business & Branches')" :subtitle="__('Run every shop from one login — switch any time from the header.')" />

@if (! $canAddBranch)
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <span>{{ __('Multiple branches are not part of your :plan plan. Upgrade to add retail stores.', ['plan' => $plan?->name ?? __('current')]) }}</span>
        <a href="{{ route('billing.plans') }}" class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">{{ __('Upgrade plan') }}</a>
    </div>
@endif

{{-- Group overview: each business with its own numbers --}}
<x-card :title="__('Group overview')" class="mb-5 !p-0 overflow-x-auto">
    <table class="w-full min-w-[720px] text-sm">
        <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-5 py-3 font-medium">{{ __('Business') }}</th>
                <th class="px-3 font-medium">GSTIN</th>
                <th class="px-3 text-right font-medium">{{ __('Sales (MTD)') }}</th>
                <th class="px-3 text-right font-medium">{{ __('Outstanding') }}</th>
                <th class="px-3 text-right font-medium">{{ __('Documents') }}</th>
                <th class="px-5"></th>
            </tr>
        </thead>
        <tbody>
        @foreach ($businesses as $b)
            <tr class="border-b border-slate-50 last:border-0 {{ (int) $activeId === (int) $b->id ? 'bg-indigo-50/40' : '' }}">
                <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-slate-900">{{ $b->name }}</span>
                        <x-badge tone="{{ $b->parent_tenant_id ? 'slate' : 'indigo' }}">
                            {{ $b->parent_tenant_id ? __('Branch') : __('Head office') }}
                        </x-badge>
                        @if ((int) $activeId === (int) $b->id)
                            <span class="text-[11px] font-medium text-indigo-600">{{ __('Active') }}</span>
                        @endif
                    </div>
                </td>
                <td class="px-3 font-mono text-xs">{{ $b->gstin ?: '—' }}</td>
                <td class="px-3 text-right tabular-nums">₹{{ number_format((float) ($sales[$b->id] ?? 0), 2) }}</td>
                <td class="px-3 text-right tabular-nums {{ ($outstanding[$b->id] ?? 0) > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                    ₹{{ number_format((float) ($outstanding[$b->id] ?? 0), 2) }}
                </td>
                <td class="px-3 text-right tabular-nums">{{ (int) ($counts[$b->id] ?? 0) }}</td>
                <td class="px-5 py-3 text-right">
                    @if ((int) $activeId !== (int) $b->id)
                        <form method="POST" action="{{ route('business.switch') }}">
                            @csrf
                            <input type="hidden" name="tenant_id" value="{{ $b->id }}">
                            <button class="text-xs font-medium text-indigo-600 hover:underline">{{ __('Switch') }}</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</x-card>

<div class="grid gap-5 lg:grid-cols-2">
    {{-- Add a branch --}}
    <x-card :title="__('Add branch')">
        <form method="POST" action="{{ route('business.branches.store') }}" class="grid gap-4">
            @csrf
            <x-field :label="__('Branch name')" name="name" required>
                <x-input name="name" value="{{ old('name') }}" required placeholder="{{ __('Branch name') }}" />
            </x-field>

            <x-field label="GSTIN" name="gstin" :hint="__('Leave blank for a non-GST branch. Each branch keeps its own GSTIN and invoice series.')">
                <x-input name="gstin" value="{{ old('gstin') }}" maxlength="15" class="font-mono uppercase" placeholder="09AAACD1234F1Z5" />
            </x-field>

            <x-field :label="__('State')" name="state_code" :hint="__('Used when the branch has no GSTIN of its own.')">
                <x-select name="state_code">
                    <option value="">{{ __('— Select state —') }}</option>
                    @foreach ($states as $code => $name)
                        <option value="{{ $code }}" @selected(old('state_code') === $code)>{{ $code }} — {{ $name }}</option>
                    @endforeach
                </x-select>
            </x-field>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('City')" name="city">
                    <x-input name="city" value="{{ old('city') }}" />
                </x-field>
                <x-field :label="__('Phone')" name="phone">
                    <x-input name="phone" value="{{ old('phone') }}" />
                </x-field>
            </div>

            <x-btn variant="primary" type="submit">{{ __('Add branch') }}</x-btn>
        </form>
    </x-card>

    {{-- Branches (edit / remove) --}}
    <div class="grid content-start gap-5">
        @foreach ($businesses->whereNotNull('parent_tenant_id') as $b)
            <x-card :title="$b->name">
                <form method="POST" action="{{ route('business.branches.update', $b) }}" class="grid gap-4">
                    @csrf @method('PUT')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field :label="__('Branch name')" name="name" required>
                            <x-input name="name" value="{{ old('name', $b->name) }}" required />
                        </x-field>
                        <x-field label="GSTIN" name="gstin">
                            <x-input name="gstin" value="{{ old('gstin', $b->gstin) }}" maxlength="15" class="font-mono uppercase" />
                        </x-field>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-field :label="__('State')" name="state_code">
                            <x-select name="state_code">
                                <option value="">{{ __('— Select state —') }}</option>
                                @foreach ($states as $code => $name)
                                    <option value="{{ $code }}" @selected(old('state_code', $b->state_code) === $code)>{{ $code }} — {{ $name }}</option>
                                @endforeach
                            </x-select>
                        </x-field>
                        <x-field :label="__('City')" name="city">
                            <x-input name="city" value="{{ old('city', $b->setting('address.city')) }}" />
                        </x-field>
                        <x-field :label="__('Phone')" name="phone">
                            <x-input name="phone" value="{{ old('phone', $b->setting('contact.phone')) }}" />
                        </x-field>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-btn variant="primary" size="sm" type="submit">{{ __('Save') }}</x-btn>
                    </div>
                </form>

                <form method="POST" action="{{ route('business.branches.destroy', $b) }}" class="mt-3 border-t border-slate-100 pt-3"
                      onsubmit="return confirm('{{ __('Remove this branch?') }}')">
                    @csrf @method('DELETE')
                    <button class="text-xs font-medium text-rose-600 hover:underline">{{ __('Remove branch') }}</button>
                </form>
            </x-card>
        @endforeach

        @if ($businesses->whereNotNull('parent_tenant_id')->isEmpty())
            <x-card>
                <x-empty :title="__('No branches yet')"
                         :message="__('Add a branch or retail store and it appears in the header switcher instantly.')" />
            </x-card>
        @endif
    </div>

    {{-- Staff and their business --}}
    <x-card :title="__('Team')" class="lg:col-span-2 !p-0 overflow-x-auto">
        <table class="w-full min-w-[640px] text-sm">
            <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">{{ __('Name') }}</th>
                    <th class="px-3 font-medium">{{ __('Email address') }}</th>
                    <th class="px-3 font-medium">{{ __('Business') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Move to branch') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($team as $member)
                <tr class="border-b border-slate-50 last:border-0">
                    <td class="px-5 py-3 font-medium">{{ $member->name }}</td>
                    <td class="px-3 text-slate-600">{{ $member->email }}</td>
                    <td class="px-3">
                        {{ optional($businesses->firstWhere('id', $member->tenant_id))->name ?? '—' }}
                    </td>
                    <td class="px-5 py-3 text-right">
                        <form method="POST" action="{{ route('business.users.branch', $member) }}" class="inline-flex items-center gap-2">
                            @csrf @method('PUT')
                            <x-select name="tenant_id" class="!w-44 !py-1 text-xs">
                                @foreach ($businesses as $option)
                                    <option value="{{ $option->id }}" @selected((int) $member->tenant_id === (int) $option->id)>{{ $option->name }}</option>
                                @endforeach
                            </x-select>
                            <button class="text-xs font-medium text-indigo-600 hover:underline">{{ __('Move') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </x-card>
</div>
@endsection
