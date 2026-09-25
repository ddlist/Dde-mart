{{-- DDE-Mart Admin — brands list (original view, UI kit) --}}
<x-admin-layout title="Brands">
    <x-page-head title="Brands" sub="Product brands, optionally per section.">
        <x-slot:action>
            @if (auth()->user()->canAccess('catalog', 'create'))
                <x-btn href="{{ route('admin.brands.create') }}"><x-icon name="plus" class="h-4 w-4" /> New brand</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.brands.index') }}" class="flex flex-wrap gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search brands…" class="min-w-52 flex-1" />
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
                <tr><th class="th">Brand</th><th class="th">Section</th><th class="th">Products</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($brands as $brand)
                    <tr>
                        <td class="td">
                            <div class="flex items-center gap-3">
                                @if ($brand->image_path)
                                    <img src="{{ \App\Support\Images::url($brand->image_path) }}" alt="" class="h-10 w-10 rounded-xl object-cover">
                                @endif
                                <p class="font-semibold">{{ $brand->name }}</p>
                            </div>
                        </td>
                        <td class="td text-slate-500">{{ $brand->section?->name ?? '—' }}</td>
                        <td class="td text-slate-500">{{ $brand->products_count }}</td>
                        <td class="td"><x-status-pill :active="$brand->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('catalog', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.brands.edit', $brand) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('catalog', 'delete'))
                                    <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}"
                                          onsubmit="return confirm('Delete brand {{ $brand->name }}? Blocked if products exist.')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="No brands yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $brands->links() }}</div>
</x-admin-layout>
