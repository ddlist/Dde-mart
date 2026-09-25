{{-- DDE-Mart storefront — location picker (original view) --}}
<x-store-layout title="Location">
    <x-card title="Delivery location" sub="Used to show nearby stores." class="mx-auto max-w-xl">
        <form method="POST" action="{{ route('shop.location.store') }}" class="space-y-4">
            @csrf
            <x-field label="Address label" for="loc-label">
                <x-input id="loc-label" name="label" value="{{ session('shop.address') }}" placeholder="e.g. Home — Gulshan Block 4" />
            </x-field>
            <div class="grid grid-cols-2 gap-4">
                <x-field label="Latitude" for="loc-lat">
                    <x-input id="loc-lat" name="latitude" type="number" step="0.0000001" value="{{ session('shop.lat') }}" />
                </x-field>
                <x-field label="Longitude" for="loc-lng">
                    <x-input id="loc-lng" name="longitude" type="number" step="0.0000001" value="{{ session('shop.lng') }}" />
                </x-field>
            </div>
            <x-btn class="w-full">Save location</x-btn>
        </form>
    </x-card>
</x-store-layout>
