{{-- DDE-Mart Admin — languages list (original view, UI kit) --}}
<x-admin-layout title="Languages">
    <x-page-head title="Languages" sub="Exactly one default.">
        <x-slot:action>
            @if (auth()->user()->canAccess('content', 'create'))
                <x-btn href="{{ route('admin.languages.create') }}"><x-icon name="plus" class="h-4 w-4" /> New language</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Code</th><th class="th">Name</th><th class="th">Default</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($languages as $language)
                    <tr>
                        <td class="td font-mono font-bold">{{ $language->code }}</td>
                        <td class="td">{{ $language->name }}</td>
                        <td class="td">
                            @if ($language->is_default)
                                <span class="badge-ink">default</span>
                            @endif
                        </td>
                        <td class="td"><x-status-pill :active="$language->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('content', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.languages.edit', $language) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('content', 'delete') && ! $language->is_default)
                                    <form method="POST" action="{{ route('admin.languages.destroy', $language) }}"
                                          onsubmit="return confirm('Delete language {{ $language->name }}?')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="No languages yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $languages->links() }}</div>
</x-admin-layout>
