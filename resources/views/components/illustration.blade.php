@props(['name', 'alt', 'width' => 800, 'height' => 436, 'eager' => false])

@php
    // SVG has no WebP twin; PNG-based illustrations get one for free.
    $svg  = public_path("images/{$name}.svg");
    $isSvg = file_exists($svg);
    $webp = public_path("images/{$name}.webp");
@endphp

@if ($isSvg)
    <img src="{{ asset("images/{$name}.svg") }}" alt="{{ $alt }}"
         width="{{ $width }}" height="{{ $height }}"
         loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async"
         {{ $attributes->merge(['class' => 'w-full']) }}>
@else
    <picture>
        @if (file_exists($webp))
            <source srcset="{{ asset("images/{$name}.webp") }}" type="image/webp">
        @endif
        <img src="{{ asset("images/{$name}.png") }}" alt="{{ $alt }}"
             width="{{ $width }}" height="{{ $height }}"
             loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async"
             @if ($eager) fetchpriority="high" @endif
             {{ $attributes->merge(['class' => 'w-full']) }}>
    </picture>
@endif
