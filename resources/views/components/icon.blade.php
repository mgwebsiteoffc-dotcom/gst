{{--
    Inline SVG icon set (strokes, 24×24). No emojis, no icon font, no CDN.

    Usage:
        <x-icon name="invoice" class="h-5 w-5" />
        <x-icon name="inventory" class="h-11 w-11 text-brand-600" />

    Add new icons to the $icons map below — keep names semantic, not literal.
--}}
@props(['name', 'class' => 'h-5 w-5'])

@php
    $icons = [
        // ── Business & documents ────────────────────────────────────────
        'invoice' => '<path d="M8 3h8a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M9.5 8h5M9.5 12h5M9.5 16h3"/>',
        'invoice-check' => '<path d="M8 3h8a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M9 9h6M9 13h4"/><path d="m14.5 17 1.5 1.5 2.5-3"/>',
        'truck' => '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17.5" cy="18" r="1.6"/>',
        'inventory' => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5"/><path d="m3 16.5 9 5 9-5"/>',
        'cart' => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2.5 3h2.2l2.3 12.2a1.6 1.6 0 0 0 1.6 1.3h9.6"/><path d="M6.2 7h14l-1.6 7.5"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'message' => '<path d="M21 12a8.5 8.5 0 0 1-12.5 7.5L3 21l1.6-5A8.5 8.5 0 1 1 21 12Z"/>',
        'users' => '<circle cx="9" cy="8" r="3.2"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 5.2a3.2 3.2 0 0 1 0 6.3M17.5 20a6.5 6.5 0 0 0-2-4.7"/>',
        'store' => '<path d="M3.5 9 5 4h14l1.5 5"/><path d="M4 9h17v11H4z"/><path d="M9 20v-6h6v6"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 12h18"/>',
        'wrench' => '<path d="M14.5 6.5a4 4 0 1 1 5 5L11 20a2.8 2.8 0 0 1-4-4l8.5-8.5Z"/>',
        'coffee' => '<path d="M4 9h13v6a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4V9Z"/><path d="M17 10h1.5a2.5 2.5 0 0 1 0 5H17"/><path d="M6 3.5v2M10 3v2M14 3.5v2"/>',
        'factory' => '<path d="M3 21V9l6 4V9l6 4V7l6 4v10z"/><path d="M7 21v-4h3v4"/>',
        'calculator' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8.5 7h7M8.5 11h2M13.5 11h2M8.5 15h2M13.5 15h2"/>',
        'file' => '<path d="M14 3v5h5"/><path d="M19 8v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7z"/>',

        // ── Money & value ───────────────────────────────────────────────
        'rupee' => '<path d="M7 4h10M7 9h10M16 4c0 4-3.6 5-7 5l7 10"/>',
        'wallet' => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/><circle cx="16.5" cy="14.5" r="1.2"/>',
        'trending' => '<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
        'award' => '<circle cx="12" cy="9" r="5.5"/><path d="m8.5 14-1.5 7 5-2.5 5 2.5-1.5-7"/>',
        'shield' => '<path d="M12 3l7.5 3v6c0 4.5-3 8-7.5 9.5C7.5 20 4.5 16.5 4.5 12V6z"/><path d="m9 12 2 2 4-4"/>',
        'lock' => '<rect x="4.5" y="10" width="15" height="10" rx="2"/><path d="M8.5 10V7.5a3.5 3.5 0 0 1 7 0V10"/>',

        // ── Communication ───────────────────────────────────────────────
        'bell' => '<path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-4-5.7V5a2 2 0 1 0-4 0v.3A6 6 0 0 0 6 11v3.2c0 .5-.2 1-.6 1.4L4 17h5"/><path d="M10 17v1a2 2 0 1 0 4 0v-1"/>',
        'signal' => '<path d="M4 18v-5M9 18v-9M14 18V5M19 18v-3"/>',
        'phone' => '<path d="M6.5 3.5 9 4l1 4-2 1.5a10 10 0 0 0 6.5 6.5L16 14l4 1 .5 2.5c0 .8-.7 1.5-1.5 1.5A16 16 0 0 1 3 5c0-.8.7-1.5 1.5-1.5Z"/>',
        'speaker' => '<path d="M4 10v4h3l4 3.5V6.5L7 10H4Z"/><path d="M15 9.5a4 4 0 0 1 0 5M17.5 7a7 7 0 0 1 0 10"/>',
        'megaphone' => '<path d="M4 11v3a2 2 0 0 0 2 2h1l9 4V5L7 9H6a2 2 0 0 0-2 2Z"/><path d="M18.5 9.5a4 4 0 0 1 0 5"/>',
        'graduation' => '<path d="m3 9 9-4 9 4-9 4-9-4Z"/><path d="M7 11.5V16c0 1 2.2 2.5 5 2.5s5-1.5 5-2.5v-4.5"/>',
        'handshake' => '<path d="m8 12 3-3 3 3 3-3 4 4-4 4-3-3-3 3-3-3-4 4 4-4Z" transform="translate(-1 0)"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 3.8 5.7 3.8 9S14.5 18.5 12 21c-2.5-2.5-3.8-5.7-3.8-9S9.5 5.5 12 3Z"/>',
        'target' => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="1"/>',
        'flag' => '<path d="M5 21V4h9l-1 3h6l-1.5 4L19 15h-7l1-3H5"/>',
        'rocket' => '<path d="M13 4c3.5 1 6.5 4 7 7.5-1.5 3-4.5 6-8 7l-3.5-3.5C9.5 11.5 11 7 13 4Z"/><path d="M8.5 15 5 18.5M6 12l-2 2M16 18l-2 2"/><circle cx="14.5" cy="9.5" r="1.4"/>',
        'clip' => '<path d="M16.5 8 9 15.5a2.5 2.5 0 0 0 3.5 3.5l8-8a4.5 4.5 0 0 0-6.5-6.5l-8.5 8.5a6.5 6.5 0 0 0 9 9"/>',
        'mobile' => '<rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/>',
        'refresh' => '<path d="M20 11a8 8 0 1 0-2.5 6"/><path d="M20 5v6h-6"/>',
        'layers' => '<path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z"/><path d="m4 12 8 4.5 8-4.5"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'star' => '<path d="m12 3.5 2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L3.5 9.7l5.9-.9z" fill="currentColor" stroke="none"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'zap' => '<path d="M13 2 4.5 13.5H11L10 22l8.5-11.5H12L13 2Z"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.5 2.5 5.5-6"/>',
        'x-circle' => '<circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/>',
        'store-front' => '<path d="M3.5 9 5 4h14l1.5 5M4 9h17v11H4zM9 20v-5h6v5"/>',
        'building' => '<path d="M4 21V5a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v16"/><path d="M15 21V9h4a1 1 0 0 1 1 1v11M7 7h4M7 11h4M7 15h4M2 21h20"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2 2 2 0 1 1-4 0 1.7 1.7 0 0 0-2.9-1.2l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 3 15a2 2 0 1 1 0-4 1.7 1.7 0 0 0 1.2-2.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.7 1.7 0 0 0 10 4.6a2 2 0 1 1 4 0 1.7 1.7 0 0 0 2.9 1.2l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1A1.7 1.7 0 0 0 21 11a2 2 0 1 1 0 4Z"/>',
    ];

    $path = $icons[$name] ?? null;
@endphp

@if ($path)
    <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"
         aria-hidden="true" focusable="false">{!! $path !!}</svg>
@endif
