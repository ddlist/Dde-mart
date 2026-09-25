{{-- DDE-Mart storefront — parcel tracking (original view) --}}
<x-store-layout title="Parcel {{ $order->number }}">
    <div class="mb-4">
        <a href="{{ route('shop.parcel.orders') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-800">
            <x-icon name="back" class="h-4 w-4" /> My parcels
        </a>
    </div>
    <x-card title="Parcel {{ $order->number }}" sub="{{ $order->statusLabel() }}" class="mx-auto max-w-xl">
        <p class="text-sm">{{ $order->sender_name }} → {{ $order->receiver_name }}</p>
        <p class="mt-1 text-lg font-black">{{ number_format($order->total, 2) }}</p>
        <ol class="mt-4 space-y-3">
            @foreach ($order->history as $entry)
                <li class="flex gap-3 text-sm">
                    <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500"></span>
                    <div>
                        <p class="font-semibold">{{ $statuses[$entry->to_status] ?? $entry->to_status }}</p>
                        <p class="text-xs text-slate-400">{{ $entry->created_at->format('d M Y, H:i') }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </x-card>
</x-store-layout>
