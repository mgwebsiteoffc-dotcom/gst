@extends('layouts.app')
@section('title', \App\Models\Document::TYPES[$type] ?? 'Documents')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold">{{ \Illuminate\Support\Str::plural(\App\Models\Document::TYPES[$type] ?? 'Document') }}</h1>
        <a href="{{ route('documents.create', $type) }}" class="rounded bg-indigo-600 px-4 py-2 text-white text-sm">New</a>
    </div>

    <div class="flex flex-wrap gap-1 border-b border-slate-200 mb-4">
        @foreach (['active' => 'Active', 'discarded' => 'Discarded'] as $key => $label)
            <a href="{{ route('documents.index', array_merge(['type' => $type], request()->except(['view', 'status', 'page']), ['view' => $key])) }}"
               class="-mb-px border-b-2 px-4 py-2.5 text-sm transition {{ $view === $key
                   ? 'border-indigo-600 font-medium text-indigo-700'
                   : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <form class="mb-4 flex gap-2 text-sm">
        <input type="hidden" name="view" value="{{ $view }}">
        <input name="q" value="{{ request('q') }}" placeholder="Search number or party" class="rounded border-slate-300 px-3 py-2">
        <input type="date" name="from" value="{{ request('from') }}" class="rounded border-slate-300 px-3 py-2">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded border-slate-300 px-3 py-2">
        @if ($view === 'active')
            <select name="status" class="rounded border-slate-300 px-3 py-2">
                <option value="">All statuses</option>
                @foreach (['draft','sent','partially_paid','paid','overdue'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
        @endif
        <button class="rounded bg-slate-800 px-4 py-2 text-white">Filter</button>
    </form>

    <div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="p-3">Number</th><th>Date</th><th>Party</th><th>GSTIN</th>
                    <th class="text-right">Taxable</th><th class="text-right">Tax</th><th class="text-right">Total</th>
                    <th class="text-right">Due</th><th>IRN</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($documents as $doc)
                <tr class="border-t">
                    <td class="p-3"><a class="text-indigo-600" href="{{ route('documents.show', $doc) }}">{{ $doc->number }}</a></td>
                    <td>{{ $doc->issue_date->format('d M Y') }}</td>
                    <td>{{ $doc->party?->name ?? '—' }}</td>
                    <td class="font-mono text-xs">{{ $doc->party?->gstin }}</td>
                    <td class="text-right">{{ number_format($doc->taxable_value, 2) }}</td>
                    <td class="text-right">{{ number_format($doc->tax_total, 2) }}</td>
                    <td class="text-right font-medium">{{ number_format($doc->grand_total, 2) }}</td>
                    <td class="text-right">{{ number_format($doc->balance_due, 2) }}</td>
                    <td>@if ($doc->einvoice?->isGenerated())<span class="text-emerald-600 text-xs">✔</span>@endif</td>
                    <td><span class="text-xs px-2 py-0.5 rounded bg-slate-100">{{ $doc->status }}</span></td>
                    <td class="pr-3 text-right"><a class="text-slate-500 text-xs" href="{{ route('documents.pdf', $doc) }}">PDF</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $documents->links() }}</div>
@endsection
