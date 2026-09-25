{{-- DDE-Mart storefront — search results (original view) --}}
<x-store-layout title="Search{{ $q !== '' ? ': '.$q : '' }}">
    <h1 class="mb-4 text-xl font-black tracking-tight">
        {{ $q !== '' ? "Results for “{$q}”" : 'Search the shop' }}
    </h1>

    @if ($stores->isNotEmpty())
        <h2 class="mb-2 text-sm font-bold uppercase tracking-wider text-slate-400">Stores</h2>
        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($stores as $store)
                <a href="{{ route('shop.stores.show', $store->slug) }}" class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                    <p class="truncate text-sm font-bold">{{ $store->name }}</p>
                    <p class="text-xs {{ $store->is_open ? 'text-emerald-600' : 'text-slate-400' }}">{{ $store->is_open ? 'Open' : 'Closed' }}</p>
                </a>
            @endforeach
        </div>
    @endif

    <h2 class="mb-2 text-sm font-bold uppercase tracking-wider text-slate-400">Products</h2>
    @if ($products->isEmpty())
        <x-empty message="Nothing found. Try another search." />
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                @include('shop.partials.product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="mt-4">{{ $products->links() }}</div>
    @endif
</x-store-layout>
