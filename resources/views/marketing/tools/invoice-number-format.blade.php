@extends('layouts.marketing')

@section('content')
<section class="mx-auto max-w-3xl px-4 py-14 lg:px-6">
    <nav aria-label="Breadcrumb" class="mb-4 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a> ›
        <a href="{{ route('tools') }}" class="hover:text-indigo-600">Tools</a> › {{ $name }}
    </nav>

    <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $name }}</h1>
    <p class="mt-3 text-slate-600">{{ $desc }}</p>

    <div class="mt-8 rounded-2xl border border-slate-200 p-6"
         x-data="{ prefix: 'INV', fy: '2026-27', pad: 4, next: 1,
            get sample() { return `${this.prefix}/${this.fy}/${String(this.next).padStart(+this.pad, '0')}`; } }">
        <div class="grid gap-4 sm:grid-cols-4">
            <div><label class="mb-1 block text-xs text-slate-500">Prefix</label>
                <input x-model="prefix" maxlength="6" class="w-full rounded-lg border-slate-300 text-sm"></div>
            <div><label class="mb-1 block text-xs text-slate-500">Financial year</label>
                <input x-model="fy" maxlength="7" class="w-full rounded-lg border-slate-300 text-sm"></div>
            <div><label class="mb-1 block text-xs text-slate-500">Padding</label>
                <input type="number" x-model.number="pad" min="1" max="8" class="w-full rounded-lg border-slate-300 text-sm"></div>
            <div><label class="mb-1 block text-xs text-slate-500">Next number</label>
                <input type="number" x-model.number="next" min="1" class="w-full rounded-lg border-slate-300 text-sm"></div>
        </div>

        <div class="mt-5 rounded-xl bg-slate-900 p-5 text-center">
            <p class="text-xs uppercase tracking-wide text-slate-400">Your invoice number</p>
            <p class="mt-1 font-mono text-2xl font-bold text-white" x-text="sample"></p>
        </div>
    </div>

    <div class="prose prose-slate mt-10 max-w-none">
        <h2>What the law requires</h2>
        <p>Rule 46 of the CGST Rules says an invoice number must be:</p>
        <ul>
            <li><strong>Consecutive</strong> — no gaps, no repeats</li>
            <li><strong>Unique within the financial year</strong> — the series resets each April</li>
            <li><strong>16 characters or fewer</strong></li>
            <li>Made only of letters, digits, hyphens and slashes</li>
        </ul>
        <h2>The most common mistake</h2>
        <p>
            Deleting an invoice and reusing its number. A gap in the series is an audit red flag.
            If an invoice is wrong, cancel it or issue a credit note — never reuse the number.
        </p>
        <p>
            BikriBook enforces all of this automatically, with a database lock so two staff members
            billing at the same time can never get the same number.
        </p>
    </div>
</section>
@endsection
