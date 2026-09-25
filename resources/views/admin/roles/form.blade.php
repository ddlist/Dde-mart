{{-- DDE-Mart Admin — role form with ability matrix (original view, UI kit) --}}
<x-admin-layout title="{{ $role->exists ? 'Edit role' : 'New role' }}">
    <form method="POST" action="{{ $action }}" class="max-w-3xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="Role">
            <x-field label="Role name" for="name" :error="$errors->first('name')">
                <x-input id="name" name="name" required value="{{ old('name', $role->name) }}" placeholder="e.g. Store Manager" />
            </x-field>
        </x-card>

        <x-card title="Abilities" sub="Checked abilities are stored as group.ability rows.">
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($catalog as $group => $meta)
                    <fieldset class="rounded-xl border border-slate-200 p-4">
                        <legend class="px-1 text-xs font-bold uppercase tracking-wider text-slate-500">{{ $meta['label'] }}</legend>
                        <div class="space-y-2">
                            @foreach ($meta['abilities'] as $ability => $label)
                                <x-check name="abilities[{{ $group }}][]" :value="$ability" :label="$label"
                                    :checked="in_array($group.'.'.$ability, old('abilities.'.$group, $granted))" />
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $role->exists ? 'Save changes' : 'Create role' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.roles.index') }}">Cancel</x-btn>
        </div>
    </form>
</x-admin-layout>
