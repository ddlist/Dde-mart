{{-- DDE-Mart Admin — staff users list (original view, UI kit) --}}
<x-admin-layout title="Users">
    <x-page-head title="Staff users" sub="Accounts with panel access.">
        <x-slot:action>
            @if (auth()->user()->canAccess('users', 'create'))
                <x-btn href="{{ route('admin.users.create') }}"><x-icon name="plus" class="h-4 w-4" /> New user</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search name or email…" class="min-w-52 flex-1" />
            <x-select name="role" onchange="this.form.submit()">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected((string) request('role') === (string) $role->id)>{{ $role->name }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Name</th><th class="th">Email</th><th class="th">Role</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($users as $staff)
                    <tr>
                        <td class="td">
                            <span class="font-semibold">{{ $staff->name }}</span>
                            @if ($staff->is(auth()->user()))
                                <span class="badge-green ml-2">you</span>
                            @endif
                        </td>
                        <td class="td text-slate-500">{{ $staff->email }}</td>
                        <td class="td"><span class="badge-slate">{{ $staff->role?->name ?? 'No role' }}</span></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('users', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.users.edit', $staff) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('users', 'delete') && ! $staff->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.users.destroy', $staff) }}"
                                          onsubmit="return confirm('Delete user {{ $staff->name }}?')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="td"><x-empty message="No users found." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</x-admin-layout>
