{{-- DDE-Mart Admin — banner form (original view, UI kit) --}}
<x-admin-layout title="{{ $banner->exists ? 'Edit banner' : 'New banner' }}">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="max-w-xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $banner->exists ? 'Edit banner' : 'New banner' }}">
            <div class="space-y-4">
                <x-field label="Section (blank = all)" for="section_id">
                    <x-select id="section_id" name="section_id">
                        <option value="">— All sections —</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected((string) old('section_id', $banner->section_id) === (string) $section->id)>{{ $section->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Title" for="title" :error="$errors->first('title')">
                    <x-input id="title" name="title" required value="{{ old('title', $banner->title) }}" />
                </x-field>
                @include('admin.catalog.partials.image-field', ['model' => $banner])
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Redirects to" for="redirect_type">
                        <x-select id="redirect_type" name="redirect_type">
                            @foreach (\App\Models\Banner::REDIRECT_TYPES as $type)
                                <option value="{{ $type }}" @selected(old('redirect_type', $banner->redirect_type ?? 'none') === $type)>{{ ucfirst($type) }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                    <x-field label="Target (id or URL)" for="redirect_target">
                        <x-input id="redirect_target" name="redirect_target" value="{{ old('redirect_target', $banner->redirect_target) }}" />
                    </x-field>
                    <x-field label="Position" for="position">
                        <x-select id="position" name="position">
                            @foreach (\App\Models\Banner::POSITIONS as $position)
                                <option value="{{ $position }}" @selected(old('position', $banner->position ?? 'home') === $position)>{{ ucfirst($position) }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                    <x-field label="Order" for="sort_order">
                        <x-input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $banner->sort_order ?? 0) }}" />
                    </x-field>
                </div>
                <x-check name="is_active" label="Active" :checked="old('is_active', $banner->is_active ?? true)" />
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $banner->exists ? 'Save changes' : 'Create banner' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.banners.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
