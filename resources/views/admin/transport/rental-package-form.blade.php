{{-- DDE-Mart Admin — rental package form (original view, UI kit) --}}
<x-admin-layout title="{{ $package->exists ? 'Edit package' : 'New package' }}">
    <form method="POST" action="{{ $action }}" class="max-w-xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $package->exists ? 'Edit package' : 'New package' }}">
            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Vehicle type" for="vehicle_type_id">
                        <x-select id="vehicle_type_id" name="vehicle_type_id">
                            <option value="">—</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->id }}" @selected((string) old('vehicle_type_id', $package->vehicle_type_id) === (string) $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                    <x-field label="Section" for="section_id">
                        <x-select id="section_id" name="section_id">
                            <option value="">—</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}" @selected((string) old('section_id', $package->section_id) === (string) $section->id)>{{ $section->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                </div>
                <x-field label="Name" for="name" :error="$errors->first('name')">
                    <x-input id="name" name="name" required value="{{ old('name', $package->name) }}" placeholder="e.g. 2 Hour | 40 KM" />
                </x-field>
                <x-field label="Description" for="description">
                    <x-textarea id="description" name="description" rows="2">{{ old('description', $package->description) }}</x-textarea>
                </x-field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Base fare" for="base_fare">
                        <x-input id="base_fare" name="base_fare" type="number" step="0.01" min="0" required value="{{ old('base_fare', $package->base_fare ?? 0) }}" />
                    </x-field>
                    <x-field label="Order" for="sort_order">
                        <x-input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $package->sort_order ?? 0) }}" />
                    </x-field>
                    <x-field label="Included hours" for="included_hours">
                        <x-input id="included_hours" name="included_hours" type="number" step="0.01" min="0" value="{{ old('included_hours', $package->included_hours ?? 0) }}" />
                    </x-field>
                    <x-field label="Included km" for="included_km">
                        <x-input id="included_km" name="included_km" type="number" step="0.01" min="0" value="{{ old('included_km', $package->included_km ?? 0) }}" />
                    </x-field>
                    <x-field label="Extra / km" for="extra_km_fare">
                        <x-input id="extra_km_fare" name="extra_km_fare" type="number" step="0.01" min="0" value="{{ old('extra_km_fare', $package->extra_km_fare ?? 0) }}" />
                    </x-field>
                    <x-field label="Extra / minute" for="extra_minute_fare">
                        <x-input id="extra_minute_fare" name="extra_minute_fare" type="number" step="0.01" min="0" value="{{ old('extra_minute_fare', $package->extra_minute_fare ?? 0) }}" />
                    </x-field>
                </div>
                <x-check name="is_active" label="Active" :checked="old('is_active', $package->is_active ?? true)" />
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $package->exists ? 'Save changes' : 'Create package' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.rental-packages.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
