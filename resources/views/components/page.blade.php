@props(['title', 'subtitle' => null, 'back' => null])

<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        @isset($back)
            <a href="{{ $back }}" class="mb-1 inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-800">&larr; Back</a>
        @endisset

        <h1 class="text-xl font-semibold tracking-tight text-slate-900">{{ $title }}</h1>

        @isset($subtitle)
            <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
        @endisset
    </div>

    <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
</div>
