{{-- DDE-Mart Admin — attributes list (original view, UI kit) --}}
<x-admin-layout title="Attributes">
    <x-page-head title="Attributes" sub="Variant axes (size, spice…) with inline values.">
        <x-slot:action>
            @if (auth()->user()->canAccess('catalog', 'create'))
                <x-btn href="{{ route('admin.attributes.create') }}"><x-icon name="plus" class="h-4 w-4" /> New attribute</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.attributes.index') }}" class="flex gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search attributes…" class="flex-1" />
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Attribute</th><th class="th">Values</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($attributes as $attribute)
                    <tr>
                        <td class="td font-semibold">{{ $attribute->name }}</td>
                        <td class="td text-slate-500">{{ $attribute->values_count }}</td>
                        <td class="td"><x-status-pill :active="$attribute->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('catalog', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.attributes.edit', $attribute) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('catalog', 'delete'))
                                    <form method="POST" action="{{ route('admin.attributes.destroy', $attribute) }}"
                                          onsubmit="return confirm('Delete attribute {{ $attribute->name }}? Blocked if values exist.')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="td"><x-empty message="No attributes yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $attributes->links() }}</div>
</x-admin-layout>
