{{-- DDE-Mart Admin — category form (original view, UI kit) --}}
<x-admin-layout title="{{ $category->exists ? 'Edit category' : 'New category' }}">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="max-w-xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $category->exists ? 'Edit category' : 'New category' }}">
            <div class="space-y-4">
                <x-field label="Section" for="section_id">
                    <x-select id="section_id" name="section_id">
                        <option value="">— No section —</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected((string) old('section_id', $category->section_id) === (string) $section->id)>{{ $section->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Name" for="name" :error="$errors->first('name')">
                    <x-input id="name" name="name" required value="{{ old('name', $category->name) }}" />
                </x-field>
                <x-field label="Slug (blank = auto)" for="slug" :error="$errors->first('slug')">
                    <x-input id="slug" name="slug" value="{{ old('slug', $category->slug) }}" />
                </x-field>
                <x-field label="Description" for="description">
                    <x-textarea id="description" name="description">{{ old('description', $category->description) }}</x-textarea>
                </x-field>
                <x-field label="Order" for="sort_order">
                    <x-input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $category->sort_order ?? 0) }}" />
                </x-field>
                @include('admin.catalog.partials.image-field', ['model' => $category])
                <div class="flex gap-5">
                    <x-check name="is_active" label="Active" :checked="old('is_active', $category->is_active ?? true)" />
                    <x-check name="show_in_homepage" label="Show in homepage" :checked="old('show_in_homepage', $category->show_in_homepage ?? false)" />
                </div>
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $category->exists ? 'Save changes' : 'Create category' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.categories.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
