{{-- DDE-Mart storefront — product detail (original view) --}}
<x-store-layout title="{{ $product->name }}">
    <div class="grid gap-6 lg:grid-cols-2">
        <div>
            @if ($product->image_path)
                <img src="{{ \App\Support\Images::url($product->image_path) }}" alt="{{ $product->name }}"
                     class="aspect-square w-full rounded-2xl border border-slate-200 object-cover">
            @else
                <div class="grid aspect-square w-full place-items-center rounded-2xl bg-slate-100 text-6xl font-black text-slate-300">
                    {{ strtoupper(substr($product->name, 0, 1)) }}
                </div>
            @endif
        </div>

        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">
                {{ $product->category?->name ?? '' }}
            </p>
            <h1 class="mt-1 text-2xl font-black tracking-tight">{{ $product->name }}</h1>
            @if ($product->store)
                <p class="mt-1 text-sm text-slate-500">
                    Sold by <a href="{{ route('shop.stores.show', $product->store->slug) }}" class="font-semibold text-emerald-700">{{ $product->store->name }}</a>
                </p>
            @endif
            <p class="mt-3 text-3xl font-black">
                {{ number_format($product->sellingPrice(), 2) }}
                @if ($product->discount_price !== null && $product->discount_price < $product->price)
                    <span class="text-base font-normal text-slate-400 line-through">{{ number_format($product->price, 2) }}</span>
                @endif
            </p>

            @if ($product->description)
                <p class="mt-3 text-sm text-slate-600">{{ $product->description }}</p>
            @endif

            <form method="POST" action="{{ route('shop.cart.add') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                @if ($product->attributeValues->isNotEmpty())
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="mb-2 text-sm font-bold">Options</p>
                        <div class="flex flex-wrap gap-2 text-sm text-slate-600">
                            @foreach ($product->attributeValues->groupBy('attribute_id') as $values)
                                <span class="font-semibold">{{ $values->first()->attribute?->name }}:</span>
                                <span>{{ $values->pluck('value')->join(', ') }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($product->addons->isNotEmpty())
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="mb-2 text-sm font-bold">Add-ons</p>
                        <div class="space-y-2">
                            @foreach ($product->addons as $addon)
                                <x-check name="addons[]" :value="$addon->id"
                                    :label="$addon->name.' (+'.number_format($addon->price, 2).')'" />
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex items-center gap-2">
                    <x-input name="quantity" type="number" min="1" max="99" value="1" class="!w-24" />
                    <x-btn class="flex-1">Add to cart</x-btn>
                </div>
            </form>

            <p class="mt-3 text-xs text-slate-400">
                {{ $product->quantity > 0 ? "In stock ({$product->quantity})" : 'Out of stock' }}
                · {{ $product->veg ? 'Veg' : 'Non-veg' }}
            </p>

            @auth('customer')
                <form method="POST" action="{{ route('shop.favorites.toggle') }}" class="mt-2">
                    @csrf
                    <input type="hidden" name="type" value="product">
                    <input type="hidden" name="id" value="{{ $product->id }}">
                    <x-btn variant="ghost">♥ Save to favorites</x-btn>
                </form>
            @endauth
        </div>
    </div>

    @if ($reviews->isNotEmpty())
        <h2 class="mb-3 mt-8 text-lg font-black tracking-tight">Reviews</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($reviews as $review)
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-sm font-bold">{{ $review->author_name }} <span class="text-amber-500">{{ $review->rating }}★</span></p>
                    <p class="mt-1 text-sm text-slate-600">{{ $review->comment }}</p>
                </div>
            @endforeach
        </div>
    @endif

    @if ($related->isNotEmpty())
        <h2 class="mb-3 mt-8 text-lg font-black tracking-tight">You may also like</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($related as $item)
                @include('shop.partials.product-card', ['product' => $item])
            @endforeach
        </div>
    @endif
</x-store-layout>
