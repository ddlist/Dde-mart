{{-- DDE-Mart storefront — store page (original view) --}}
<x-store-layout title="{{ $store->name }}">
    <div class="mb-4 flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        @if ($store->image_path)
            <img src="{{ \App\Support\Images::url($store->image_path) }}" alt="" class="h-16 w-16 rounded-2xl object-cover">
        @endif
        <div class="flex-1">
            <h1 class="text-xl font-black tracking-tight">{{ $store->name }}</h1>
            <p class="text-sm text-slate-500">{{ $store->address }}</p>
        </div>
        <span @class(['badge', 'badge-green' => $store->is_open, 'badge-slate' => ! $store->is_open])>
            {{ $store->is_open ? 'Open' : 'Closed' }}
        </span>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        @auth('customer')
            <form method="POST" action="{{ route('shop.favorites.toggle') }}">
                @csrf
                <input type="hidden" name="type" value="store">
                <input type="hidden" name="id" value="{{ $store->id }}">
                <x-btn variant="ghost">♥ Save store</x-btn>
            </form>
        @endauth
        <x-btn variant="ghost" href="{{ route('shop.dinein') }}">Book a table</x-btn>
    </div>

    @if ($products->isEmpty())
        <x-empty message="This store has no products yet." />
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                @include('shop.partials.product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="mt-4">{{ $products->links() }}</div>
    @endif
</x-store-layout>
