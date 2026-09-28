{{-- DDE-Mart Admin — drivers list (original view, UI kit) --}}
<x-admin-layout title="Drivers">
    <x-page-head title="Drivers" sub="Riders, store deliverymen and fleet.">
        <x-slot:action>
            @if (auth()->user()->canAccess('drivers', 'create'))
                <x-btn href="{{ route('admin.drivers.create') }}"><x-icon name="plus" class="h-4 w-4" /> New driver</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.drivers.index') }}" class="flex flex-wrap gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search name or phone…" class="min-w-52 flex-1" />
            <x-select name="kind" onchange="this.form.submit()">
                <option value="">All kinds</option>
                @foreach (\App\Models\Driver::KINDS as $kind)
                    <option value="{{ $kind }}" @selected(request('kind') === $kind)>{{ ucfirst($kind) }}</option>
                @endforeach
            </x-select>
            <x-select name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (\App\Models\Driver::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-select>
            <x-select name="scope" onchange="this.form.submit()">
                <option value="">All drivers</option>
                <option value="fleet" @selected(request('scope') === 'fleet')>Fleet only</option>
                <option value="store" @selected(request('scope') === 'store')>Store riders only</option>
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Driver</th><th class="th">Kind</th><th class="th">Zone / Store</th><th class="th">Docs</th><th class="th">Status</th><th class="th text-right">Open</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($drivers as $driver)
                    <tr>
                        <td class="td">
                            <div class="flex items-center gap-3">
                                @if ($driver->photo_path)
                                    <img src="{{ \App\Support\Images::url($driver->photo_path) }}" alt="" class="h-10 w-10 rounded-full object-cover">
                                @else
                                    <span class="grid h-10 w-10 place-items-center rounded-full bg-slate-200 font-black text-slate-500">{{ strtoupper(substr($driver->name, 0, 1)) }}</span>
                                @endif
                                <div>
                                    <p class="font-semibold">
                                        <span class="mr-1 inline-block h-2 w-2 rounded-full {{ $driver->is_online ? 'bg-green-500' : 'bg-slate-300' }}" title="{{ $driver->is_online ? 'Online' : 'Offline' }}"></span>{{ $driver->name }}
                                    </p>
                                    <p class="text-xs text-slate-400">{{ $driver->phone }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="td text-xs text-slate-500">{{ $driver->kind }}</td>
                        <td class="td text-xs text-slate-500">{{ $driver->zone?->name ?? '—' }} / {{ $driver->store?->name ?? '—' }}</td>
                        <td class="td text-xs text-slate-500">{{ $driver->verifications_count ?? '—' }}</td>
                        <td class="td">
                            <span @class([
                                'badge', 'badge-amber' => $driver->status === 'pending', 'badge-green' => $driver->status === 'active',
                                'badge-slate' => $driver->status === 'suspended', 'badge-red' => $driver->status === 'rejected',
                            ])>{{ $driver->status }}</span>
                        </td>
                        <td class="td text-right">
                            <x-btn variant="row" href="{{ route('admin.drivers.show', $driver) }}">Open</x-btn>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td"><x-empty message="No drivers yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $drivers->links() }}</div>
</x-admin-layout>
