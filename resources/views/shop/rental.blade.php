{{-- DDE-Mart storefront — rental booking (original view) --}}
<x-store-layout title="Rent a Ride">
    <h1 class="mb-4 text-xl font-black tracking-tight">Rent a ride</h1>

    @if ($packages->isEmpty())
        <x-empty message="No rental packages right now." />
    @else
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($packages as $package)
                <x-card title="{{ $package->name }}" sub="{{ $package->vehicleType?->name ?? '' }}">
                    <p class="text-2xl font-black">{{ number_format($package->base_fare, 2) }}</p>
                    <p class="text-xs text-slate-500">{{ $package->included_hours }}h · {{ $package->included_km }}km included</p>
                    <form method="POST" action="{{ route('shop.rental.book') }}" class="mt-3 space-y-2">
                        @csrf
                        <input type="hidden" name="package_id" value="{{ $package->id }}">
                        <x-input name="source" required placeholder="Pickup location" />
                        <x-input name="booking_at" type="datetime-local" required />
                        <x-btn class="w-full">Book</x-btn>
                    </form>
                </x-card>
            @endforeach
        </div>
    @endif
</x-store-layout>
