@extends('layouts.marketing')

@section('content')
<section class="bg-gradient-to-b from-indigo-50 to-white py-16">
    <div class="mx-auto max-w-4xl px-4 text-center lg:px-6">
        <nav aria-label="Breadcrumb" class="mb-4 text-xs text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a> › {{ $page['heading'] }}
        </nav>
        <h1 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">{{ $page['heading'] }}</h1>
        <p class="mx-auto mt-5 max-w-2xl text-lg leading-relaxed text-slate-600">{{ $page['lede'] }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700">Start free for 14 days</a>
            <a href="{{ route('contact') }}" class="rounded-xl border border-slate-300 bg-white px-6 py-3.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Request a demo →</a>
        </div>
    </div>
</section>

<section class="py-16">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <div class="grid gap-px overflow-hidden rounded-2xl border border-slate-200 bg-slate-200 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($page['points'] as [$title, $desc])
                <article class="bg-white p-6">
                    <h2 class="font-semibold text-slate-900">{{ $title }}</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="border-t border-slate-200 bg-slate-50 py-16">
    <div class="mx-auto max-w-4xl px-4 text-center lg:px-6">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Explore more</h2>
        <div class="mt-6 flex flex-wrap justify-center gap-2">
            @foreach (\App\Support\SiteMap::SOLUTIONS as $s => $p)
                @if ($s !== $slug)
                    <a href="{{ route('solution', $s) }}" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:border-indigo-300 hover:text-indigo-700">
                        {{ $p['heading'] }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</section>

<section class="bg-slate-900 py-16">
    <div class="mx-auto max-w-3xl px-4 text-center lg:px-6">
        <h2 class="text-3xl font-bold tracking-tight text-white">Try it free for 14 days</h2>
        <p class="mt-3 text-slate-300">No credit card. No training. Cancel anytime.</p>
        <a href="{{ route('register') }}" class="mt-7 inline-block rounded-xl bg-white px-7 py-3.5 text-sm font-semibold text-slate-900 hover:bg-slate-100">Create your free account</a>
    </div>
</section>
@endsection
