{{-- DDE-Mart Admin — zone form (original view, UI kit) --}}
<x-admin-layout title="{{ $zone->exists ? 'Edit zone' : 'New zone' }}">
    <form method="POST" action="{{ $action }}" class="max-w-xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $zone->exists ? 'Edit zone' : 'New zone' }}">
            <div class="space-y-4">
                <x-field label="Name" for="name" :error="$errors->first('name')">
                    <x-input id="name" name="name" required value="{{ old('name', $zone->name) }}" placeholder="Downtown" />
                </x-field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Latitude" for="latitude" :error="$errors->first('latitude')">
                        <x-input id="latitude" name="latitude" type="number" step="0.0000001" value="{{ old('latitude', $zone->latitude) }}" />
                    </x-field>
                    <x-field label="Longitude" for="longitude" :error="$errors->first('longitude')">
                        <x-input id="longitude" name="longitude" type="number" step="0.0000001" value="{{ old('longitude', $zone->longitude) }}" />
                    </x-field>
                </div>
                <x-field label="Service radius (km)" for="radius_km" :error="$errors->first('radius_km')">
                    <x-input id="radius_km" name="radius_km" type="number" step="0.1" min="0.1" required value="{{ old('radius_km', $zone->radius_km ?? 5) }}" />
                </x-field>
                <x-check name="is_active" label="Active" :checked="old('is_active', $zone->is_active ?? true)" />
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $zone->exists ? 'Save changes' : 'Create zone' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.zones.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
