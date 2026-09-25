{{-- Original DDE-Mart stat card (anonymous Blade component) --}}
@props(['label', 'value' => '—', 'hint' => null])

<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $label }}</p>
    <p class="mt-2 text-3xl font-black tracking-tight">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
    @endif
</div>
