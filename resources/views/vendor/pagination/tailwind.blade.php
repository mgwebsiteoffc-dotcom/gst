{{--
    Branded pagination.

    Laravel's built-in views live in vendor/, which Tailwind never scans, so
    their utility classes were dropped and the controls rendered unstyled and
    overlapping. This view is ours, so the classes are compiled and the result
    matches the rest of the product.

    Mobile: compact "Previous — page X of Y — Next".
    Desktop: numbered buttons with ellipsis.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination') }}"
         class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">

        {{-- Result count --}}
        <p class="text-xs text-slate-500 sm:text-sm">
            {{ __('Showing') }}
            <span class="font-medium text-slate-700">{{ $paginator->firstItem() }}</span>
            {{ __('to') }}
            <span class="font-medium text-slate-700">{{ $paginator->lastItem() }}</span>
            {{ __('of') }}
            <span class="font-medium text-slate-700">{{ $paginator->total() }}</span>
            {{ __('results') }}
        </p>

        <div class="flex items-center gap-1.5">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-400 sm:text-sm">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15 6-6 6 6 6"/></svg>
                    <span class="hidden sm:inline">{{ __('Previous') }}</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 sm:text-sm">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15 6-6 6 6 6"/></svg>
                    <span class="hidden sm:inline">{{ __('Previous') }}</span>
                </a>
            @endif

            {{-- Numbers: hidden on small screens to keep the row tidy --}}
            <div class="hidden items-center gap-1 sm:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="grid h-9 w-9 place-items-center text-sm text-slate-400">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                      class="grid h-9 w-9 place-items-center rounded-lg bg-brand-600 text-sm font-semibold text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}"
                                   class="grid h-9 w-9 place-items-center rounded-lg border border-slate-300 bg-white text-sm text-slate-700 transition hover:bg-slate-50">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Compact position indicator on phones --}}
            <span class="text-xs text-slate-500 sm:hidden">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 sm:text-sm">
                    <span class="hidden sm:inline">{{ __('Next') }}</span>
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/></svg>
                </a>
            @else
                <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-400 sm:text-sm">
                    <span class="hidden sm:inline">{{ __('Next') }}</span>
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
