{{-- DDE-Mart Admin — provider form (original view, UI kit) --}}
<x-admin-layout title="{{ $provider->exists ? 'Edit provider' : 'New provider' }}">
    <x-page-head :title="$provider->exists ? 'Edit provider' : 'New provider'" sub="Contact, bank and commission." />

    <form method="POST" action="{{ $provider->exists ? route('admin.providers.update', $provider) : route('admin.providers.store') }}" class="max-w-2xl space-y-5">
        @csrf @if ($provider->exists) @method('PUT') @endif

        <x-card title="Provider">
            <div class="space-y-4">
                <x-field label="Name" for="name" :error="$errors->first('name')">
                    <x-input id="name" name="name" required value="{{ old('name', $provider->name) }}" />
                </x-field>
                <x-field label="Phone" for="phone" :error="$errors->first('phone')">
                    <x-input id="phone" name="phone" required value="{{ old('phone', $provider->phone) }}" />
                </x-field>
                <x-field label="Email (optional)" for="email" :error="$errors->first('email')">
                    <x-input id="email" name="email" type="email" value="{{ old('email', $provider->email) }}" />
                </x-field>
                <x-field label="Address (optional)" for="address">
                    <x-input id="address" name="address" value="{{ old('address', $provider->address) }}" />
                </x-field>
            </div>
        </x-card>

        <x-card title="Commission">
            <div class="space-y-4">
                <x-field label="Type" for="commission_type">
                    <x-select id="commission_type" name="commission_type">
                        <option value="percentage" @selected(old('commission_type', $provider->commission_type ?? 'percentage') === 'percentage')>Percentage</option>
                        <option value="fixed" @selected(old('commission_type', $provider->commission_type ?? 'percentage') === 'fixed')>Fixed</option>
                    </x-select>
                </x-field>
                <x-field label="Value" for="commission_value" :error="$errors->first('commission_value')">
                    <x-input id="commission_value" name="commission_value" type="number" step="0.01" min="0" value="{{ old('commission_value', $provider->commission_value) }}" />
                </x-field>
            </div>
        </x-card>

        <x-card title="Bank details">
            <div class="space-y-4">
                <x-field label="Bank name" for="bank_name">
                    <x-input id="bank_name" name="bank_name" value="{{ old('bank_name', $provider->bank_name) }}" />
                </x-field>
                <x-field label="Branch" for="bank_branch">
                    <x-input id="bank_branch" name="bank_branch" value="{{ old('bank_branch', $provider->bank_branch) }}" />
                </x-field>
                <x-field label="Account holder" for="bank_holder">
                    <x-input id="bank_holder" name="bank_holder" value="{{ old('bank_holder', $provider->bank_holder) }}" />
                </x-field>
                <x-field label="Account number" for="bank_account">
                    <x-input id="bank_account" name="bank_account" value="{{ old('bank_account', $provider->bank_account) }}" />
                </x-field>
                <x-field label="Other info" for="bank_other">
                    <x-input id="bank_other" name="bank_other" value="{{ old('bank_other', $provider->bank_other) }}" />
                </x-field>
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $provider->exists ? 'Save changes' : 'Create provider' }}</x-btn>
            <x-btn variant="ghost" href="{{ $provider->exists ? route('admin.providers.show', $provider) : route('admin.providers.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
