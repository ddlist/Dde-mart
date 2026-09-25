{{-- DDE-Mart UI — textarea (original) --}}
@props(['rows' => 3])

<textarea rows="{{ $rows }}" {{ $attributes->merge(['class' => 'input']) }}>{{ $slot }}</textarea>
