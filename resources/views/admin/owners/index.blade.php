{{-- DDE-Mart Admin — owners list (original view, UI kit) --}}
<x-admin-layout title="Owners">
    <x-page-head title="Owners" sub="Store-owner accounts.">
        <x-slot:action>
            @if (auth()->user()->canAccess('owners', 'create'))
                <x-btn href="{{ route('admin.owners.create') }}"><x-icon name="plus" class="h-4 w-4" /> New owner</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.owners.index') }}" class="flex flex-wrap gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search name, phone, email…" class="min-w-52 flex-1" />
            <x-select name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (\App\Models\Owner::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Owner</th><th class="th">Contact</th><th class="th">Stores</th><th class="th">Status</th><th class="th text-right">Open</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($owners as $owner)
                    <tr>
                        <td class="td font-semibold">{{ $owner->name }}</td>
                        <td class="td text-xs text-slate-500">{{ $owner->phone }}<span class="block">{{ $owner->email }}</span></td>
                        <td class="td text-slate-500">{{ $owner->stores_count }}</td>
                        <td class="td">
                            <span @class([
                                'badge', 'badge-amber' => $owner->status === 'pending', 'badge-green' => $owner->status === 'active',
                                'badge-slate' => $owner->status === 'suspended', 'badge-red' => $owner->status === 'rejected',
                            ])>{{ $owner->status }}</span>
                        </td>
                        <td class="td text-right">
                            <x-btn variant="row" href="{{ route('admin.owners.show', $owner) }}">Open</x-btn>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="No owners yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $owners->links() }}</div>
</x-admin-layout>
