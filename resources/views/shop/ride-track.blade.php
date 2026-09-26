{{-- DDE-Mart storefront — ride tracking (original view) --}}
<x-store-layout title="Ride {{ $ride->number }}">
    <div class="mb-4">
        <a href="{{ route('shop.ride.orders') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-800">
            <x-icon name="back" class="h-4 w-4" /> My rides
        </a>
    </div>
    <x-card title="Ride {{ $ride->number }}" sub="{{ $ride->statusLabel() }}" class="mx-auto max-w-xl">
        <p class="text-sm">{{ $ride->source }} → {{ $ride->destination }}</p>
        <p class="mt-1 text-lg font-black">{{ number_format($ride->total, 2) }}</p>
        @if (in_array($ride->status, ['placed', 'accepted'], true))
            <form method="POST" action="{{ route('shop.ride.cancel', $ride) }}" class="mt-3">
                @csrf
                <x-btn variant="ghost">Cancel this ride</x-btn>
            </form>
        @endif
        <ol class="mt-4 space-y-3">
            @foreach ($ride->history as $entry)
                <li class="flex gap-3 text-sm">
                    <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500"></span>
                    <div>
                        <p class="font-semibold">{{ $entry->to_status }}</p>
                        <p class="text-xs text-slate-400">{{ $entry->created_at->format('d M Y, H:i') }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </x-card>
</x-store-layout>
