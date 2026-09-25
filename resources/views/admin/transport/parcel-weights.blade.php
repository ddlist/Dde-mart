{{-- DDE-Mart Admin — parcel weight slabs (original view, UI kit, inline editing) --}}
<x-admin-layout title="Parcel Weights">
    <x-page-head title="Weight Slabs" sub="Title + delivery charge per slab." />

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card title="Add slab">
            <form method="POST" action="{{ route('admin.parcel-weights.store') }}" class="space-y-3">
                @csrf
                <x-field label="Title" for="new-title">
                    <x-input id="new-title" name="title" required placeholder="e.g. 5 KG" />
                </x-field>
                <x-field label="Max kg (optional)" for="new-max">
                    <x-input id="new-max" name="max_kg" type="number" step="0.01" min="0" />
                </x-field>
                <x-field label="Delivery charge" for="new-charge">
                    <x-input id="new-charge" name="delivery_charge" type="number" step="0.01" min="0" required value="0" />
                </x-field>
                <x-btn>Create slab</x-btn>
            </form>
        </x-card>

        <div class="table-card lg:col-span-2">
            <table class="min-w-full">
                <thead class="thead">
                    <tr><th class="th">Title</th><th class="th">Max kg</th><th class="th">Charge</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
                </thead>
                <tbody class="tbody-row">
                @forelse ($weights as $weight)
                    <tr>
                        <td class="td"><x-input name="title" form="w-{{ $weight->id }}" required value="{{ $weight->title }}" class="!w-auto" /></td>
                        <td class="td"><x-input name="max_kg" form="w-{{ $weight->id }}" type="number" step="0.01" min="0" value="{{ $weight->max_kg }}" class="!w-24" /></td>
                        <td class="td"><x-input name="delivery_charge" form="w-{{ $weight->id }}" type="number" step="0.01" min="0" required value="{{ $weight->delivery_charge }}" class="!w-24" /></td>
                        <td class="td"><x-status-pill :active="$weight->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                <form id="w-{{ $weight->id }}" method="POST" action="{{ route('admin.parcel-weights.update', $weight) }}">
                                    @csrf @method('PUT')
                                    <x-btn variant="row">Save</x-btn>
                                </form>
                                @if (auth()->user()->canAccess('transport', 'delete'))
                                    <form method="POST" action="{{ route('admin.parcel-weights.destroy', $weight) }}"
                                          onsubmit="return confirm('Delete slab?')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="No weight slabs." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $weights->links() }}</div>
</x-admin-layout>
