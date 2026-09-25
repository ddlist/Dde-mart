{{-- DDE-Mart Admin — self-service profile (original view, UI kit) --}}
<x-admin-layout title="My profile">
    <form method="POST" action="{{ route('admin.profile.update') }}" class="max-w-xl space-y-5">
        @csrf @method('PUT')

        <x-card title="Profile">
            <div class="mb-4 flex items-center gap-3">
                <span class="grid h-12 w-12 place-items-center rounded-full bg-slate-900 text-lg font-bold text-white">
                    {{ strtoupper(substr($staff->name ?? 'A', 0, 1)) }}
                </span>
                <div>
                    <p class="font-bold">{{ $staff->name }}</p>
                    <p class="text-xs text-slate-500">{{ $staff->role?->name ?? 'No role' }}</p>
                </div>
            </div>
            <div class="space-y-4">
                <x-field label="Name" for="name" :error="$errors->first('name')">
                    <x-input id="name" name="name" required value="{{ old('name', $staff->name) }}" />
                </x-field>
                <x-field label="Email" for="email" :error="$errors->first('email')">
                    <x-input id="email" name="email" type="email" required value="{{ old('email', $staff->email) }}" />
                </x-field>
            </div>
        </x-card>

        <x-card title="Change password" sub="Optional — leave blank to keep the current one.">
            <div class="space-y-4">
                <x-field label="Current password" for="current_password" :error="$errors->first('current_password')">
                    <x-input id="current_password" name="current_password" type="password" />
                </x-field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="New password" for="password" :error="$errors->first('password')">
                        <x-input id="password" name="password" type="password" />
                    </x-field>
                    <x-field label="Confirm new password" for="password_confirmation">
                        <x-input id="password_confirmation" name="password_confirmation" type="password" />
                    </x-field>
                </div>
            </div>
        </x-card>

        <x-btn>Save profile</x-btn>
    </form>
</x-admin-layout>
