{{-- DDE-Mart Admin — categories list (original view, UI kit) --}}
<x-admin-layout title="Categories">
    <x-page-head title="Categories" sub="Product taxonomy, optionally per section.">
        <x-slot:action>
            @if (auth()->user()->canAccess('catalog', 'create'))
                <x-btn href="{{ route('admin.categories.create') }}"><x-icon name="plus" class="h-4 w-4" /> New category</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.categories.index') }}" class="flex flex-wrap gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search categories…" class="min-w-52 flex-1" />
            <x-select name="section" onchange="this.form.submit()">
                <option value="">All sections</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected((string) request('section') === (string) $section->id)>{{ $section->name }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Category</th><th class="th">Section</th><th class="th">Products</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
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
                                    <p class="text-xs text-slate-400">order {{ $category->sort_order }}
                                        @if ($category->show_in_homepage) · homepage @endif</p>
                                </div>
                            </div>
                        </td>
                        <td class="td text-slate-500">{{ $category->section?->name ?? '—' }}</td>
                        <td class="td text-slate-500">{{ $category->products_count }}</td>
                        <td class="td"><x-status-pill :active="$category->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('catalog', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.categories.edit', $category) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('catalog', 'delete'))
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                          onsubmit="return confirm('Delete category {{ $category->name }}? Blocked if products exist.')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="No categories yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $categories->links() }}</div>
</x-admin-layout>
