{{-- DDE-Mart Admin — email templates list (original view, UI kit) --}}
<x-admin-layout title="Email Templates">
    <x-page-head title="Email Templates" sub="Rendered server-side with :placeholders.">
        <x-slot:action>
            @if (auth()->user()->canAccess('content', 'create'))
                <x-btn href="{{ route('admin.emails.create') }}"><x-icon name="plus" class="h-4 w-4" /> New template</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Key</th><th class="th">Subject</th><th class="th">To admin</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($templates as $template)
                    <tr>
                        <td class="td font-mono text-xs">{{ $template->key }}</td>
                        <td class="td">{{ $template->subject }}</td>
                        <td class="td text-xs">{{ $template->send_to_admin ? 'yes' : 'no' }}</td>
                        <td class="td"><x-status-pill :active="$template->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('content', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.emails.edit', $template) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('content', 'delete'))
                                    <form method="POST" action="{{ route('admin.emails.destroy', $template) }}"
                                          onsubmit="return confirm('Delete template {{ $template->key }}?')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="No templates yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $templates->links() }}</div>
</x-admin-layout>
