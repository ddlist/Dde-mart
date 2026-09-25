{{-- DDE-Mart Admin — broadcast composer (original view, UI kit) --}}
<x-admin-layout title="New broadcast">
    @if (! $fcm)
        <div class="alert-warn max-w-xl">
            FCM credentials are not configured (<code>FIREBASE_CREDENTIALS</code>). The broadcast will be
            logged as failed — configure credentials to actually deliver.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.notifications.store') }}" class="max-w-xl space-y-5">
        @csrf

        <x-card title="New broadcast">
            <div class="space-y-4">
                <x-field label="Audience topic" for="audience" :error="$errors->first('audience')">
                    <x-select id="audience" name="audience">
                        @foreach (\App\Models\Notification::AUDIENCES as $audience)
                            <option value="{{ $audience }}">{{ ucfirst($audience) }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Subject" for="subject" :error="$errors->first('subject')">
                    <x-input id="subject" name="subject" required value="{{ old('subject') }}" />
                </x-field>
                <x-field label="Message" for="message" :error="$errors->first('message')">
                    <x-textarea id="message" name="message" rows="4" required>{{ old('message') }}</x-textarea>
                </x-field>
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>Send broadcast</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.notifications.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
