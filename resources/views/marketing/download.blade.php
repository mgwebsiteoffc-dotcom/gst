@extends('layouts.marketing')

@section('content')
<section class="mx-auto max-w-4xl px-4 py-16 text-center lg:px-6">
    <h1 class="text-4xl font-bold tracking-tight text-slate-900">Use BikriBook anywhere</h1>
    <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-600">
        BikriBook runs in your browser, so there is nothing to download or update. Add it to your
        home screen and it behaves exactly like an app.
    </p>

    <div class="mt-10 grid gap-6 sm:grid-cols-3">
        @foreach ([
            ['Android', 'Open BikriBook in Chrome, tap the ⋮ menu, then "Add to Home screen".'],
            ['iPhone & iPad', 'Open in Safari, tap the Share button, then "Add to Home Screen".'],
            ['Desktop', 'Works in Chrome, Edge, Firefox and Safari. Just bookmark it.'],
        ] as [$device, $how])
            <article class="rounded-2xl border border-slate-200 p-6 text-left">
                <h2 class="font-semibold text-slate-900">{{ $device }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $how }}</p>
            </article>
        @endforeach
    </div>

    <div class="mt-10 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
        <strong>Native apps are on the way.</strong> Android and iOS builds are in development.
        Everything works in the browser today, including on a ₹6,000 phone.
    </div>

    <a href="{{ route('register') }}" class="mt-10 inline-block rounded-xl bg-indigo-600 px-7 py-3.5 text-sm font-semibold text-white hover:bg-indigo-700">Start free for 14 days</a>
</section>
@endsection
