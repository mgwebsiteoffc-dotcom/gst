@props(['status' => null, 'tone' => 'slate'])
@php
    $tones = [
        'draft' => 'bg-slate-100 text-slate-600', 'sent' => 'bg-blue-50 text-blue-700',
        'paid' => 'bg-emerald-50 text-emerald-700', 'partially_paid' => 'bg-amber-50 text-amber-700',
        'overdue' => 'bg-rose-50 text-rose-700', 'cancelled' => 'bg-slate-100 text-slate-400 line-through',
    ];
    $cls = $status ? ($tones[$status] ?? 'bg-slate-100 text-slate-600') : "bg-{$tone}-50 text-{$tone}-700";
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium $cls"]) }}>
    {{ $slot ?: ucfirst(str_replace('_', ' ', (string) $status)) }}
</span>
