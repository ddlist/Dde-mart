{{-- DDE-Mart Admin — rental orders (original view, UI kit) --}}
<x-admin-layout title="Rental Orders">
    <x-page-head title="Rental Orders" sub="Timed hires with odometer-verified billing later." />

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.rental-orders.index') }}" class="flex flex-wrap gap-2">
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
                <tr><th class="th">Order</th><th class="th">Customer</th><th class="th">Package</th><th class="th">Total</th><th class="th">Status</th><th class="th text-right">Open</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($orders as $order)
                    <tr>
                        <td class="td font-semibold">{{ $order->number ?? '#'.$order->id }}</td>
                        <td class="td">{{ $order->customer_name }}</td>
                        <td class="td text-xs text-slate-500">{{ $order->package?->name ?? '—' }}</td>
                        <td class="td font-semibold">{{ number_format($order->total, 2) }}</td>
                        <td class="td">
                            <span @class([
                                'badge', 'badge-sky' => in_array($order->status, ['placed', 'accepted']),
                                'badge-amber' => $order->status === 'ongoing', 'badge-green' => $order->status === 'completed',
                                'badge-red' => in_array($order->status, ['cancelled', 'rejected']),
                            ])>{{ $order->statusLabel() }}</span>
                        </td>
                        <td class="td text-right">
                            <x-btn variant="row" href="{{ route('admin.rental-orders.show', $order) }}">View</x-btn>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td"><x-empty message="No rental orders." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</x-admin-layout>
