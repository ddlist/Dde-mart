{{-- DDE-Mart Admin — taxes list (original view, UI kit) --}}
<x-admin-layout title="Taxes">
    <x-page-head title="Taxes" sub="Percentage or fixed levies, optionally per section.">
        <x-slot:action>
            @if (auth()->user()->canAccess('finance', 'create'))
                <x-btn href="{{ route('admin.taxes.create') }}"><x-icon name="plus" class="h-4 w-4" /> New tax</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.taxes.index') }}" class="flex gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search taxes…" class="flex-1" />
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Title</th><th class="th">Country</th><th class="th">Value</th><th class="th">Section</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($taxes as $tax)
                    <tr>
                        <td class="td font-semibold">{{ $tax->title }}</td>
                        <td class="td text-slate-500">{{ $tax->country }}</td>
                        <td class="td">{{ $tax->type === 'percentage' ? $tax->value.'%' : $tax->value }}</td>
                        <td class="td text-xs text-slate-500">{{ $tax->section?->name ?? 'all' }}</td>
                        <td class="td"><x-status-pill :active="$tax->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('finance', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.taxes.edit', $tax) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('finance', 'delete'))
                                    <form method="POST" action="{{ route('admin.taxes.destroy', $tax) }}"
                                          onsubmit="return confirm('Delete tax {{ $tax->title }}?')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td"><x-empty message="No taxes yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $taxes->links() }}</div>
</x-admin-layout>
