{{--
    Brand lockup: the BikriBook mark plus optional wordmark.

    <x-brand />                 mark + name   (headers, auth pages)
    <x-brand :mark-only="true" />   mark only     (tight spaces, footers)
    <x-brand variant="white" />    white wordmark for dark backgrounds
--}}
@props([
    'markOnly' => false,
    'variant' => 'default',   // default | white
    'size' => 'md',           // sm | md | lg
    'href' => null,
])

@php
    $mark = ['sm' => 'h-7 w-7', 'md' => 'h-8 w-8', 'lg' => 'h-10 w-10'][$size] ?? 'h-8 w-8';
    $text = ['sm' => 'text-base', 'md' => 'text-lg', 'lg' => 'text-xl'][$size] ?? 'text-lg';
    $wordColour = $variant === 'white' ? 'text-white' : 'text-slate-900';
    $tag = $href ? 'a' : 'span';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <img src="{{ asset('images/brand/mark-256.png') }}"
         alt="{{ $markOnly ? config('app.name') : '' }}"
         width="64" height="64"
         class="{{ $mark }} shrink-0 select-none"
         draggable="false">

    @unless ($markOnly)
        <span class="{{ $text }} font-semibold tracking-tight {{ $wordColour }}">{{ config('app.name') }}</span>
    @endunless
</{{ $tag }}>
