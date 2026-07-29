@extends('layouts.marketing')

@section('content')
<article class="mx-auto max-w-3xl px-4 py-12 lg:px-6">
    <nav aria-label="Breadcrumb" class="mb-6 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a> ›
        <a href="{{ route('blog.index') }}" class="hover:text-indigo-600">Blog</a>
        @if ($post->category)
            › <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}" class="hover:text-indigo-600">{{ $post->category->name }}</a>
        @endif
    </nav>

    <header>
        @if ($post->category)
            <span class="text-xs font-medium uppercase tracking-wide text-indigo-600">{{ $post->category->name }}</span>
        @endif
        <h1 class="mt-2 text-4xl font-bold leading-tight tracking-tight text-slate-900">{{ $post->title }}</h1>
        @if ($post->excerpt)
            <p class="mt-4 text-lg leading-relaxed text-slate-600">{{ $post->excerpt }}</p>
        @endif
        <p class="mt-5 flex flex-wrap items-center gap-2 text-sm text-slate-500">
            <span>{{ $post->author?->name ?? config('app.name').' Team' }}</span> ·
            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('d M Y') }}</time> ·
            <span>{{ $post->reading_minutes }} min read</span>
        </p>
    </header>

    @if ($post->cover_image)
        <img src="{{ Storage::url($post->cover_image) }}" alt="{{ $post->cover_alt ?: $post->title }}"
             class="mt-8 w-full rounded-xl object-cover" width="768" height="384">
    @endif

    @php $toc = $post->tableOfContents(); @endphp
    @if (count($toc) > 2)
        <nav class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5" aria-label="Table of contents">
            <p class="text-sm font-semibold text-slate-900">In this article</p>
            <ol class="mt-2 space-y-1 text-sm">
                @foreach ($toc as $item)
                    <li><a href="#{{ $item['anchor'] }}" class="text-indigo-600 hover:underline">{{ $item['title'] }}</a></li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="prose prose-slate mt-8 max-w-none prose-headings:scroll-mt-24 prose-a:text-indigo-600">
        {!! $post->bodyWithAnchors() !!}
    </div>

    {{-- Post FAQs: rendered for humans, and emitted as FAQPage schema for answer engines --}}
    @if ($post->faqs)
        <section class="mt-12 border-t border-slate-200 pt-8">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Frequently asked questions</h2>
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

    <aside class="mt-12 rounded-xl bg-indigo-600 p-6 text-center">
        <p class="text-lg font-semibold text-white">Make your next GST invoice in 30 seconds</p>
        <a href="{{ route('register') }}" class="mt-4 inline-block rounded-lg bg-white px-5 py-2.5 text-sm font-medium text-indigo-700 hover:bg-indigo-50">
            Try BikriBook free
        </a>
    </aside>
</article>

@if ($related->count())
<section class="border-t border-slate-200 bg-slate-50 py-14">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Related reading</h2>
        <div class="mt-8 grid gap-6 md:grid-cols-3">
            @foreach ($related as $post)
                @include('marketing.blog._card', ['post' => $post])
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
