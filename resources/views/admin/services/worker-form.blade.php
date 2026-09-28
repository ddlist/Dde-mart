{{-- DDE-Mart Admin — worker form (original view, UI kit) --}}
<x-admin-layout title="{{ $worker->exists ? 'Edit worker' : 'New worker' }}">
    <x-page-head :title="$worker->exists ? 'Edit worker' : 'New worker'" sub="Field staff under a provider." />

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ $worker->exists ? route('admin.provider-workers.update', $worker) : route('admin.provider-workers.store') }}" class="space-y-4">
            @csrf @if ($worker->exists) @method('PUT') @endif

            @if ($fixedProvider)
                <input type="hidden" name="provider_id" value="{{ $fixedProvider }}">
            @else
                <x-field label="Provider" for="provider_id" :error="$errors->first('provider_id')">
                    <x-select id="provider_id" name="provider_id" required>
                        <option value="">—</option>
                        @foreach ($providers as $provider)
                            <option value="{{ $provider->id }}" @selected((string) old('provider_id', $worker->provider_id) === (string) $provider->id)>{{ $provider->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
            @endif
            <x-field label="Name" for="name" :error="$errors->first('name')">
                <x-input id="name" name="name" required value="{{ old('name', $worker->name) }}" />
            </x-field>
            <x-field label="Phone" for="phone" :error="$errors->first('phone')">
                <x-input id="phone" name="phone" required value="{{ old('phone', $worker->phone) }}" />
            </x-field>
            <x-field label="Email (optional)" for="email">
                <x-input id="email" name="email" type="email" value="{{ old('email', $worker->email) }}" />
            </x-field>
            <x-field label="Salary (optional)" for="salary">
                <x-input id="salary" name="salary" type="number" step="0.01" min="0" value="{{ old('salary', $worker->salary) }}" />
            </x-field>
            <x-field label="Address (optional)" for="address">
                <x-input id="address" name="address" value="{{ old('address', $worker->address) }}" />
            </x-field>

            <div class="flex gap-2">
                <x-btn>{{ $worker->exists ? 'Save changes' : 'Create worker' }}</x-btn>
                <x-btn variant="ghost" href="{{ route('admin.provider-workers.index') }}">Cancel</x-btn>
            </div>
        </form>
    </x-card>
</x-admin-layout>
