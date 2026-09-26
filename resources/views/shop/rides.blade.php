{{-- DDE-Mart storefront — my rides (original view) --}}
<x-store-layout title="My Rides">
    <h1 class="mb-4 text-xl font-black tracking-tight">My rides</h1>
    <div class="grid max-w-3xl gap-3">
        @forelse ($rides as $ride)
            <a href="{{ route('shop.ride.track', $ride) }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:border-emerald-300">
                <div class="flex items-center justify-between gap-2">
                    <p class="font-black">{{ $ride->number }}</p>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600">{{ $ride->statusLabel() }}</span>
                </div>
                <p class="mt-1 text-sm text-slate-600">{{ $ride->source }} → {{ $ride->destination }}</p>
                <p class="mt-1 text-sm font-bold">{{ number_format($ride->total, 2) }}</p>
            </a>
        @empty
            <x-empty message="No rides yet." />
        @endforelse
        <div>{{ $rides->links() }}</div>
    </div>
</x-store-layout>
