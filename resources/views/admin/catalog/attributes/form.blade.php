{{-- DDE-Mart Admin — attribute form with inline values (original view, UI kit) --}}
<x-admin-layout title="{{ $attribute->exists ? 'Edit attribute' : 'New attribute' }}">
    <form method="POST" action="{{ $action }}" class="max-w-xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $attribute->exists ? 'Edit attribute' : 'New attribute' }}">
            <div class="space-y-4">
                <x-field label="Name" for="name" :error="$errors->first('name')">
                    <x-input id="name" name="name" required value="{{ old('name', $attribute->name) }}" placeholder="e.g. Size" />
                </x-field>
                <x-field label="Slug (blank = auto)" for="slug">
                    <x-input id="slug" name="slug" value="{{ old('slug', $attribute->slug) }}" />
                </x-field>
                <x-check name="is_active" label="Active" :checked="old('is_active', $attribute->is_active ?? true)" />
            </div>
        </x-card>

        <x-card title="Values">
            <x-slot:action>
                <x-btn variant="row" type="button" id="add-value">+ Add value</x-btn>
            </x-slot:action>
            <div id="values" class="space-y-2">
                @foreach (old('values', $attribute->values->map(fn ($v) => ['id' => $v->id, 'value' => $v->value])->all() ?? []) as $i => $row)
                    <div class="flex gap-2">
                        <input type="hidden" name="values[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                        <x-input name="values[{{ $i }}][value]" value="{{ $row['value'] ?? '' }}" placeholder="e.g. Large" class="flex-1" />
                        <x-btn variant="row-danger" type="button" onclick="this.parentElement.remove()">✕</x-btn>
                    </div>
                @endforeach
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $attribute->exists ? 'Save changes' : 'Create attribute' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.attributes.index') }}">Cancel</x-btn>
        </div>
    </form>

    <script>
        document.getElementById('add-value').addEventListener('click', () => {
            const list = document.getElementById('values');
            const i = list.children.length + Date.now();
            const row = document.createElement('div');
            row.className = 'flex gap-2';
            row.innerHTML = `<input type="hidden" name="values[${i}][id]" value="">
                <input name="values[${i}][value]" type="text" placeholder="e.g. Large" class="input flex-1">
                <button type="button" class="btn-danger-outline">✕</button>`;
            row.querySelector('button').addEventListener('click', () => row.remove());
            list.appendChild(row);
        });
    </script>
</x-admin-layout>
