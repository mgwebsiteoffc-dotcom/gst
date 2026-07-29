@extends('layouts.marketing')

@section('content')
<section class="border-b border-slate-200 bg-gradient-to-b from-indigo-50 to-white py-14">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <h1 class="text-4xl font-bold tracking-tight text-slate-900">GST &amp; business guides</h1>
        <p class="mt-3 max-w-2xl text-lg text-slate-600">
            Plain-English answers on GST invoicing, e-invoicing, e-way bills and running a small business in India.
        </p>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-12 lg:px-6">
    <div class="mb-8 flex flex-wrap items-center gap-2">
        <a href="{{ route('blog.index') }}"
           class="rounded-full px-3 py-1.5 text-sm {{ ! request('category') ? 'bg-indigo-600 text-white' : 'border border-slate-300 text-slate-600 hover:bg-slate-50' }}">All</a>
        @foreach ($categories as $c)
            <a href="{{ route('blog.index', ['category' => $c->slug]) }}"
               class="rounded-full px-3 py-1.5 text-sm {{ request('category') === $c->slug ? 'bg-indigo-600 text-white' : 'border border-slate-300 text-slate-600 hover:bg-slate-50' }}">
                {{ $c->name }} <span class="opacity-60">{{ $c->posts_count }}</span>
            </a>
        @endforeach

        <form class="ml-auto">
            @if (request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
            <input name="q" value="{{ request('q') }}" placeholder="Search articles"
                   class="w-56 rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </form>
    </div>

    @if ($posts->isEmpty())
        <p class="py-16 text-center text-slate-500">No articles found.</p>
    @else
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                @include('marketing.blog._card', ['post' => $post])
            @endforeach
        </div>
        <div class="mt-10">{{ $posts->links() }}</div>
    @endif
</section>
@endsection
