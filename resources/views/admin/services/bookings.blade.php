{{-- DDE-Mart Admin — service bookings (original view, UI kit) --}}
<x-admin-layout title="Bookings">
    <x-page-head title="Bookings" sub="On-demand jobs with enforced transitions." />

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.provider-bookings.index') }}" class="flex flex-wrap gap-2">
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
                <tr><th class="th">Booking</th><th class="th">Customer</th><th class="th">Provider</th><th class="th">Scheduled</th><th class="th">Total</th><th class="th">Status</th><th class="th text-right">Open</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($bookings as $booking)
                    <tr>
                        <td class="td font-semibold">{{ $booking->number ?? '#'.$booking->id }}</td>
                        <td class="td">{{ $booking->customer_name }}</td>
                        <td class="td text-xs text-slate-500">{{ $booking->provider?->name ?? '—' }}</td>
                        <td class="td text-xs text-slate-500">{{ $booking->scheduled_at?->format('d M H:i') ?? '—' }}</td>
                        <td class="td font-semibold">{{ number_format($booking->total, 2) }}</td>
                        <td class="td">
                            <span @class([
                                'badge', 'badge-sky' => in_array($booking->status, ['placed', 'accepted']),
                                'badge-amber' => $booking->status === 'ongoing', 'badge-green' => $booking->status === 'completed',
                                'badge-red' => in_array($booking->status, ['cancelled', 'rejected']),
                            ])>{{ $booking->statusLabel() }}</span>
                        </td>
                        <td class="td text-right">
                            <x-btn variant="row" href="{{ route('admin.provider-bookings.show', $booking) }}">View</x-btn>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="td"><x-empty message="No bookings." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $bookings->links() }}</div>
</x-admin-layout>
