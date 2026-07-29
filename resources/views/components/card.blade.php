@props(['title' => null, 'subtitle' => null, 'actions' => null])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white']) }}>
    @if ($title || $actions)
        <header class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3">
            <div>
                @isset($title)
                    <h2 class="font-medium text-slate-900">{{ $title }}</h2>
                @endisset
                @isset($subtitle)
                    <p class="text-sm text-slate-500">{{ $subtitle }}</p>
                @endisset
            </div>
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div class="p-5">{{ $slot }}</div>
</section>
