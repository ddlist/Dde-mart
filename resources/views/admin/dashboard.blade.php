{{-- DDE-Mart Admin dashboard (original view, UI kit) --}}
<x-admin-layout title="Dashboard">
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['all' => 'All', 'food' => 'Food', 'parcel' => 'Parcel', 'rental' => 'Rental', 'ride' => 'Rides', 'service' => 'Services'] as $key => $label)
            <a href="{{ route('admin.dashboard', ['vertical' => $key]) }}"
               class="rounded-full px-3 py-1 text-xs font-bold {{ $vertical === $key ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            <x-stat-card :label="$stat['label']" :value="$stat['value']" :hint="$stat['hint']" />
        @endforeach
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <x-card title="Recent orders" class="lg:col-span-2">
            @if (auth()->user()->canAccess('orders'))
                <a href="{{ route('admin.orders.index') }}" class="mb-3 inline-block text-xs font-semibold text-emerald-700">View all →</a>
            @endif
            @if ($recentOrders->isEmpty())
                <x-empty message="No orders yet." />
            @else
                <table class="min-w-full text-sm">
                    <tbody class="tbody-row">
                        @foreach ($recentOrders as $order)
                            <tr>
                                <td class="py-2 font-semibold">{{ $order->number ?? '#'.$order->id }}</td>
                                <td class="py-2 text-slate-500">{{ $order->customer_name ?? $order->sender_name ?? '—' }}</td>
                                <td class="py-2 text-right font-semibold">{{ number_format($order->total, 2) }}</td>
                                <td class="py-2 text-right text-xs uppercase text-slate-400">{{ $order->statusLabel() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <h2 class="card-title mb-2 mt-5">Orders by status</h2>
            @if (empty($statusBreakdown))
                <p class="text-sm text-slate-400">Nothing to break down yet.</p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($statusBreakdown as $status => $count)
                        <span class="badge-slate">{{ $status }}: {{ $count }}</span>
                    @endforeach
                </div>
            @endif

            <h2 class="card-title mb-2 mt-5">Sales · last 14 days</h2>
            <div class="flex h-28 items-end gap-1">
                @foreach ($sales as $day)
                    <div class="flex-1 rounded-t bg-emerald-500/80" style="height: {{ round($day['total'] / $salesMax * 100) }}%" title="{{ $day['label'] }}: {{ number_format($day['total'], 2) }}"></div>
                @endforeach
            </div>
            <div class="mt-1 flex justify-between text-[11px] text-slate-400">
                <span>{{ $sales[0]['label'] ?? '' }}</span>
                <span>{{ $sales[13]['label'] ?? '' }}</span>
            </div>

            @if ($topStores->isNotEmpty())
                <h2 class="card-title mb-2 mt-5">Top stores</h2>
                <table class="min-w-full text-sm">
                    <tbody class="tbody-row">
                        @foreach ($topStores as $row)
                            <tr>
                                <td class="py-2 font-semibold">{{ $row->name }}</td>
                                <td class="py-2 text-slate-500">{{ $row->orders }} orders</td>
                                <td class="py-2 text-right font-semibold">{{ number_format($row->revenue, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-card>

        <div class="space-y-4">
            <x-card title="Pending payouts">
                <p class="mt-2 text-3xl font-black">{{ number_format($pendingPayouts['amount'], 2) }}</p>
                <p class="hint">{{ $pendingPayouts['count'] }} request(s) awaiting review</p>
                @if (auth()->user()->canAccess('finance'))
                    <a href="{{ route('admin.payouts.index', ['status' => 'pending']) }}" class="mt-2 inline-block text-xs font-semibold text-emerald-700">Review →</a>
                @endif
            </x-card>
            <x-card title="Low stock (≤ 5)">
                @if ($lowStock->isEmpty())
                    <p class="mt-2 text-sm text-slate-400">All stocked up.</p>
                @else
                    <ul class="mt-2 space-y-1.5 text-sm">
                        @foreach ($lowStock as $product)
                            <li class="flex justify-between">
                                <span>{{ $product->name }}</span>
                                <span class="font-bold {{ $product->quantity === 0 ? 'text-red-600' : 'text-amber-600' }}">{{ $product->quantity }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </div>
</x-admin-layout>
