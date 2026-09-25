{{-- DDE-Mart Admin — provider categories, nested (original view, UI kit) --}}
<x-admin-layout title="Service Categories">
    <x-page-head title="Service Categories" sub="Two levels: parents with subcategories." />

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card title="Add category">
            <form method="POST" action="{{ route('admin.provider-categories.store') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <x-field label="Title" for="pc-title">
                    <x-input id="pc-title" name="title" required />
                </x-field>
                <x-field label="Parent (blank = top level)" for="pc-parent">
                    <x-select id="pc-parent" name="parent_id">
                        <option value="">— Top level —</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->title }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Section" for="pc-section">
                    <x-select id="pc-section" name="section_id">
                        <option value="">—</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Image" for="pc-image">
                    <input id="pc-image" name="image" type="file" accept="image/*" class="file">
                </x-field>
                <x-btn>Add category</x-btn>
            </form>
        </x-card>

        <div class="table-card lg:col-span-2">
            <table class="min-w-full">
                <thead class="thead">
                    <tr><th class="th">Category</th><th class="th">Level</th><th class="th">Children</th><th class="th text-right">Actions</th></tr>
                </thead>
                <tbody class="tbody-row">
                    @forelse ($categories as $category)
                        <tr>
                            <td class="td font-semibold">{{ $category->title }}</td>
                            <td class="td text-xs text-slate-500">top</td>
                            <td class="td text-xs text-slate-500">{{ $category->children->pluck('title')->join(', ') ?: '—' }}</td>
                            <td class="td text-right">
                                @if (auth()->user()->canAccess('transport', 'delete'))
                                    <form method="POST" action="{{ route('admin.provider-categories.destroy', $category) }}"
                                          onsubmit="return confirm('Delete category? Blocked with children.')" class="inline">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @foreach ($category->children as $child)
                            <tr>
                                <td class="td pl-8 text-slate-600">↳ {{ $child->title }}</td>
                                <td class="td text-xs text-slate-500">sub</td>
                                <td class="td">—</td>
                                <td class="td text-right">
                                    @if (auth()->user()->canAccess('transport', 'delete'))
                                        <form method="POST" action="{{ route('admin.provider-categories.destroy', $child) }}"
                                              onsubmit="return confirm('Delete category?')" class="inline">
                                            @csrf @method('DELETE')
                                            <x-btn variant="row-danger">Delete</x-btn>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="4" class="td"><x-empty message="No categories." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
