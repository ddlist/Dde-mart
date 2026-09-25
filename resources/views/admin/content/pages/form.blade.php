{{-- DDE-Mart Admin — CMS page form (original view, UI kit) --}}
<x-admin-layout title="{{ $page->exists ? 'Edit page' : 'New page' }}">
    <form method="POST" action="{{ $action }}" class="max-w-2xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $page->exists ? 'Edit page' : 'New page' }}">
            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Name" for="name" :error="$errors->first('name')">
                        <x-input id="name" name="name" required value="{{ old('name', $page->name) }}" />
                    </x-field>
                    <x-field label="Slug (blank = auto)" for="slug" :error="$errors->first('slug')">
                        <x-input id="slug" name="slug" value="{{ old('slug', $page->slug) }}" />
                    </x-field>
                </div>
                <x-field label="Body" for="body">
                    <x-textarea id="body" name="body" rows="10">{{ old('body', $page->body) }}</x-textarea>
                </x-field>
                <x-check name="is_active" label="Published" :checked="old('is_active', $page->is_active ?? true)" />
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $page->exists ? 'Save changes' : 'Create page' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.pages.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
