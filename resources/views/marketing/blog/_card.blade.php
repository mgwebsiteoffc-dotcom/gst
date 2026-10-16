<article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:shadow-md">
    <a href="{{ route('blog.show', $post->slug) }}" class="block">
        @if ($post->cover_image)
            <img src="{{ Storage::url($post->cover_image) }}" alt="{{ $post->cover_alt ?: $post->title }}"
                 class="h-40 w-full object-cover" loading="lazy" width="400" height="160">
        @else
            <div class="h-40 w-full bg-gradient-to-br from-brand-100 to-slate-100"></div>
        @endif

        <div class="p-5">
            @if ($post->category)
                <span class="text-xs font-medium uppercase tracking-wide text-brand-600">{{ $post->category->name }}</span>
            @endif
            <h3 class="mt-1.5 font-semibold leading-snug text-slate-900 group-hover:text-brand-700">{{ $post->title }}</h3>
            <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-slate-600">{{ $post->excerpt }}</p>
            <p class="mt-3 text-xs text-slate-400">
                <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('d M Y') }}</time>
                · {{ $post->reading_minutes }} {{ __('min read') }}
            </p>
        </div>
    </a>
</article>
