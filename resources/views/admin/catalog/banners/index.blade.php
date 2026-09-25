{{-- DDE-Mart Admin — banners list (original view, UI kit) --}}
<x-admin-layout title="Banners">
    <x-page-head title="Banners" sub="Homepage and section promos.">
        <x-slot:action>
            @if (auth()->user()->canAccess('catalog', 'create'))
                <x-btn href="{{ route('admin.banners.create') }}"><x-icon name="plus" class="h-4 w-4" /> New banner</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.banners.index') }}" class="flex flex-wrap gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search banners…" class="min-w-52 flex-1" />
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
                <tr><th class="th">Banner</th><th class="th">Target</th><th class="th">Position</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($banners as $banner)
                    <tr>
                        <td class="td">
                            <div class="flex items-center gap-3">
                                @if ($banner->image_path)
                                    <img src="{{ \App\Support\Images::url($banner->image_path) }}" alt="" class="h-10 w-16 rounded-lg object-cover">
                                @endif
                                <div>
                                    <p class="font-semibold">{{ $banner->title }}</p>
                                    <p class="text-xs text-slate-400">{{ $banner->section?->name ?? 'all sections' }} · order {{ $banner->sort_order }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="td text-xs text-slate-500">{{ $banner->redirect_type }}@if ($banner->redirect_target): {{ $banner->redirect_target }}@endif</td>
                        <td class="td text-xs text-slate-500">{{ $banner->position }}</td>
                        <td class="td"><x-status-pill :active="$banner->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('catalog', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.banners.edit', $banner) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('catalog', 'delete'))
                                    <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}"
                                          onsubmit="return confirm('Delete banner {{ $banner->title }}?')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="No banners yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $banners->links() }}</div>
</x-admin-layout>
