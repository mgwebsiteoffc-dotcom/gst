{{-- Business/branch switcher: only shown when the login can access more than one. --}}
@php
    $__user = auth()->user();
    $__active = \App\Support\TenantContext::get();
    $__options = collect();

    if ($__active && $__user && ! $__user->is_super_admin && $__user->tenant) {
        $__home = $__user->tenant;
        $__options = $__home->parent_tenant_id
            ? collect([$__home])
            : collect([$__home])->merge($__home->branches()->get());
    }

    $__isRoot = $__active && ! $__active->parent_tenant_id;
@endphp

@if ($__options->count() > 1)
    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open" @click.outside="open = false" type="button"
                class="flex max-w-[11rem] items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                title="{{ __('Switch business') }}">
            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5"/></svg>
            <span class="truncate">{{ $__active->name }}</span>
            <svg class="h-3 w-3 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="m19 9-7 7-7-7"/></svg>
        </button>

        <div x-show="open" x-cloak x-transition.origin.top.right
             class="absolute right-0 z-30 mt-2 w-64 rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
            <p class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ __('Business & Branches') }}</p>

            @foreach ($__options as $option)
                <form method="POST" action="{{ route('business.switch') }}">
                    @csrf
                    <input type="hidden" name="tenant_id" value="{{ $option->id }}">
                    <button type="submit"
                            class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm hover:bg-slate-50 {{ (int) $__active->id === (int) $option->id ? 'font-semibold text-indigo-600' : 'text-slate-600' }}">
                        <span class="min-w-0">
                            <span class="block truncate">{{ $option->name }}</span>
                            <span class="block text-[11px] font-normal text-slate-400">
                                {{ $option->parent_tenant_id ? __('Branch') : __('Head office') }}
                                @if ($option->gstin) · <span class="font-mono">{{ $option->gstin }}</span>@endif
                            </span>
                        </span>
                        @if ((int) $__active->id === (int) $option->id)<span>✓</span>@endif
                    </button>
                </form>
            @endforeach

            @if ($__isRoot && auth()->user()->hasAnyRole(['owner', 'admin']))
                <a href="{{ route('business.branches') }}"
                   class="block border-t border-slate-100 px-3 py-2 text-sm text-indigo-600 hover:bg-slate-50">{{ __('Manage branches') }}</a>
            @endif
        </div>
    </div>
@endif
