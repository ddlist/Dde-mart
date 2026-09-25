{{-- DDE-Mart storefront — order detail (original view) --}}
<x-store-layout title="Order {{ $order->number }}">
    <div class="mb-4">
        <a href="{{ route('shop.orders') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-800">
            <x-icon name="back" class="h-4 w-4" /> My orders
        </a>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card title="Order {{ $order->number }}" class="lg:col-span-2">
            <p class="mb-3 text-xs font-bold uppercase text-slate-400">{{ $order->statusLabel() }} · {{ $order->payment_method }}</p>
            <table class="min-w-full text-sm">
                <tbody class="tbody-row">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="py-2">
                                <p class="font-semibold">{{ $item->name }}</p>
                                @foreach ($item->extras ?? [] as $extra)
                                    <p class="text-xs text-slate-400">+ {{ $extra['name'] }}</p>
                                @endforeach
                            </td>
                            <td class="py-2 text-right text-slate-500">× {{ $item->quantity }}</td>
                            <td class="py-2 text-right font-semibold">{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-3 flex justify-between border-t border-slate-100 pt-3 font-black">
                <span>Total paid</span><span>{{ number_format($order->total, 2) }}</span>
            </div>
            <form method="POST" action="{{ route('shop.orders.reorder', $order) }}" class="mt-4">
                @csrf
                <x-btn variant="ghost">Reorder these items</x-btn>
            </form>
        </x-card>

        <x-card title="Tracking">
            <ol class="space-y-3">
                @foreach ($order->history as $entry)
                    <li class="flex gap-3 text-sm">
                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500"></span>
                        <div>
                            <p class="font-semibold">{{ $entry->to_status }}</p>
                            <p class="text-xs text-slate-400">{{ $entry->created_at->format('d M Y, H:i') }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
            <button onclick="window.print()" class="no-print mt-4 rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Print receipt</button>
        </x-card>
    </div>
</x-store-layout>
