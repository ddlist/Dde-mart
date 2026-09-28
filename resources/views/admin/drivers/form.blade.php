{{-- DDE-Mart Admin — driver form (original view, UI kit) --}}
<x-admin-layout title="{{ $driver->exists ? 'Edit driver' : 'New driver' }}">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="max-w-2xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $driver->exists ? 'Edit driver' : 'New driver' }}">
            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Kind" for="kind">
                        <x-select id="kind" name="kind">
                            @foreach (\App\Models\Driver::KINDS as $kind)
                                <option value="{{ $kind }}" @selected(old('kind', $driver->kind ?? 'ride') === $kind)>{{ ucfirst($kind) }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                    <x-field label="Name" for="name" :error="$errors->first('name')">
                        <x-input id="name" name="name" required value="{{ old('name', $driver->name) }}" />
                    </x-field>
                    <x-field label="Phone" for="phone">
                        <x-input id="phone" name="phone" value="{{ old('phone', $driver->phone) }}" />
                    </x-field>
                    <x-field label="Email" for="email" :error="$errors->first('email')">
                        <x-input id="email" name="email" type="email" value="{{ old('email', $driver->email) }}" />
                    </x-field>
                </div>
                <x-field label="Vehicle info" for="vehicle_info" hint="e.g. Honda CD-70 · ABC-123">
                    <x-input id="vehicle_info" name="vehicle_info" value="{{ old('vehicle_info', $driver->vehicle_info) }}" />
                </x-field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Zone" for="zone_id">
                        <x-select id="zone_id" name="zone_id">
                            <option value="">—</option>
                            @foreach ($zones as $zone)
                                <option value="{{ $zone->id }}" @selected((string) old('zone_id', $driver->zone_id) === (string) $zone->id)>{{ $zone->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                    <x-field label="Store (delivery riders)" for="store_id">
                        <x-select id="store_id" name="store_id">
                            <option value="">—</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" @selected((string) old('store_id', $driver->store_id) === (string) $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                </div>
                <x-field label="Fleet owner (optional)" for="owner_id">
                    <x-select id="owner_id" name="owner_id">
                        <option value="">—</option>
                        @foreach ($owners as $owner)
                            <option value="{{ $owner->id }}" @selected((string) old('owner_id', $driver->owner_id) === (string) $owner->id)>{{ $owner->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <div>
                    <span class="label">Photo</span>
                    @if ($driver->photo_path)
                        <img src="{{ \App\Support\Images::url($driver->photo_path) }}" alt="" class="mb-2 h-20 w-20 rounded-full object-cover">
                        <div class="mb-2"><x-check name="remove_photo" label="Remove current photo" /></div>
                    @endif
                    <input name="photo" type="file" accept="image/*" class="file">
                    @error('photo')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-card>

        <x-card title="Bank details">
            <div class="space-y-4">
                <x-field label="Bank name" for="bank_name">
                    <x-input id="bank_name" name="bank_name" value="{{ old('bank_name', $driver->bank_name) }}" />
                </x-field>
                <x-field label="Branch" for="bank_branch">
                    <x-input id="bank_branch" name="bank_branch" value="{{ old('bank_branch', $driver->bank_branch) }}" />
                </x-field>
                <x-field label="Account holder" for="bank_holder">
                    <x-input id="bank_holder" name="bank_holder" value="{{ old('bank_holder', $driver->bank_holder) }}" />
                </x-field>
                <x-field label="Account number" for="bank_account">
                    <x-input id="bank_account" name="bank_account" value="{{ old('bank_account', $driver->bank_account) }}" />
                </x-field>
                <x-field label="Other info" for="bank_other">
                    <x-input id="bank_other" name="bank_other" value="{{ old('bank_other', $driver->bank_other) }}" />
                </x-field>
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $driver->exists ? 'Save changes' : 'Create driver' }}</x-btn>
            <x-btn variant="ghost" href="{{ $driver->exists ? route('admin.drivers.show', $driver) : route('admin.drivers.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
