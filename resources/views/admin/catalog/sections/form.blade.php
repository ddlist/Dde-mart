{{-- DDE-Mart Admin — section form (original view, UI kit) --}}
<x-admin-layout title="{{ $section->exists ? 'Edit section' : 'New section' }}">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="max-w-xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $section->exists ? 'Edit section' : 'New section' }}">
            <div class="space-y-4">
                <x-field label="Name" for="name" :error="$errors->first('name')">
                    <x-input id="name" name="name" required value="{{ old('name', $section->name) }}" />
                </x-field>
                <x-field label="Slug (blank = auto)" for="slug" :error="$errors->first('slug')">
                    <x-input id="slug" name="slug" value="{{ old('slug', $section->slug) }}" />
                </x-field>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-field label="Service type" for="service_type">
                        <x-input id="service_type" name="service_type" value="{{ old('service_type', $section->service_type) }}" placeholder="food" />
                    </x-field>
                    <x-field label="Color" for="color">
                        <x-input id="color" name="color" type="color" value="{{ old('color', $section->color ?? '#10b981') }}" class="h-10 cursor-pointer p-1" />
                    </x-field>
                    <x-field label="Order" for="sort_order">
                        <x-input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $section->sort_order ?? 0) }}" />
                    </x-field>
                </div>
                @include('admin.catalog.partials.image-field', ['model' => $section])
                <x-check name="is_active" label="Active" :checked="old('is_active', $section->is_active ?? true)" />
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $section->exists ? 'Save changes' : 'Create section' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.sections.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
