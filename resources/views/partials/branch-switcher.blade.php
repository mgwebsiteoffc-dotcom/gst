{{-- Business/branch switcher: shown whenever the login can reach more than one business. --}}
@php
    $__user = auth()->user();
    $__active = \App\Support\TenantContext::get();
    $__options = collect();
    $__isRoot = false;

    /*
     * Decide from the LOGIN'S OWN business (its tenant_id), not from the
     * business currently being viewed and not from the session:
     *
     *  - head-office user  -> may switch across the whole group
     *  - branch staff      -> single business, switcher hidden (and any
     *                         tampered session value is ignored entirely)
     *
     * Building the list from the viewed business instead was the bug: once a
     * branch became active the list collapsed to one entry and the switcher
     * vanished, leaving no way back to the head office.
     */
    if ($__active && $__user && ! $__user->is_super_admin) {
        $__own = $__user->tenant_id ? \App\Models\Tenant::find($__user->tenant_id) : null;
        $__isRoot = (bool) ($__own && ! $__own->parent_tenant_id);

        $__options = $__isRoot
            ? collect([$__own])->merge($__own->branches()->get())->unique('id')->values()
            : collect([$__active]);
    }
@endphp

@if ($__options->count() > 1)
    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open" @click.outside="open = false" type="button"
                class="flex max-w-[7.5rem] items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 sm:max-w-[11rem] sm:px-2.5"
                title="{{ __('Switch business') }}">
            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5"/></svg>
            <span class="truncate">{{ $__active->name }}</span>
            <span class="rounded bg-slate-100 px-1 text-[10px] font-semibold text-slate-500">{{ $__options->count() }}</span>
            <svg class="h-3 w-3 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="m19 9-7 7-7-7"/></svg>
        </button>

        <div x-show="open" x-cloak x-transition.origin.top.right
             class="absolute right-0 z-30 mt-2 w-72 rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
            <p class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ __('Business & Branches') }}</p>

            @foreach ($__options as $option)
                @php $isCurrent = (int) $__active->id === (int) $option->id; @endphp
                <form method="POST" action="{{ route('business.switch') }}">
                    @csrf
                    <input type="hidden" name="tenant_id" value="{{ $option->id }}">
                    <button type="submit" @disabled($isCurrent)
                            class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm {{ $isCurrent ? 'bg-indigo-50/60 font-semibold text-indigo-700' : 'text-slate-600 hover:bg-slate-50' }}">
                        <span class="min-w-0">
                            <span class="block truncate">{{ $option->name }}</span>
                            <span class="block text-[11px] font-normal text-slate-400">
                                {{ $option->parent_tenant_id ? __('Branch') : __('Head office') }}
                                @if ($option->gstin) · <span class="font-mono">{{ $option->gstin }}</span>@endif
                            </span>
                        </span>
                        @if ($isCurrent)<span class="text-indigo-600">✓</span>@endif
                    </button>
                </form>
            @endforeach

            @if ($__isRoot && auth()->user()->hasAnyRole(['owner', 'admin']))
                <a href="{{ route('business.branches') }}"
                   class="block border-t border-slate-100 px-3 py-2 text-sm font-medium text-indigo-600 hover:bg-slate-50">
                    {{ __('Manage branches') }}
                </a>
            @endif
        </div>
    </div>
@endif
