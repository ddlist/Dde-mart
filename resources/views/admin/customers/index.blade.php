{{-- DDE-Mart Admin — customers list (original view, UI kit) --}}
<x-admin-layout title="Customers">
    <x-page-head title="Customers" sub="App end-users. Staff accounts live under Users.">
        <x-slot:action>
            @if (auth()->user()->canAccess('users', 'create'))
                <x-btn href="{{ route('admin.customers.create') }}"><x-icon name="plus" class="h-4 w-4" /> New customer</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.customers.index') }}" class="flex flex-wrap gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search name, email or phone…" class="min-w-52 flex-1" />
            <x-select name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Name</th><th class="th">Contact</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($customers as $customer)
                    <tr>
                        <td class="td"><span class="font-semibold">{{ $customer->name }}</span></td>
                        <td class="td text-slate-500">{{ $customer->phone }}@if ($customer->email)<br>{{ $customer->email }}@endif</td>
                        <td class="td">
                            @if ($customer->is_active)
                                <span class="badge-green">active</span>
                            @else
                                <span class="badge-slate">inactive</span>
                            @endif
                        </td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                <x-btn variant="row" href="{{ route('admin.customers.show', $customer) }}">View</x-btn>
                                @if (auth()->user()->canAccess('users', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.customers.edit', $customer) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('users', 'delete'))
                                    <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}"
                                          onsubmit="return confirm('Delete customer {{ $customer->name }}?')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="td"><x-empty message="No customers found." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $customers->links() }}</div>
</x-admin-layout>
