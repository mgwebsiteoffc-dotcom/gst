@extends('layouts.marketing')

@section('content')
<section class="bg-gradient-to-b from-indigo-50 to-white py-14">
    <div class="mx-auto max-w-4xl px-4 text-center lg:px-6">
        <h1 class="text-4xl font-bold tracking-tight text-slate-900">Free tools for Indian businesses</h1>
        <p class="mt-4 text-lg text-slate-600">No signup, no limits. Answer a GST question in seconds.</p>
    </div>
</section>

<section class="py-14">
    <div class="mx-auto max-w-6xl px-4 lg:px-6">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($tools as $slug => [$name, $desc])
                <a href="{{ route('tool', $slug) }}" class="group rounded-2xl border border-slate-200 p-6 transition hover:border-indigo-300 hover:shadow-md">
                    <h2 class="font-semibold text-slate-900 group-hover:text-indigo-700">{{ $name }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>
                    <span class="mt-4 inline-block text-sm font-medium text-indigo-600">Open tool →</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endsection
