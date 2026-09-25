{{-- DDE-Mart Admin — owner form (original view, UI kit) --}}
<x-admin-layout title="{{ $owner->exists ? 'Edit owner' : 'New owner' }}">
    <form method="POST" action="{{ $action }}" class="max-w-xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $owner->exists ? 'Edit owner' : 'New owner' }}">
            <div class="space-y-4">
                <x-field label="Name" for="name" :error="$errors->first('name')">
                    <x-input id="name" name="name" required value="{{ old('name', $owner->name) }}" />
                </x-field>
                <x-field label="Phone" for="phone">
                    <x-input id="phone" name="phone" value="{{ old('phone', $owner->phone) }}" />
                </x-field>
                <x-field label="Email" for="email" :error="$errors->first('email')">
                    <x-input id="email" name="email" type="email" value="{{ old('email', $owner->email) }}" />
                </x-field>
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $owner->exists ? 'Save changes' : 'Create owner' }}</x-btn>
            <x-btn variant="ghost" href="{{ $owner->exists ? route('admin.owners.show', $owner) : route('admin.owners.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
