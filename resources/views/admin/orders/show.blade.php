{{-- DDE-Mart Admin — order detail (original view, UI kit) --}}
<x-admin-layout title="Order {{ $order->number }}">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-800">
            <x-icon name="back" class="h-4 w-4" /> All orders
        </a>
        <button onclick="window.print()" class="no-print rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Print</button>
        <span @class([
            'badge', 'badge-sky' => in_array($order->status, ['placed', 'accepted']),
            'badge-amber' => in_array($order->status, ['preparing', 'shipped']),
            'badge-green' => $order->status === 'completed',
            'badge-red' => in_array($order->status, ['cancelled', 'rejected']),
        ])>{{ $order->statusLabel() }}</span>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-card title="Items">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wider text-slate-400">
                        <tr><th class="py-2">Item</th><th class="py-2 text-right">Price</th><th class="py-2 text-right">Qty</th><th class="py-2 text-right">Total</th></tr>
                    </thead>
                    <tbody class="tbody-row">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="py-2">
                                    <p class="font-semibold">{{ $item->name }}</p>
                                    @foreach ($item->extras ?? [] as $extra)
                                        <p class="text-xs text-slate-400">+ {{ $extra['name'] }} ({{ number_format($extra['price'] ?? 0, 2) }})</p>
                                    @endforeach
                                </td>
                                <td class="py-2 text-right text-slate-500">{{ number_format($item->price, 2) }}</td>
                                <td class="py-2 text-right text-slate-500">{{ $item->quantity }}</td>
                                <td class="py-2 text-right font-semibold">{{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <dl class="mt-4 space-y-1 border-t border-slate-100 pt-3 text-sm">
                    <div class="flex justify-between text-slate-500"><dt>Subtotal</dt><dd>{{ number_format($order->subtotal, 2) }}</dd></div>
                    <div class="flex justify-between text-slate-500"><dt>Discount @if($order->coupon_code) ({{ $order->coupon_code }}) @endif</dt><dd>−{{ number_format($order->discount, 2) }}</dd></div>
                    <div class="flex justify-between text-slate-500"><dt>Delivery</dt><dd>{{ number_format($order->delivery_charge, 2) }}</dd></div>
                    <div class="flex justify-between text-slate-500"><dt>Tax</dt><dd>{{ number_format($order->tax, 2) }}</dd></div>
                    <div class="flex justify-between text-slate-500"><dt>Tip</dt><dd>{{ number_format($order->tip, 2) }}</dd></div>
                    <div class="flex justify-between text-base font-black"><dt>Total ({{ $order->payment_method }})</dt><dd>{{ number_format($order->total, 2) }}</dd></div>
                </dl>
            </x-card>

            <x-card title="Timeline">
                @if ($order->history->isEmpty())
                    <p class="text-sm text-slate-400">No transitions recorded yet.</p>
                @else
                    <ol class="space-y-3">
                        @foreach ($order->history as $entry)
                            <li class="flex gap-3 text-sm">
                                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500"></span>
                                <div>
                                    <p class="font-semibold">{{ $entry->from_status ? ($statuses[$entry->from_status] ?? $entry->from_status).' → ' : '' }}{{ $statuses[$entry->to_status] ?? $entry->to_status }}</p>
                                    <p class="text-xs text-slate-400">
                                        {{ $entry->created_at->format('d M Y, H:i') }}
                                        @if ($entry->changedBy) · by {{ $entry->changedBy->name }} @endif
                                        @if ($entry->note) · “{{ $entry->note }}” @endif
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-card>
        </div>

        <div class="space-y-4">
            <x-card title="Customer">
                <p class="font-semibold">{{ $order->customer_name }}</p>
                <p class="text-sm text-slate-500">{{ $order->customer_email }}</p>
                <p class="text-sm text-slate-500">{{ $order->customer_phone }}</p>
                @if ($order->address)
                    <p class="mt-2 text-sm text-slate-600">{{ $order->address['address'] ?? '' }}, {{ $order->address['locality'] ?? '' }}</p>
                @endif
                @if ($order->notes)
                    <p class="alert-warn mt-2 !mb-0">Note: {{ $order->notes }}</p>
                @endif
                @if ($order->scheduled_at)
                    <p class="mt-2 text-xs text-slate-500">Scheduled: {{ $order->scheduled_at->format('d M Y, H:i') }}</p>
                @endif
            </x-card>

            @if (auth()->user()->canAccess('orders', 'edit'))
                <x-card title="Move order">
                    @if (empty($allowed))
                        <p class="text-sm text-slate-400">Terminal state — no further moves.</p>
                    @else
                        <form method="POST" action="{{ route('admin.orders.transition', $order) }}" class="space-y-3">
                            @csrf
                            <x-field label="Next status" for="to">
                                <x-select id="to" name="to">
                                    @foreach ($allowed as $next)
                                        <option value="{{ $next }}">{{ $statuses[$next] }}</option>
                                    @endforeach
                                </x-select>
                            </x-field>
                            <x-field label="Note (optional)" for="note">
                                <x-input id="note" name="note" maxlength="500" />
                            </x-field>
                            <x-btn class="w-full">Confirm move</x-btn>
                        </form>
                    @endif
                </x-card>
            @endif
        </div>
    </div>
</x-admin-layout>
