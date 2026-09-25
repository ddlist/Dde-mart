{{-- DDE-Mart storefront — profile (original view) --}}
<x-store-layout title="Profile">
    <div class="mx-auto max-w-xl space-y-4">
        <x-card title="Profile">
            <form method="POST" action="{{ route('shop.profile.update') }}" class="space-y-4">
                @csrf @method('PUT')
                <x-field label="Name" for="name" :error="$errors->first('name')">
                    <x-input id="name" name="name" required value="{{ old('name', $customer->name) }}" />
                </x-field>
                <x-field label="Email" for="email" :error="$errors->first('email')">
                    <x-input id="email" name="email" type="email" value="{{ old('email', $customer->email) }}" />
                </x-field>
                <x-field label="New password (blank = keep)" for="password" :error="$errors->first('password')">
                    <x-input id="password" name="password" type="password" />
                </x-field>
                <x-field label="Confirm password" for="password_confirmation">
                    <x-input id="password_confirmation" name="password_confirmation" type="password" />
                </x-field>
                <x-btn>Save profile</x-btn>
            </form>
        </x-card>

        <div class="flex gap-2">
            <x-btn variant="ghost" href="{{ route('shop.orders') }}">My orders</x-btn>
            <form method="POST" action="{{ route('shop.logout') }}">
                @csrf
                <x-btn variant="ghost">Sign out</x-btn>
            </form>
        </div>
    </div>
</x-store-layout>
