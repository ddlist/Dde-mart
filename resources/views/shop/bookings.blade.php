{{-- DDE-Mart storefront — my bookings (original view) --}}
<x-store-layout title="My Bookings">
    <h1 class="mb-4 text-xl font-black tracking-tight">My bookings</h1>
    @if ($bookings->isEmpty())
        <x-empty message="No bookings yet.">
            <x-slot:action><x-btn href="{{ route('shop.services') }}">Browse services</x-btn></x-slot:action>
        </x-empty>
    @else
        <div class="space-y-3">
            @foreach ($bookings as $booking)
                <a href="{{ route('shop.bookings.track', $booking) }}"
                   class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div>
                        <p class="font-bold">{{ $booking->number }}</p>
                        <p class="text-xs text-slate-400">{{ $booking->scheduled_at?->format('d M Y, H:i') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-black">{{ number_format($booking->total, 2) }}</p>
                        <p class="text-xs uppercase text-slate-400">{{ $booking->statusLabel() }}</p>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-4">{{ $bookings->links() }}</div>
    @endif
</x-store-layout>
