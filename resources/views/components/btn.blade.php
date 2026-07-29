@props(['variant' => 'secondary', 'href' => null, 'size' => 'md'])
@php
    $base = 'inline-flex items-center justify-center gap-1.5 rounded-lg font-medium transition disabled:opacity-50';
    $sizes = ['sm' => 'px-2.5 py-1.5 text-xs', 'md' => 'px-4 py-2 text-sm'];
    $variants = [
        'primary' => 'bg-indigo-600 text-white hover:bg-indigo-700',
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
        'danger' => 'border border-rose-200 bg-white text-rose-600 hover:bg-rose-50',
        'ghost' => 'text-slate-500 hover:bg-slate-100 hover:text-slate-800',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-700',
    ];
    $cls = "$base {$sizes[$size]} {$variants[$variant]}";
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</button>
@endif
