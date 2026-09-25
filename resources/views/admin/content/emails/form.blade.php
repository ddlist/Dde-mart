{{-- DDE-Mart Admin — email template form (original view, UI kit) --}}
<x-admin-layout title="{{ $template->exists ? 'Edit template' : 'New template' }}">
    <form method="POST" action="{{ $action }}" class="max-w-xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $template->exists ? 'Edit template' : 'New template' }}">
            <div class="space-y-4">
                <x-field label="Key" for="key" :error="$errors->first('key')">
                    <x-input id="key" name="key" required value="{{ old('key', $template->key) }}" placeholder="order.placed" class="font-mono" />
                </x-field>
                <x-field label="Subject" for="subject">
                    <x-input id="subject" name="subject" required value="{{ old('subject', $template->subject) }}" />
                </x-field>
                <x-field label="Body (:placeholders supported)" for="body">
                    <x-textarea id="body" name="body" rows="6" required class="font-mono">{{ old('body', $template->body) }}</x-textarea>
                </x-field>
                <div class="flex gap-5">
                    <x-check name="send_to_admin" label="Also send to admin" :checked="old('send_to_admin', $template->send_to_admin ?? false)" />
                    <x-check name="is_active" label="Active" :checked="old('is_active', $template->is_active ?? true)" />
                </div>
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $template->exists ? 'Save changes' : 'Create template' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.emails.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
