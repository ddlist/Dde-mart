{{-- DDE-Mart Admin — roles list (original view, UI kit) --}}
<x-admin-layout title="Roles">
    <x-page-head title="Roles" sub="{{ $roles->count() }} role(s). Deleting a role with users is blocked.">
        <x-slot:action>
            @if (auth()->user()->canAccess('roles', 'create'))
                <x-btn href="{{ route('admin.roles.create') }}"><x-icon name="plus" class="h-4 w-4" /> New role</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Role</th><th class="th">Users</th><th class="th">Abilities</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($roles as $role)
                    <tr>
                        <td class="td">
                            <span class="font-semibold">{{ $role->name }}</span>
                            @if ($role->is_super)
                                <span class="badge-ink ml-2">super</span>
                            @endif
                        </td>
                        <td class="td text-slate-500">{{ $role->users_count }}</td>
                        <td class="td text-slate-500">{{ $role->permissions_count }}</td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('roles', 'edit') && ! $role->is_super)
                                    <x-btn variant="row" href="{{ route('admin.roles.edit', $role) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('roles', 'delete') && ! $role->is_super)
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                                          onsubmit="return confirm('Delete role {{ $role->name }}? Blocked if users are attached.')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="td"><x-empty message="No roles yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
