{{-- DDE-Mart Admin — sections list (original view, UI kit) --}}
<x-admin-layout title="Sections">
    <x-page-head title="Sections" sub="Business verticals (food, grocery…).">
        <x-slot:action>
            @if (auth()->user()->canAccess('catalog', 'create'))
                <x-btn href="{{ route('admin.sections.create') }}"><x-icon name="plus" class="h-4 w-4" /> New section</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.sections.index') }}" class="flex gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search sections…" class="flex-1" />
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Section</th><th class="th">Contents</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($sections as $section)
                    <tr>
                        <td class="td">
                            <div class="flex items-center gap-3">
                                @if ($section->image_path)
                                    <img src="{{ \App\Support\Images::url($section->image_path) }}" alt="" class="h-10 w-10 rounded-xl object-cover">
                                @else
                                    <span class="grid h-10 w-10 place-items-center rounded-xl font-black"
                                          style="background:{{ $section->color ?? '#e2e8f0' }}">{{ strtoupper(substr($section->name, 0, 1)) }}</span>
                                @endif
                                <div>
                                    <p class="font-semibold">{{ $section->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $section->service_type ?? 'no service type' }} · order {{ $section->sort_order }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="td text-xs text-slate-500">
                            {{ $section->categories_count }} cat · {{ $section->brands_count }} brands · {{ $section->products_count }} products · {{ $section->banners_count }} banners
                        </td>
                        <td class="td"><x-status-pill :active="$section->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('catalog', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.sections.edit', $section) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('catalog', 'delete'))
                                    <form method="POST" action="{{ route('admin.sections.destroy', $section) }}"
                                          onsubmit="return confirm('Delete section {{ $section->name }}? Blocked if contents exist.')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="td"><x-empty message="No sections yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $sections->links() }}</div>
</x-admin-layout>
