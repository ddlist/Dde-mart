{{-- DDE-Mart UI — card wrapper (original) --}}
@props(['title' => null, 'sub' => null])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title)
        <div class="mb-4 flex items-start justify-between gap-2">
            <div>
                <h2 class="card-title">{{ $title }}</h2>
                @if ($sub)<p class="card-sub">{{ $sub }}</p>@endif
            </div>
            @if (isset($action))
                <div class="flex shrink-0 gap-2">{{ $action }}</div>
            @endif
        </div>
        {{ $slot }}
    @else
        {{ $slot }}
    @endif
</div>
