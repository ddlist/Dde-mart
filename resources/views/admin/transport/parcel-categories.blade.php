{{-- DDE-Mart Admin — parcel categories (original view, UI kit) --}}
<x-admin-layout title="Parcel Categories">
    <x-page-head title="Parcel Categories" sub="What can be shipped.">
        <x-slot:action>
            @if (auth()->user()->canAccess('transport', 'create'))
                <x-btn href="{{ route('admin.parcel-categories.create') }}"><x-icon name="plus" class="h-4 w-4" /> New category</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Category</th><th class="th">Section</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($categories as $category)
                    <tr>
                        <td class="td">
                            <div class="flex items-center gap-3">
                                @if ($category->image_path)
                                    <img src="{{ \App\Support\Images::url($category->image_path) }}" alt="" class="h-10 w-10 rounded-xl object-cover">
                                @endif
                                <div>
                                    <p class="font-semibold">{{ $category->name }}</p>
                                    <p class="text-xs text-slate-400">order {{ $category->sort_order }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="td text-slate-500">{{ $category->section?->name ?? '—' }}</td>
                        <td class="td"><x-status-pill :active="$category->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('transport', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.parcel-categories.edit', $category) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('transport', 'delete'))
                                    <form method="POST" action="{{ route('admin.parcel-categories.destroy', $category) }}"
                                          onsubmit="return confirm('Delete parcel category?')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="td"><x-empty message="No parcel categories." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $categories->links() }}</div>
</x-admin-layout>
