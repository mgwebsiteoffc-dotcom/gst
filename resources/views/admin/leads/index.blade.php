@extends('layouts.app')
@section('title', 'Enquiries')

@section('content')
<x-page title="Enquiries" subtitle="Messages from the public contact form." />

<x-card class="!p-0">
    @if ($leads->isEmpty())
        <x-empty title="No enquiries yet" message="Contact form submissions will appear here." />
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">From</th>
                        <th class="px-3 font-medium">Message</th>
                        <th class="px-3 font-medium">Received</th>
                        <th class="px-5 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($leads as $lead)
                    <tr class="border-b border-slate-50 last:border-0 align-top">
                        <td class="px-5 py-3">
                            <div class="font-medium text-slate-900">{{ $lead->name }}</div>
                            <div class="text-xs text-slate-500">{{ $lead->email }}</div>
                            @if ($lead->phone)<div class="text-xs text-slate-500">{{ $lead->phone }}</div>@endif
                            @if ($lead->business)<div class="text-xs text-slate-400">{{ $lead->business }}</div>@endif
                        </td>
                        <td class="px-3 py-3 text-slate-600">
                            @if ($lead->subject)<div class="font-medium text-slate-800">{{ $lead->subject }}</div>@endif
                            <p class="max-w-md whitespace-pre-line">{{ $lead->message }}</p>
                        </td>
                        <td class="px-3 py-3 text-xs text-slate-500">{{ $lead->created_at->diffForHumans() }}</td>
                        <td class="px-5 py-3">
                            <form method="POST" action="{{ route('admin.leads.update', $lead) }}">
                                @csrf @method('PUT')
                                <select name="status" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-xs">
                                    @foreach (['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed'] as $v => $l)
                                        <option value="{{ $v }}" @selected($lead->status === $v)>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-card>

<div class="mt-4">{{ $leads->links() }}</div>
@endsection
