@extends('layouts.marketing')

@section('content')
<section class="border-b border-slate-200 bg-gradient-to-b from-brand-50 to-white py-12 lg:py-16">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <nav aria-label="Breadcrumb" class="mb-4 text-xs text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-brand-600">{{ __('Home') }}</a>
            <span class="px-1.5 text-slate-300">/</span>
            <span class="text-slate-700">{{ __('Blog') }}</span>
        </nav>

        <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ __('GST & business guides') }}</h1>
        <p class="mt-3 max-w-2xl text-base text-slate-600 sm:text-lg">
            {{ __('Plain-English answers on GST invoicing, e-invoicing, e-way bills and running a small business in India.') }}
        </p>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-10 lg:px-6">
    {{-- Search + categories: stacked on phones, one row from lg --}}
    <div class="mb-8 grid gap-4 lg:flex lg:items-center lg:justify-between">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('blog.index') }}"
               class="rounded-full px-3 py-1.5 text-sm transition {{ ! request('category') ? 'bg-brand-600 text-white' : 'border border-slate-300 text-slate-600 hover:bg-slate-50' }}">
                {{ __('All') }}
            </a>
            @foreach ($categories as $c)
                @if ($c->posts_count)
                    <a href="{{ route('blog.index', ['category' => $c->slug]) }}"
                       class="rounded-full px-3 py-1.5 text-sm transition {{ request('category') === $c->slug ? 'bg-brand-600 text-white' : 'border border-slate-300 text-slate-600 hover:bg-slate-50' }}">
                        {{ $c->name }} <span class="opacity-60">{{ $c->posts_count }}</span>
                    </a>
                @endif
            @endforeach
        </div>

        <form class="flex w-full gap-2 lg:w-auto">
            @if (request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search articles') }}"
                   class="w-full rounded-lg border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 lg:w-60">
            <button class="shrink-0 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                {{ __('Search') }}
            </button>
        </form>
    </div>

    @if (request('q') || request('category'))
        <p class="mb-5 text-sm text-slate-500">
            {{ __('Showing results for') }}
            <span class="font-medium text-slate-700">{{ request('q') ?: optional($categories->firstWhere('slug', request('category')))->name }}</span>
            <a href="{{ route('blog.index') }}" class="ml-2 text-brand-600 hover:underline">{{ __('Clear') }}</a>
        </p>
    @endif

    @if ($posts->isEmpty())
        <x-card>
            <div class="py-10 text-center">
                <x-icon name="file" class="mx-auto h-10 w-10 text-slate-300" />
                <h2 class="mt-4 text-lg font-semibold text-slate-900">{{ __('No articles found') }}</h2>
                <p class="mt-1.5 text-sm text-slate-500">{{ __('Try a different search, or browse everything we have published.') }}</p>
                <a href="{{ route('blog.index') }}" class="mt-5 inline-block rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    {{ __('View all articles') }}
                </a>
            </div>
        </x-card>
    @else
        {{-- Featured: the newest article gets a wide card --}}
        @php $featured = $posts->first(); $rest = $posts->slice(1); @endphp

        <article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:shadow-lg lg:grid lg:grid-cols-2">
            <a href="{{ route('blog.show', $featured->slug) }}" class="block">
                @if ($featured->cover_image)
                    <img src="{{ Storage::url($featured->cover_image) }}" alt="{{ $featured->cover_alt ?: $featured->title }}"
                         class="h-56 w-full object-cover lg:h-full" loading="eager" width="600" height="400">
                @else
                    <div class="h-56 w-full bg-gradient-to-br from-brand-100 to-slate-100 lg:h-full"></div>
                @endif
            </a>
            <div class="p-6 lg:p-8">
                <span class="inline-block rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-brand-700">
                    {{ __('Latest') }}{{ $featured->category ? ' · '.$featured->category->name : '' }}
                </span>
                <h2 class="mt-3 text-xl font-bold leading-snug text-slate-900 group-hover:text-brand-700 sm:text-2xl">
                    <a href="{{ route('blog.show', $featured->slug) }}">{{ $featured->title }}</a>
                </h2>
                @if ($featured->excerpt)
                    <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-slate-600">{{ $featured->excerpt }}</p>
                @endif
                <div class="mt-5 flex items-center gap-3 text-xs text-slate-500">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-700">
                        {{ mb_substr($featured->author?->name ?? config('app.name'), 0, 1) }}
                    </span>
                    <span>
                        {{ $featured->author?->name ?? config('app.name').' '.__('Team') }} ·
                        <time datetime="{{ $featured->published_at?->toDateString() }}">{{ $featured->published_at?->format('d M Y') }}</time> ·
                        {{ $featured->reading_minutes }} {{ __('min read') }}
                    </span>
                </div>
                <a href="{{ route('blog.show', $featured->slug) }}"
                   class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:text-brand-700">
                    {{ __('Read article') }} <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>
        </article>

        @if ($rest->count())
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($rest as $post)
                    @include('marketing.blog._card', ['post' => $post])
                @endforeach
            </div>
        @endif

        <div class="mt-10">{{ $posts->links() }}</div>
    @endif
</section>
@endsection
