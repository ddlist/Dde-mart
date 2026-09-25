{{-- DDE-Mart UI — inline SVG icon set (original, hand-drawn 1.7-stroke icons) --}}
@props(['name' => 'box', 'class' => 'h-5 w-5'])

@php
$paths = [
    'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.8-3.2 3.4-5 6.5-5s5.7 1.8 6.5 5"/><circle cx="17" cy="9" r="2.8"/><path d="M16 15.2c2.6.3 4.6 1.9 5.3 4.3"/>',
    'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/>',
    'shield' => '<path d="M12 2.5 20 6v6c0 5-3.5 8.5-8 9.5C7.5 20.5 4 17 4 12V6z"/><path d="m9 12 2 2 4-4.5"/>',
    'cart' => '<path d="M2.5 4h2.5l2.6 12h11.2l2.2-8H6"/><circle cx="9.5" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/>',
    'tag' => '<path d="M3 3h8l10 10-8 8L3 11z"/><circle cx="8" cy="8" r="1.5"/>',
    'cube' => '<path d="M12 2.5 21 7.5v9l-9 5-9-5v-9z"/><path d="M12 12.5 21 7.5M12 12.5 3 7.5M12 12.5v9"/>',
    'store' => '<path d="M3.5 9 5 3.5h14L20.5 9"/><path d="M3.5 9h17v3a2.5 2.5 0 0 1-5 0 2.5 2.5 0 0 1-5 0 2.5 2.5 0 0 1-5 0 2.5 2.5 0 0 1-2-1z"/><path d="M5 14.5V20h14v-5.5"/>',
    'ticket' => '<path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4z"/><path d="M14 6v2M14 11v2M14 16v2"/>',
    'card' => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19M6 15h4"/>',
    'bell' => '<path d="M6 9a6 6 0 0 1 12 0c0 5 2 6.5 2 6.5H4S6 14 6 9"/><path d="M10 19.5a2.2 2.2 0 0 0 4 0"/>',
    'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.4 3.8 5.6 3.8 9S14.5 18.6 12 21c-2.5-2.4-3.8-5.6-3.8-9S9.5 5.4 12 3z"/>',
    'chart' => '<path d="M3 3v18h18"/><path d="M7 15v-5M12 15V8M17 15v-8"/>',
    'cog' => '<circle cx="12" cy="12" r="3.2"/><path d="M12 2.8v3M12 18.2v3M2.8 12h3M18.2 12h3M5 5l2.1 2.1M16.9 16.9 19 19M19 5l-2.1 2.1M7.1 16.9 5 19"/>',
    'pin' => '<path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>',
    'mail' => '<rect x="2.5" y="4.5" width="19" height="15" rx="2.5"/><path d="m3.5 7 8.5 6 8.5-6"/>',
    'doc' => '<path d="M6 2.5h8L19 8v13.5H6z"/><path d="M13.5 2.5V8H19"/>',
    'star' => '<path d="m12 2.8 2.8 5.8 6.4.9-4.6 4.5 1.1 6.3L12 17.2l-5.7 3.1 1.1-6.3L2.8 9.5l6.4-.9z"/>',
    'plus' => '<path d="M12 5v14M5 12h14"/>',
    'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.8-3.8"/>',
    'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
    'check' => '<path d="m4.5 12.5 5 5 10-11"/>',
    'back' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
    'out' => '<path d="M14 4h6v16h-6M10 8l-4 4 4 4M6 12h11"/>',
    'eye' => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/>',
    'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
    'wallet' => '<path d="M3 7a2 2 0 0 1 2-2h13v13H5a2 2 0 0 1-2-2z"/><path d="M3 7v10M18 9h3a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-3"/><circle cx="18.5" cy="13" r="1.2"/>',
];
@endphp

<svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $paths[$name] ?? $paths['cube'] !!}
</svg>
