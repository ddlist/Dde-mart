{{-- DDE-Mart Admin — push template form (original view, UI kit) --}}
<x-admin-layout title="{{ $template->exists ? 'Edit template' : 'New template' }}">
    <form method="POST" action="{{ $action }}" class="max-w-xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $template->exists ? 'Edit template' : 'New template' }}">
            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Key" for="key" :error="$errors->first('key')">
                        <x-input id="key" name="key" required value="{{ old('key', $template->key) }}" placeholder="order.accepted" class="font-mono" />
                    </x-field>
                    <x-field label="Audience" for="audience">
                        <x-select id="audience" name="audience">
                            @foreach (\App\Models\Notification::AUDIENCES as $audience)
                                <option value="{{ $audience }}" @selected(old('audience', $template->audience ?? 'customer') === $audience)>{{ ucfirst($audience) }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                </div>
                <x-field label="Subject" for="subject">
                    <x-input id="subject" name="subject" required value="{{ old('subject', $template->subject) }}" />
                </x-field>
                <x-field label="Body" for="body">
                    <x-textarea id="body" name="body" required>{{ old('body', $template->body) }}</x-textarea>
                </x-field>
                <x-check name="is_active" label="Active" :checked="old('is_active', $template->is_active ?? true)" />
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $template->exists ? 'Save changes' : 'Create template' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.push.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
