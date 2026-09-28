{{-- DDE-Mart Admin — schedule a push (original view, UI kit) --}}
<x-admin-layout title="Schedule push">
    <x-page-head title="Schedule push" sub="Delivered by the schedule:send cron when due." />

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('admin.scheduled.store') }}" class="space-y-4">
            @csrf
            <x-field label="Audience" for="audience">
                <x-select id="audience" name="audience" required>
                    @foreach (['customer', 'driver', 'vendor', 'provider', 'worker', 'all'] as $audience)
                        <option value="{{ $audience }}">{{ ucfirst($audience) }}</option>
                    @endforeach
                </x-select>
            </x-field>
            <x-field label="Subject" for="subject" :error="$errors->first('subject')">
                <x-input id="subject" name="subject" required value="{{ old('subject') }}" />
            </x-field>
            <x-field label="Message" for="message" :error="$errors->first('message')">
                <x-textarea id="message" name="message" rows="3" required>{{ old('message') }}</x-textarea>
            </x-field>
            <x-field label="Send at" for="send_at" :error="$errors->first('send_at')">
                <x-input id="send_at" name="send_at" type="datetime-local" required value="{{ old('send_at') }}" />
            </x-field>
            <div class="flex gap-2">
                <x-btn>Schedule</x-btn>
                <x-btn variant="ghost" href="{{ route('admin.scheduled.index') }}">Cancel</x-btn>
            </div>
        </form>
    </x-card>
</x-admin-layout>
