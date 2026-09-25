{{-- DDE-Mart UI — page header with optional action (original) --}}
@props(['title', 'sub' => null])

<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <div>
        <h1 class="text-lg font-black tracking-tight">{{ $title }}</h1>
        @if ($sub)<p class="text-sm text-slate-500">{{ $sub }}</p>@endif
    </div>
    @if (isset($action))
        <div class="flex gap-2">{{ $action }}</div>
    @endif
</div>
