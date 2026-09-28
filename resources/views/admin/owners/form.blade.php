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

        <x-card title="Bank details">
            <div class="space-y-4">
                <x-field label="Bank name" for="bank_name">
                    <x-input id="bank_name" name="bank_name" value="{{ old('bank_name', $owner->bank_name) }}" />
                </x-field>
                <x-field label="Branch" for="bank_branch">
                    <x-input id="bank_branch" name="bank_branch" value="{{ old('bank_branch', $owner->bank_branch) }}" />
                </x-field>
                <x-field label="Account holder" for="bank_holder">
                    <x-input id="bank_holder" name="bank_holder" value="{{ old('bank_holder', $owner->bank_holder) }}" />
                </x-field>
                <x-field label="Account number" for="bank_account">
                    <x-input id="bank_account" name="bank_account" value="{{ old('bank_account', $owner->bank_account) }}" />
                </x-field>
                <x-field label="Other info" for="bank_other">
                    <x-input id="bank_other" name="bank_other" value="{{ old('bank_other', $owner->bank_other) }}" />
                </x-field>
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $owner->exists ? 'Save changes' : 'Create owner' }}</x-btn>
            <x-btn variant="ghost" href="{{ $owner->exists ? route('admin.owners.show', $owner) : route('admin.owners.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
