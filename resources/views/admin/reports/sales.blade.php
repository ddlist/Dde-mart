{{-- DDE-Mart Admin — sales report (original view, UI kit) --}}
<x-admin-layout title="Sales Report">
    <x-page-head title="Sales Report" sub="Server-computed from orders." />

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.reports.sales') }}" class="flex flex-wrap items-end gap-2">
            <x-field label="From" for="from">
                <x-input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" />
            </x-field>
            <x-field label="To" for="to">
                <x-input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" />
            </x-field>
            <x-field label="Status" for="status">
                <x-select id="status" name="status">
                    <option value="">All</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </x-field>
            <x-btn variant="dark">Apply</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.reports.salesExport', request()->only(['from', 'to', 'status'])) }}">Export CSV</x-btn>
        </form>
    </x-card>

    <div class="mb-4 grid gap-4 sm:grid-cols-3 xl:grid-cols-6">
        @foreach (['orders' => 'Orders', 'gross' => 'Gross', 'discount' => 'Discount', 'delivery' => 'Delivery', 'tax' => 'Tax', 'net' => 'Net'] as $key => $label)
            <x-card>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-xl font-black">{{ $key === 'orders' ? $summary[$key] : number_format($summary[$key], 2) }}</p>
            </x-card>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="table-card lg:col-span-2">
            <table class="min-w-full">
                <thead class="thead">
                    <tr><th class="th">Order</th><th class="th">Date</th><th class="th">Customer</th><th class="th">Status</th><th class="th text-right">Total</th></tr>
                </thead>
                <tbody class="tbody-row">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="td font-semibold">
                                @if (auth()->user()->canAccess('orders'))
                                    <a href="{{ route('admin.orders.show', $order) }}" class="text-emerald-700">{{ $order->number }}</a>
                                @else
                                    {{ $order->number }}
                                @endif
                            </td>
                            <td class="td text-xs text-slate-500">{{ $order->created_at->format('d M Y') }}</td>
                            <td class="td">{{ $order->customer_name }}</td>
                            <td class="td text-xs uppercase text-slate-500">{{ $order->statusLabel() }}</td>
                            <td class="td text-right font-semibold">{{ number_format($order->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="td"><x-empty message="No orders match." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-card title="Daily net">
            @if (empty($daily))
                <p class="text-sm text-slate-400">No data.</p>
            @else
                <ul class="space-y-1.5 text-sm">
                    @foreach ($daily as $row)
                        <li class="flex justify-between">
                            <span class="text-slate-500">{{ $row['day'] }} <span class="text-xs">({{ $row['orders'] }})</span></span>
                            <span class="font-semibold">{{ number_format($row['net'], 2) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</x-admin-layout>
