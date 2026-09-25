{{-- DDE-Mart storefront — favorites (original view) --}}
<x-store-layout title="Favorites">
    <h1 class="mb-4 text-xl font-black tracking-tight">Favorites</h1>

    @if ($products->isEmpty() && $stores->isEmpty())
        <x-empty message="Nothing saved yet — tap ♥ on any product or store." />
    @else
        @if ($products->isNotEmpty())
            <h2 class="mb-2 text-sm font-bold uppercase tracking-wider text-slate-400">Products</h2>
            <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($products as $product)
                    @include('shop.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        @endif
        @if ($stores->isNotEmpty())
            <h2 class="mb-2 text-sm font-bold uppercase tracking-wider text-slate-400">Stores</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($stores as $store)
                    <a href="{{ route('shop.stores.show', $store->slug) }}" class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                        <p class="truncate text-sm font-bold">{{ $store->name }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    @endif
</x-store-layout>
