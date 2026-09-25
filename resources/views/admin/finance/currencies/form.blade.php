{{-- DDE-Mart Admin — currency form (original view, UI kit) --}}
<x-admin-layout title="{{ $currency->exists ? 'Edit currency' : 'New currency' }}">
    <form method="POST" action="{{ $action }}" class="max-w-xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $currency->exists ? 'Edit currency' : 'New currency' }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Code" for="code" :error="$errors->first('code')">
                    <x-input id="code" name="code" required value="{{ old('code', $currency->code) }}" placeholder="PKR" class="font-mono uppercase" />
                </x-field>
                <x-field label="Name" for="name">
                    <x-input id="name" name="name" required value="{{ old('name', $currency->name) }}" placeholder="Pakistani Rupee" />
                </x-field>
                <x-field label="Symbol" for="symbol">
                    <x-input id="symbol" name="symbol" required value="{{ old('symbol', $currency->symbol) }}" placeholder="₨" />
                </x-field>
                <x-field label="Decimals" for="decimals">
                    <x-input id="decimals" name="decimals" type="number" min="0" max="4" value="{{ old('decimals', $currency->decimals ?? 2) }}" />
                </x-field>
            </div>
            <div class="mt-4 flex flex-wrap gap-5">
                <x-check name="symbol_at_right" label="Symbol on right" :checked="old('symbol_at_right', $currency->symbol_at_right ?? false)" />
                <x-check name="is_default" label="Default currency" :checked="old('is_default', $currency->is_default ?? false)" />
                <x-check name="is_active" label="Active" :checked="old('is_active', $currency->is_active ?? true)" />
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $currency->exists ? 'Save changes' : 'Create currency' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.currencies.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
