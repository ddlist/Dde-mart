{{-- DDE-Mart Admin — gift card form (original view, UI kit) --}}
<x-admin-layout title="{{ $card->exists ? 'Edit gift card' : 'New gift card' }}">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="max-w-xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $card->exists ? 'Edit gift card' : 'New gift card' }}">
            <div class="space-y-4">
                <x-field label="Title" for="title" :error="$errors->first('title')">
                    <x-input id="title" name="title" required value="{{ old('title', $card->title) }}" />
                </x-field>
                <x-field label="Message" for="message">
                    <x-textarea id="message" name="message" rows="2">{{ old('message', $card->message) }}</x-textarea>
                </x-field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Amount" for="amount" :error="$errors->first('amount')">
                        <x-input id="amount" name="amount" type="number" step="0.01" min="0" required value="{{ old('amount', $card->amount) }}" />
                    </x-field>
                    <x-field label="Valid for (days)" for="expiry_days">
                        <x-input id="expiry_days" name="expiry_days" type="number" min="1" value="{{ old('expiry_days', $card->expiry_days ?? 365) }}" />
                    </x-field>
                </div>
                @include('admin.catalog.partials.image-field', ['model' => $card])
                <x-check name="is_active" label="Active" :checked="old('is_active', $card->is_active ?? true)" />
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $card->exists ? 'Save changes' : 'Create gift card' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.gifts.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
