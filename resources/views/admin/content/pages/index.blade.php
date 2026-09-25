{{-- DDE-Mart Admin — CMS pages list (original view, UI kit) --}}
<x-admin-layout title="Pages">
    <x-page-head title="Pages" sub="Terms, privacy, about…">
        <x-slot:action>
            @if (auth()->user()->canAccess('content', 'create'))
                <x-btn href="{{ route('admin.pages.create') }}"><x-icon name="plus" class="h-4 w-4" /> New page</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Name</th><th class="th">Slug</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($pages as $page)
                    <tr>
                        <td class="td font-semibold">{{ $page->name }}</td>
                        <td class="td font-mono text-xs text-slate-500">/{{ $page->slug }}</td>
                        <td class="td"><x-status-pill :active="$page->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('content', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.pages.edit', $page) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('content', 'delete'))
                                    <form method="POST" action="{{ route('admin.pages.destroy', $page) }}"
                                          onsubmit="return confirm('Delete page {{ $page->name }}?')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="td"><x-empty message="No pages yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $pages->links() }}</div>
</x-admin-layout>
