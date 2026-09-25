{{-- DDE-Mart storefront — booking tracking (original view) --}}
<x-store-layout title="Booking {{ $booking->number }}">
    <div class="mb-4">
        <a href="{{ route('shop.bookings') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-800">
            <x-icon name="back" class="h-4 w-4" /> My bookings
        </a>
    </div>
    <x-card title="Booking {{ $booking->number }}" sub="{{ $booking->statusLabel() }} · {{ $booking->service?->title ?? '' }}" class="mx-auto max-w-xl">
        <p class="text-lg font-black">{{ number_format($booking->total, 2) }}</p>
        <p class="text-sm text-slate-500">{{ $booking->provider?->name ?? '' }} · {{ $booking->scheduled_at?->format('d M Y, H:i') }}</p>
        <ol class="mt-4 space-y-3">
            @foreach ($booking->history as $entry)
                <li class="flex gap-3 text-sm">
                    <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500"></span>
                    <div>
                        <p class="font-semibold">{{ $statuses[$entry->to_status] ?? $entry->to_status }}</p>
                        <p class="text-xs text-slate-400">{{ $entry->created_at->format('d M Y, H:i') }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </x-card>
</x-store-layout>
