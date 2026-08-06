@extends('layouts.app')
@section('title', 'Settings')

@php
    $tabs = [
        'business' => 'Business details',
        'branding' => 'Logo & signature',
        'invoicing' => 'Invoicing & terms',
        'numbering' => 'Document numbering',
        'whatsapp' => 'WhatsApp',
        'einvoice' => 'e-Invoice (NIC)',
        'team' => 'Team & roles',
    ];
@endphp

@section('content')
<x-page title="Settings" subtitle="Everything that appears on your invoices, in one place." />

<div class="flex flex-wrap gap-1 border-b border-slate-200">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('settings.index', ['tab' => $key]) }}"
           class="-mb-px border-b-2 px-4 py-2.5 text-sm transition {{ $tab === $key
               ? 'border-indigo-600 font-medium text-indigo-700'
               : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="mt-6">
    @include('settings.tabs.'.(array_key_exists($tab, $tabs) ? $tab : 'business'))
</div>
@endsection
