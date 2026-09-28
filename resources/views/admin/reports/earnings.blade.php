{{-- DDE-Mart Admin — earnings report (original view, UI kit) --}}
<x-admin-layout title="Earnings">
    <x-page-head title="Earnings" sub="Vendor payouts after commission + driver delivery earnings, from completed orders." />

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.reports.earnings') }}" class="flex flex-wrap items-end gap-2">
            <x-field label="From" for="from">
                <x-input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" />
            </x-field>
            <x-field label="To" for="to">
                <x-input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" />
            </x-field>
            <x-field label="Vendor ID" for="vendor_id">
                <x-input id="vendor_id" name="vendor_id" type="number" min="1" value="{{ $filters['vendor_id'] ?? '' }}" class="w-28" />
            </x-field>
            <x-field label="Driver ID" for="driver_id">
                <x-input id="driver_id" name="driver_id" type="number" min="1" value="{{ $filters['driver_id'] ?? '' }}" class="w-28" />
            </x-field>
            <x-btn variant="dark">Apply</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.reports.earningsExport', request()->only(['from', 'to', 'vendor_id', 'driver_id'])) }}">Export CSV</x-btn>
        </form>
    </x-card>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="table-card">
            <h2 class="p-3 font-bold">Vendor payouts</h2>
            <table class="min-w-full">
                <thead class="thead">
                    <tr><th class="th">Vendor</th><th class="th text-right">Orders</th><th class="th text-right">Gross</th><th class="th text-right">Cut</th><th class="th text-right">Net</th></tr>
                </thead>
                <tbody class="tbody-row">
                    @forelse ($vendors as $row)
                        <tr>
                            <td class="td font-semibold">{{ $row['name'] }}</td>
                            <td class="td text-right">{{ $row['orders'] }}</td>
                            <td class="td text-right">{{ number_format($row['gross'], 2) }}</td>
                            <td class="td text-right text-slate-500">{{ number_format($row['cut'], 2) }}</td>
                            <td class="td text-right font-semibold">{{ number_format($row['net'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="td"><x-empty message="No completed orders." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-card">
            <h2 class="p-3 font-bold">Driver earnings</h2>
            <table class="min-w-full">
                <thead class="thead">
                    <tr><th class="th">Driver</th><th class="th text-right">Deliveries</th><th class="th text-right">Fees</th><th class="th text-right">Tips</th><th class="th text-right">Total</th></tr>
                </thead>
                <tbody class="tbody-row">
                    @forelse ($drivers as $row)
                        <tr>
                            <td class="td font-semibold">{{ $row['name'] }}</td>
                            <td class="td text-right">{{ $row['deliveries'] }}</td>
                            <td class="td text-right">{{ number_format($row['fees'], 2) }}</td>
                            <td class="td text-right text-slate-500">{{ number_format($row['tips'], 2) }}</td>
                            <td class="td text-right font-semibold">{{ number_format($row['total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="td"><x-empty message="No completed deliveries." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
