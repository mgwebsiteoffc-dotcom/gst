@extends('layouts.marketing')

@section('content')
<article class="mx-auto max-w-3xl px-4 py-12 lg:px-6">
    <nav aria-label="Breadcrumb" class="mb-6 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-brand-600">{{ __('Home') }}</a> ›
        <a href="{{ route('blog.index') }}" class="hover:text-brand-600">Blog</a>
        @if ($post->category)
            › <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}" class="hover:text-brand-600">{{ $post->category->name }}</a>
        @endif
    </nav>

    <header>
        @if ($post->category)
            <span class="text-xs font-medium uppercase tracking-wide text-brand-600">{{ $post->category->name }}</span>
        @endif
        <h1 class="mt-2 text-4xl font-bold leading-tight tracking-tight text-slate-900">{{ $post->title }}</h1>
        @if ($post->excerpt)
            <p class="mt-4 text-lg leading-relaxed text-slate-600">{{ $post->excerpt }}</p>
        @endif
        <p class="mt-5 flex flex-wrap items-center gap-2 text-sm text-slate-500">
            <span>{{ $post->author?->name ?? config('app.name').' Team' }}</span> ·
            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('d M Y') }}</time> ·
            <span>{{ $post->reading_minutes }} {{ __('min read') }}</span>
        </p>
    </header>

    @if ($post->cover_image)
        <img src="{{ Storage::url($post->cover_image) }}" alt="{{ $post->cover_alt ?: $post->title }}"
             class="mt-8 w-full rounded-xl object-cover" width="768" height="384">
    @endif

    @php $toc = $post->tableOfContents(); @endphp
    @if (count($toc) > 2)
        <nav class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5" aria-label="Table of contents">
            <p class="text-sm font-semibold text-slate-900">{{ __('In this article') }}</p>
            <ol class="mt-2 space-y-1 text-sm">
                @foreach ($toc as $item)
                    <li><a href="#{{ $item['anchor'] }}" class="text-brand-600 hover:underline">{{ $item['title'] }}</a></li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="prose prose-slate mt-8 max-w-none prose-headings:scroll-mt-24 prose-a:text-brand-600">
        {!! $post->bodyWithAnchors() !!}
    </div>

    {{-- Post FAQs: rendered for humans, and emitted as FAQPage schema for answer engines --}}
    @if ($post->faqs)
        <section class="mt-12 border-t border-slate-200 pt-8">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900">{{ __('Frequently asked questions') }}</h2>
            <dl class="mt-6 divide-y divide-slate-200">
                @foreach ($post->faqs as $faq)
                    <div class="py-4">
                        <dt class="font-medium text-slate-900">{{ $faq['question'] }}</dt>
                        <dd class="mt-1.5 text-sm leading-relaxed text-slate-600">{!! nl2br(e($faq['answer'])) !!}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    {{-- Share: WhatsApp first, because that is where Indian businesses talk --}}
    <div class="mt-10 flex flex-wrap items-center gap-2 border-t border-slate-200 pt-6">
        <span class="text-sm font-medium text-slate-700">{{ __('Share this article') }}</span>
        <a href="https://wa.me/?text={{ rawurlencode($post->title.' — '.route('blog.show', $post->slug)) }}"
           target="_blank" rel="noopener"
           class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700">
            <x-icon name="message" class="h-3.5 w-3.5" /> WhatsApp
        </a>
        <a href="https://twitter.com/intent/tweet?text={{ rawurlencode($post->title) }}&url={{ rawurlencode(route('blog.show', $post->slug)) }}"
           target="_blank" rel="noopener"
           class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">X</a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ rawurlencode(route('blog.show', $post->slug)) }}"
           target="_blank" rel="noopener"
           class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">LinkedIn</a>
        <button type="button"
                onclick="navigator.clipboard.writeText('{{ route('blog.show', $post->slug) }}'); this.textContent='{{ __('Link copied') }}'"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
            {{ __('Copy link') }}
        </button>
    </div>

    <aside class="mt-12 overflow-hidden rounded-2xl bg-brand-900 p-7 text-center">
        <img src="{{ asset('images/brand/logo-on-color.png') }}" alt="{{ config('app.name') }}"
             width="200" height="56" class="mx-auto h-9 w-auto" loading="lazy">
        <p class="mt-5 text-lg font-semibold text-white">{{ __('Make your next GST invoice in 30 seconds') }}</p>
        <p class="mx-auto mt-2 max-w-md text-sm text-brand-100">
            {{ __('Free for 14 days. No card, no setup fee — and your data stays yours.') }}
        </p>
        <a href="{{ route('register') }}" class="mt-5 inline-block rounded-lg bg-white px-6 py-3 text-sm font-semibold text-brand-800 transition hover:bg-brand-50">
            {{ __('Try BikriBook free') }}
        </a>
    </aside>
</article>

@if ($related->count())
<section class="border-t border-slate-200 bg-slate-50 py-14">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">{{ __('Related reading') }}</h2>
        <div class="mt-8 grid gap-6 md:grid-cols-3">
            @foreach ($related as $post)
                @include('marketing.blog._card', ['post' => $post])
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
