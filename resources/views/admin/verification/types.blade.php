{{-- DDE-Mart Admin — document types (original view, UI kit) --}}
<x-admin-layout title="Document Types">
    <x-page-head title="Document Types" sub="Which papers each directory must submit.">
        <x-slot:action>
            @if (auth()->user()->canAccess('drivers', 'create'))
                <x-btn href="{{ route('admin.doc-types.create') }}"><x-icon name="plus" class="h-4 w-4" /> New type</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Title</th><th class="th">Applies to</th><th class="th">Sides</th><th class="th">In queue</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($types as $type)
                    <tr>
                        <td class="td font-semibold">{{ $type->title }}</td>
                        <td class="td text-xs text-slate-500">{{ $type->owner_type }}</td>
                        <td class="td text-xs text-slate-500">
                            {{ $type->front_required ? 'front' : '' }}{{ $type->front_required && $type->back_required ? ' + ' : '' }}{{ $type->back_required ? 'back' : '' }}
                        </td>
                        <td class="td text-slate-500">{{ $type->verifications_count }}</td>
                        <td class="td"><x-status-pill :active="$type->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('drivers', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.doc-types.edit', $type) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('drivers', 'delete'))
                                    <form method="POST" action="{{ route('admin.doc-types.destroy', $type) }}"
                                          onsubmit="return confirm('Delete type {{ $type->title }}? Blocked if history exists.')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td"><x-empty message="No document types yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $types->links() }}</div>
</x-admin-layout>
