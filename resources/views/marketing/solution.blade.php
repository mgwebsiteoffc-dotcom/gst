@extends('layouts.marketing')

@section('content')
{{-- Hero --}}
<section class="bg-gradient-to-b from-indigo-50 to-white py-16">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <nav aria-label="Breadcrumb" class="mb-5 text-xs text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a>
            <span class="px-1">›</span>{{ $page['heading'] }}
        </nav>

        <div class="grid items-center gap-10 lg:grid-cols-2">
            <div>
                <h1 class="text-4xl font-bold leading-tight tracking-tight text-slate-900 sm:text-5xl">
                    {{ $page['heading'] }}
                </h1>
                <p class="mt-5 text-lg leading-relaxed text-slate-600">{{ $page['lede'] }}</p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700">
                        Start free for 14 days
                    </a>
                    <a href="{{ route('contact') }}" class="rounded-xl border border-slate-300 bg-white px-6 py-3.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Request a demo →
                    </a>
                </div>
            </div>

            @isset($page['image'])
                <x-illustration :name="$page['image']" :alt="$page['heading']" eager
                                class="w-full rounded-2xl" />
            @endisset
        </div>
    </div>
</section>

{{-- Capability grid --}}
<section class="py-16">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900">What you get</h2>
        <div class="mt-10 grid gap-px overflow-hidden rounded-2xl border border-slate-200 bg-slate-200 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($page['points'] as [$title, $desc])
                <article class="bg-white p-6">
                    <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- Step-by-step --}}
@isset($page['steps'])
<section class="border-t border-slate-200 bg-slate-50 py-16">
    <div class="mx-auto max-w-5xl px-4 lg:px-6">
        <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900">How it works</h2>
        <ol class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($page['steps'] as $i => [$title, $desc])
                <li class="relative rounded-2xl border border-slate-200 bg-white p-6">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-indigo-600 text-sm font-semibold text-white">{{ $i + 1 }}</span>
                    <h3 class="mt-3 font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>
@endisset

{{-- Page FAQs, also emitted as FAQPage schema --}}
@isset($page['faqs'])
<section class="py-16">
    <div class="mx-auto max-w-3xl px-4 lg:px-6">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Common questions</h2>
        <dl class="mt-8 divide-y divide-slate-200">
            @foreach ($page['faqs'] as [$q, $a])
                <div x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }" class="py-4">
                    <dt>
                        <button @click="open = !open" class="flex w-full items-start justify-between gap-4 text-left">
                            <span class="font-medium text-slate-900">{{ $q }}</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" style="width:16px;height:16px"
                                 class="mt-1 shrink-0 text-slate-400 transition" :class="open && 'rotate-180'">
                                <path d="m19 9-7 7-7-7"/>
                            </svg>
                        </button>
                    </dt>
                    <dd x-show="open" x-collapse class="mt-2 text-sm leading-relaxed text-slate-600">{{ $a }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>
@endisset

{{-- Cross-links --}}
<section class="border-t border-slate-200 bg-slate-50 py-14">
    <div class="mx-auto max-w-5xl px-4 text-center lg:px-6">
        <h2 class="text-xl font-bold tracking-tight text-slate-900">Explore more</h2>
        <div class="mt-6 flex flex-wrap justify-center gap-2">
            @foreach (\App\Support\SiteMap::SOLUTIONS as $s => $other)
                @if ($s !== $slug)
                    <a href="{{ route('solution', $s) }}"
                       class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">
                        {{ $other['heading'] }}
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
        <a href="{{ route('register') }}" class="mt-7 inline-block rounded-xl bg-white px-7 py-3.5 text-sm font-semibold text-slate-900 hover:bg-slate-100">
            Create your free account
        </a>
    </div>
</section>
@endsection
