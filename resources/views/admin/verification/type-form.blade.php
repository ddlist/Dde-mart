{{-- DDE-Mart Admin — document type form (original view, UI kit) --}}
<x-admin-layout title="{{ $type->exists ? 'Edit document type' : 'New document type' }}">
    <form method="POST" action="{{ $action }}" class="max-w-xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $type->exists ? 'Edit document type' : 'New document type' }}">
            <div class="space-y-4">
                <x-field label="Title" for="title" :error="$errors->first('title')">
                    <x-input id="title" name="title" required value="{{ old('title', $type->title) }}" placeholder="e.g. Driving license" />
                </x-field>
                <x-field label="Applies to" for="owner_type">
                    <x-select id="owner_type" name="owner_type">
                        @foreach (\App\Models\DocumentType::OWNERS as $owner)
                            <option value="{{ $owner }}" @selected(old('owner_type', $type->owner_type ?? 'driver') === $owner)>{{ ucfirst($owner) }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <div class="flex gap-5">
                    <x-check name="front_required" label="Front side required" :checked="old('front_required', $type->front_required ?? true)" />
                    <x-check name="back_required" label="Back side required" :checked="old('back_required', $type->back_required ?? false)" />
                    <x-check name="is_active" label="Active" :checked="old('is_active', $type->is_active ?? true)" />
                </div>
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $type->exists ? 'Save changes' : 'Create type' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.doc-types.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
