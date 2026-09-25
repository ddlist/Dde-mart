{{-- DDE-Mart Admin — rental vehicle type form (original view, UI kit) --}}
<x-admin-layout title="{{ $type->exists ? 'Edit vehicle type' : 'New vehicle type' }}">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="max-w-xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $type->exists ? 'Edit vehicle type' : 'New vehicle type' }}">
            <div class="space-y-4">
                <x-field label="Section" for="section_id">
                    <x-select id="section_id" name="section_id">
                        <option value="">— No section —</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected((string) old('section_id', $type->section_id) === (string) $section->id)>{{ $section->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Name" for="name" :error="$errors->first('name')">
                    <x-input id="name" name="name" required value="{{ old('name', $type->name) }}" placeholder="e.g. Van" />
                </x-field>
                <x-field label="Slug (blank = auto)" for="slug">
                    <x-input id="slug" name="slug" value="{{ old('slug', $type->slug) }}" />
                </x-field>
                <x-field label="Capacity" for="capacity">
                    <x-input id="capacity" name="capacity" type="number" min="1" value="{{ old('capacity', $type->capacity) }}" />
                </x-field>
                <x-field label="Description" for="description">
                    <x-textarea id="description" name="description" rows="2">{{ old('description', $type->description) }}</x-textarea>
                </x-field>
                <div>
                    <span class="label">Icon</span>
                    @if ($type->icon_path)
                        <img src="{{ \App\Support\Images::url($type->icon_path) }}" alt="" class="mb-2 h-16 w-16 rounded-xl object-cover">
                        <div class="mb-2"><x-check name="remove_icon" label="Remove current icon" /></div>
                    @endif
                    <input name="icon" type="file" accept="image/*" class="file">
                    @error('icon')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <x-check name="is_active" label="Active" :checked="old('is_active', $type->is_active ?? true)" />
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $type->exists ? 'Save changes' : 'Create type' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.rental-types.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
