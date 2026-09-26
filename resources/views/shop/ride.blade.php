{{-- DDE-Mart storefront — ride booking (original view) --}}
<x-store-layout title="Book a Ride">
    <h1 class="mb-4 text-xl font-black tracking-tight">Book a ride</h1>

    <form method="POST" action="{{ route('shop.ride.book') }}" class="grid max-w-3xl gap-4">
        @csrf
        <x-card title="Trip">
            <div class="grid gap-3 sm:grid-cols-2">
                <x-field label="Pickup" for="r-src" :error="$errors->first('source')">
                    <x-textarea id="r-src" name="source" rows="2" required>{{ old('source') }}</x-textarea>
                </x-field>
                <x-field label="Dropoff" for="r-dst" :error="$errors->first('destination')">
                    <x-textarea id="r-dst" name="destination" rows="2" required>{{ old('destination') }}</x-textarea>
                </x-field>
                <x-field label="Cab type" for="r-type">
                    <x-select id="r-type" name="cab_type_id">
                        <option value="">Any</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}">{{ $type->name }} (base {{ number_format($type->base_fare, 2) }}, +{{ number_format($type->per_km_fare, 2) }}/km)</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Distance (km)" for="r-dist" :error="$errors->first('distance_km')">
                    <x-input id="r-dist" name="distance_km" type="number" step="0.1" min="0" value="{{ old('distance_km') }}" />
                </x-field>
            </div>
            <x-field label="Notes" for="r-notes" class="mt-3">
                <x-input id="r-notes" name="notes" value="{{ old('notes') }}" />
            </x-field>
            <x-btn class="mt-4">Request ride (pay in cab)</x-btn>
        </x-card>
    </form>
</x-store-layout>
