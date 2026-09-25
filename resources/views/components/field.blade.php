{{-- DDE-Mart UI — labeled field with error (original) --}}
@props(['label' => null, 'for' => null, 'error' => null, 'hint' => null])

<div>
    @if ($label)
        <label @if($for) for="{{ $for }}" @endif class="label">{{ $label }}</label>
    @endif
    {{ $slot }}
    @if ($error)<p class="field-error">{{ $error }}</p>@endif
    @if ($hint)<p class="hint">{{ $hint }}</p>@endif
</div>
