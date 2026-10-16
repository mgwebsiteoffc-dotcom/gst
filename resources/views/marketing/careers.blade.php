@extends('layouts.marketing')

@section('content')
<section class="mx-auto max-w-3xl px-4 py-16 lg:px-6">
    <h1 class="text-4xl font-bold tracking-tight text-slate-900">{{ $heading }}</h1>
    <p class="mt-4 text-lg text-slate-600">{{ $lede }}</p>

    <div class="mt-10 space-y-4">
        @foreach ([
            ['Senior Laravel Engineer', 'Remote / Ghaziabad', 'Own the GST engine and e-invoicing integrations.'],
            ['Product Designer', 'Remote', 'Design for shopkeepers who have never used software before.'],
            ['Customer Success (Hindi & English)', 'Ghaziabad', 'Onboard businesses and turn their questions into product fixes.'],
        ] as [$role, $where, $what])
            <article class="rounded-2xl border border-slate-200 p-6">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="font-semibold text-slate-900">{{ $role }}</h2>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">{{ $where }}</span>
                </div>
                <p class="mt-2 text-sm text-slate-600">{{ $what }}</p>
                <a href="{{ route('contact') }}" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:underline">Apply </a>
            </article>
        @endforeach
    </div>

    <p class="mt-8 text-sm text-slate-500">
        Nothing that fits? Write to us anyway at
        <a href="mailto:careers@bikribook.com" class="text-indigo-600 hover:underline">careers@bikribook.com</a>.
    </p>
</section>
@endsection
