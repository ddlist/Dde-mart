{{-- DDE-Mart Admin — tax form (original view, UI kit) --}}
<x-admin-layout title="{{ $tax->exists ? 'Edit tax' : 'New tax' }}">
    <form method="POST" action="{{ $action }}" class="max-w-xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $tax->exists ? 'Edit tax' : 'New tax' }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Country" for="country">
                    <x-input id="country" name="country" required value="{{ old('country', $tax->country ?? 'Pakistan') }}" />
                </x-field>
                <x-field label="Title" for="title" :error="$errors->first('title')">
                    <x-input id="title" name="title" required value="{{ old('title', $tax->title) }}" placeholder="GST" />
                </x-field>
                <x-field label="Type" for="type">
                    <x-select id="type" name="type">
                        @foreach (\App\Models\Tax::TYPES as $type)
                            <option value="{{ $type }}" @selected(old('type', $tax->type ?? 'percentage') === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Value" for="value">
                    <x-input id="value" name="value" type="number" step="0.01" min="0" required value="{{ old('value', $tax->value) }}" />
                </x-field>
            </div>
            <div class="mt-4">
                <x-field label="Section (blank = all)" for="section_id">
                    <x-select id="section_id" name="section_id">
                        <option value="">— All sections —</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected((string) old('section_id', $tax->section_id) === (string) $section->id)>{{ $section->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
            </div>
            <div class="mt-4"><x-check name="is_active" label="Active" :checked="old('is_active', $tax->is_active ?? true)" /></div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $tax->exists ? 'Save changes' : 'Create tax' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.taxes.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
