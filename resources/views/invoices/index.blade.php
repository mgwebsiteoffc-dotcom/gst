@extends('layouts.app')
@section('title', __(\App\Models\Document::TYPES[$type] ?? 'Documents'))

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold">{{ __(\App\Models\Document::TYPES[$type] ?? 'Document') }}</h1>
        <a href="{{ route('documents.create', $type) }}" class="rounded bg-indigo-600 px-4 py-2 text-white text-sm">{{ __('New') }}</a>
    </div>

    <form class="mb-4 grid gap-2 text-sm sm:flex sm:flex-wrap">
        <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search number or party') }}" class="rounded border-slate-300 px-3 py-2 w-full sm:w-auto sm:max-w-xs">
        <input type="date" name="from" value="{{ request('from') }}" class="rounded border-slate-300 px-3 py-2 w-full sm:w-auto sm:max-w-xs">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded border-slate-300 px-3 py-2 w-full sm:w-auto sm:max-w-xs">
        <select name="status" class="rounded border-slate-300 px-3 py-2 w-full sm:w-auto sm:max-w-xs">
            <option value="">{{ __('All statuses') }}</option>
            @foreach (['draft','sent','partially_paid','paid','overdue','cancelled'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ __(ucfirst(str_replace('_',' ',$s))) }}</option>
            @endforeach
        </select>
        <button class="rounded bg-slate-800 px-4 py-2 text-white w-full sm:w-auto">{{ __('Filter') }}</button>
    </form>

    <div class="rounded-xl bg-white border border-slate-200 overflow-x-auto">
        <table class="w-full min-w-[640px] text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="p-3">{{ __('Number') }}</th><th>{{ __('Date') }}</th><th>{{ __('Party') }}</th><th>GSTIN</th>
                    <th class="text-right">{{ __('Taxable') }}</th><th class="text-right">{{ __('Tax') }}</th><th class="text-right">{{ __('Total') }}</th>
                    <th class="text-right">{{ __('Due') }}</th><th>IRN</th><th>{{ __('Status') }}</th><th></th>
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
                    <td><span class="text-xs px-2 py-0.5 rounded bg-slate-100">{{ __(ucfirst(str_replace('_', ' ', $doc->status))) }}</span></td>
                    <td class="pr-3 text-right"><a class="text-slate-500 text-xs" href="{{ route('documents.pdf', $doc) }}">PDF</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $documents->links() }}</div>
@endsection
