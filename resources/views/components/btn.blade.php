{{-- DDE-Mart UI — button (original). Variants: primary|dark|ghost|danger|row|row-danger --}}
@props(['variant' => 'primary', 'href' => null, 'type' => 'submit'])

@php
$classes = [
    'primary' => 'btn-primary',
    'dark' => 'btn-dark',
    'ghost' => 'btn-ghost',
    'danger' => 'btn-danger-outline',
    'row' => 'btn-row-outline',
    'row-danger' => 'btn-danger-outline',
][$variant] ?? 'btn-primary';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
