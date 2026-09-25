{{-- DDE-Mart Admin — staff user form (original view, UI kit) --}}
<x-admin-layout title="{{ $staff->exists ? 'Edit user' : 'New user' }}">
    <form method="POST" action="{{ $action }}" class="max-w-xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $staff->exists ? 'Edit user' : 'New user' }}">
            <div class="space-y-4">
                <x-field label="Name" for="name" :error="$errors->first('name')">
                    <x-input id="name" name="name" required value="{{ old('name', $staff->name) }}" />
                </x-field>
                <x-field label="Email" for="email" :error="$errors->first('email')">
                    <x-input id="email" name="email" type="email" required value="{{ old('email', $staff->email) }}" />
                </x-field>
                <x-field label="Role" for="role_id" :error="$errors->first('role_id')" :hint="$staff->exists && $staff->is(auth()->user()) ? 'You cannot change your own role.' : null">
                    <x-select id="role_id" name="role_id" required :disabled="$staff->exists && $staff->is(auth()->user())">
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((string) old('role_id', $staff->role_id) === (string) $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </x-select>
                    @if ($staff->exists && $staff->is(auth()->user()))
                        <input type="hidden" name="role_id" value="{{ $staff->role_id }}">
                    @endif
                </x-field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field :label="$staff->exists ? 'Password (leave blank to keep)' : 'Password'" for="password" :error="$errors->first('password')">
                        @if ($staff->exists)
                            <x-input id="password" name="password" type="password" />
                        @else
                            <x-input id="password" name="password" type="password" required />
                        @endif
                    </x-field>
                    <x-field label="Confirm password" for="password_confirmation">
                        <x-input id="password_confirmation" name="password_confirmation" type="password" />
                    </x-field>
                </div>
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $staff->exists ? 'Save changes' : 'Create user' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.users.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
