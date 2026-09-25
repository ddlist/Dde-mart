{{-- DDE-Mart UI — checkbox with label (original) --}}
@props(['label' => null, 'name' => null, 'value' => 1, 'checked' => false])

<label class="flex items-center gap-2 text-sm text-slate-700">
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked($checked)
        {{ $attributes->merge(['class' => 'check']) }}>
    @if ($label){{ $label }}@endif
</label>
