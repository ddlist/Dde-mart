{{-- DDE-Mart storefront — brand page (original view) --}}
<x-store-layout title="{{ $brand->name }}">
    <h1 class="mb-4 text-xl font-black tracking-tight">{{ $brand->name }}</h1>

    @if ($products->isEmpty())
        <x-empty message="Nothing here yet." />
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                @include('shop.partials.product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="mt-4">{{ $products->links() }}</div>
    @endif
</x-store-layout>
