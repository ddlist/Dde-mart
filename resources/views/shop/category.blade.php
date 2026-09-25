{{-- DDE-Mart storefront — category page (original view) --}}
<x-store-layout title="{{ $category->name }}">
    <div class="mb-4 flex items-center gap-3">
        @if ($category->image_path)
            <img src="{{ \App\Support\Images::url($category->image_path) }}" alt="" class="h-14 w-14 rounded-2xl object-cover">
        @endif
        <div>
            <h1 class="text-xl font-black tracking-tight">{{ $category->name }}</h1>
            <p class="text-sm text-slate-500">{{ $category->section?->name ?? '' }}</p>
        </div>
    </div>

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
