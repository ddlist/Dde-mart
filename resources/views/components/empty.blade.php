{{-- DDE-Mart UI — empty state (original) --}}
@props(['message' => 'Nothing here yet.'])

<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
    <x-icon name="cube" class="mx-auto h-8 w-8 text-slate-300" />
    <p class="mt-2 text-sm text-slate-500">{{ $message }}</p>
    @if (isset($action))
        <div class="mt-3">{{ $action }}</div>
    @endif
</div>
