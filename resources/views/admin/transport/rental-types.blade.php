{{-- DDE-Mart Admin — rental vehicle types (original view, UI kit) --}}
<x-admin-layout title="Vehicle Types">
    <x-page-head title="Vehicle Types" sub="Rental fleet categories.">
        <x-slot:action>
            @if (auth()->user()->canAccess('transport', 'create'))
                <x-btn href="{{ route('admin.rental-types.create') }}"><x-icon name="plus" class="h-4 w-4" /> New type</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Type</th><th class="th">Capacity</th><th class="th">Packages</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($types as $type)
                    <tr>
                        <td class="td">
                            <div class="flex items-center gap-3">
                                @if ($type->icon_path)
                                    <img src="{{ \App\Support\Images::url($type->icon_path) }}" alt="" class="h-10 w-10 rounded-xl object-cover">
                                @endif
                                <div>
                                    <p class="font-semibold">{{ $type->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $type->section?->name ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="td text-slate-500">{{ $type->capacity ?? '—' }}</td>
                        <td class="td text-slate-500">{{ $type->packages_count }}</td>
                        <td class="td"><x-status-pill :active="$type->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('transport', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.rental-types.edit', $type) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('transport', 'delete'))
                                    <form method="POST" action="{{ route('admin.rental-types.destroy', $type) }}"
                                          onsubmit="return confirm('Delete type? Blocked if packages exist.')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="No vehicle types." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $types->links() }}</div>
</x-admin-layout>
