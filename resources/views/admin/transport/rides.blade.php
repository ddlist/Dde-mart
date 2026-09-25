{{-- DDE-Mart Admin — rides list (original view, UI kit) --}}
<x-admin-layout title="Rides">
    <x-page-head title="Rides" sub="Cab trips with enforced transitions." />

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.rides.index') }}" class="flex flex-wrap gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search number, customer…" class="min-w-52 flex-1" />
            <x-select name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Ride</th><th class="th">Customer</th><th class="th">Driver</th><th class="th">Total</th><th class="th">Status</th><th class="th text-right">Open</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($rides as $ride)
                    <tr>
                        <td class="td font-semibold">{{ $ride->number ?? '#'.$ride->id }}</td>
                        <td class="td">{{ $ride->customer_name }}</td>
                        <td class="td text-xs text-slate-500">{{ $ride->driver?->name ?? 'unassigned' }}</td>
                        <td class="td font-semibold">{{ number_format($ride->total, 2) }}</td>
                        <td class="td">
                            <span @class([
                                'badge', 'badge-sky' => in_array($ride->status, ['placed', 'accepted']),
                                'badge-amber' => $ride->status === 'ongoing', 'badge-green' => $ride->status === 'completed',
                                'badge-red' => in_array($ride->status, ['cancelled', 'rejected']),
                            ])>{{ $ride->statusLabel() }}</span>
                        </td>
                        <td class="td text-right">
                            <x-btn variant="row" href="{{ route('admin.rides.show', $ride) }}">View</x-btn>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td"><x-empty message="No rides." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $rides->links() }}</div>
</x-admin-layout>
