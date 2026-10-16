{{-- Business / branch list for the mobile drawer (the header dropdown needs more
     room than a phone has). Same source of truth as partials/branch-switcher. --}}
@php
    $__user = auth()->user();
    $__active = \App\Support\TenantContext::get();
    $__options = collect();

    if ($__active && $__user && ! $__user->is_super_admin) {
        $__own = $__user->tenant_id ? \App\Models\Tenant::find($__user->tenant_id) : null;
        $__isRoot = (bool) ($__own && ! $__own->parent_tenant_id);

        $__options = $__isRoot
            ? collect([$__own])->merge($__own->branches()->get())->unique('id')->values()
            : collect([$__active]);
    }
@endphp

@if ($__options->count() > 1)
    <p class="px-3 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ __('Business & Branches') }}</p>
    <div class="space-y-1">
        @foreach ($__options as $option)
            @php $isCurrent = (int) $__active->id === (int) $option->id; @endphp
            <form method="POST" action="{{ route('business.switch') }}">
                @csrf
                <input type="hidden" name="tenant_id" value="{{ $option->id }}">
                <button type="submit" @disabled($isCurrent) @click="nav = false"
                        class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-sm {{ $isCurrent ? 'bg-brand-50 font-semibold text-brand-700' : 'text-slate-600 hover:bg-slate-100' }}">
                    <span class="min-w-0">
                        <span class="block truncate">{{ $option->name }}</span>
                        <span class="block text-[11px] font-normal text-slate-400">
                            {{ $option->parent_tenant_id ? __('Branch') : __('Head office') }}
                        </span>
                    </span>
                    @if ($isCurrent)
                        <x-icon name="check-circle" class="h-4 w-4 shrink-0" />
                    @endif
                </button>
            </form>
        @endforeach
    </div>

    @if ($__isRoot && $__user->hasAnyRole(['owner', 'admin']))
        <a href="{{ route('business.branches') }}" @click="nav = false"
           class="mt-1 block rounded-lg px-3 py-2 text-sm font-medium text-brand-600 hover:bg-slate-100">
            {{ __('Manage branches') }}
        </a>
    @endif
@endif
