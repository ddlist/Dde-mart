{{-- DDE-Mart storefront — my rentals (original view) --}}
<x-store-layout title="My Rentals">
    <h1 class="mb-4 text-xl font-black tracking-tight">My rentals</h1>
    @if ($orders->isEmpty())
        <x-empty message="No rentals yet." />
    @else
        <div class="space-y-3">
            @foreach ($orders as $order)
                <a href="{{ route('shop.rental.track', $order) }}"
                   class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div>
                        <p class="font-bold">{{ $order->number }}</p>
                        <p class="text-xs text-slate-400">{{ $order->package?->name ?? '' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-black">{{ number_format($order->total, 2) }}</p>
                        <p class="text-xs uppercase text-slate-400">{{ $order->statusLabel() }}</p>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-4">{{ $orders->links() }}</div>
    @endif
</x-store-layout>
