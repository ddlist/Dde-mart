{{-- DDE-Mart Admin — ops settings editor (original view, UI kit) --}}
<x-admin-layout title="Settings">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-2xl space-y-5">
        @csrf @method('PUT')

        @foreach ($groups as $group => $meta)
            <x-card :title="$meta['label']">
                <div class="space-y-4">
                    @foreach ($meta['keys'] as $key)
                        <x-field :label="ucwords(str_replace('_', ' ', $key))" for="setting-{{ $key }}">
                            <x-input id="setting-{{ $key }}" name="settings[{{ $key }}]" value="{{ old('settings.'.$key, $values[$key] ?? '') }}" />
                        </x-field>
                    @endforeach
                </div>
            </x-card>
        @endforeach

        <div class="alert-warn">
            Payment gateway secrets are NOT managed here — they live in <code>.env</code> only.
        </div>

        <x-btn>Save settings</x-btn>
    </form>
</x-admin-layout>
