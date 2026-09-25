{{-- DDE-Mart UI — text input (original) --}}
@props(['type' => 'text'])

<input type="{{ $type }}" {{ $attributes->merge(['class' => 'input']) }}>
