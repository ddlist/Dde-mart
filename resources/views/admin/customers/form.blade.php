{{-- DDE-Mart Admin — customer form (original view, UI kit) --}}
<x-admin-layout title="{{ $customer->exists ? 'Edit customer' : 'New customer' }}">
    <x-page-head title="{{ $customer->exists ? 'Edit customer' : 'New customer' }}" sub="Phone is the login identity." />

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ $action }}" class="space-y-4">
            @csrf @method($method)

            <x-field label="Name" for="name" :error="$errors->first('name')">
                <x-input id="name" name="name" required value="{{ old('name', $customer->name) }}" />
            </x-field>
            <x-field label="Phone" for="phone" :error="$errors->first('phone')">
                <x-input id="phone" name="phone" required value="{{ old('phone', $customer->phone) }}" />
            </x-field>
            <x-field label="Email (optional)" for="email" :error="$errors->first('email')">
                <x-input id="email" name="email" type="email" value="{{ old('email', $customer->email) }}" />
            </x-field>
            <x-field label="{{ $customer->exists ? 'New password (leave blank to keep)' : 'Password' }}" for="password" :error="$errors->first('password')">
                <x-input id="password" name="password" type="password" :required="! $customer->exists" />
            </x-field>
            <x-check name="is_active" label="Active" :checked="old('is_active', $customer->is_active ?? true)" />

            <div class="flex gap-2">
                <x-btn>{{ $customer->exists ? 'Save changes' : 'Create customer' }}</x-btn>
                <x-btn variant="ghost" href="{{ route('admin.customers.index') }}">Cancel</x-btn>
            </div>
        </form>
    </x-card>
</x-admin-layout>
