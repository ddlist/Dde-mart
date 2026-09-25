{{-- DDE-Mart Admin — language form (original view, UI kit) --}}
<x-admin-layout title="{{ $language->exists ? 'Edit language' : 'New language' }}">
    <form method="POST" action="{{ $action }}" class="max-w-xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $language->exists ? 'Edit language' : 'New language' }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Code" for="code" :error="$errors->first('code')">
                    <x-input id="code" name="code" required value="{{ old('code', $language->code) }}" placeholder="ur" class="font-mono" />
                </x-field>
                <x-field label="Name" for="name">
                    <x-input id="name" name="name" required value="{{ old('name', $language->name) }}" placeholder="Urdu" />
                </x-field>
            </div>
            <div class="mt-4 flex gap-5">
                <x-check name="is_default" label="Default" :checked="old('is_default', $language->is_default ?? false)" />
                <x-check name="is_active" label="Active" :checked="old('is_active', $language->is_active ?? true)" />
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $language->exists ? 'Save changes' : 'Create language' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.languages.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
