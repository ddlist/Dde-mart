{{-- DDE-Mart Admin — brand form (original view, UI kit) --}}
<x-admin-layout title="{{ $brand->exists ? 'Edit brand' : 'New brand' }}">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="max-w-xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $brand->exists ? 'Edit brand' : 'New brand' }}">
            <div class="space-y-4">
                <x-field label="Section" for="section_id">
                    <x-select id="section_id" name="section_id">
                        <option value="">— No section —</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected((string) old('section_id', $brand->section_id) === (string) $section->id)>{{ $section->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Name" for="name" :error="$errors->first('name')">
                    <x-input id="name" name="name" required value="{{ old('name', $brand->name) }}" />
                </x-field>
                <x-field label="Slug (blank = auto)" for="slug" :error="$errors->first('slug')">
                    <x-input id="slug" name="slug" value="{{ old('slug', $brand->slug) }}" />
                </x-field>
                @include('admin.catalog.partials.image-field', ['model' => $brand])
                <x-check name="is_active" label="Active" :checked="old('is_active', $brand->is_active ?? true)" />
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $brand->exists ? 'Save changes' : 'Create brand' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.brands.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
