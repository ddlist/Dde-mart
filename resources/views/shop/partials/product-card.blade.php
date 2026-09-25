{{-- DDE-Mart storefront — product card (original partial) --}}
@props(['product'])

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
    <a href="{{ route('shop.products.show', $product->slug) }}" class="block">
        @if ($product->image_path)
            <img src="{{ \App\Support\Images::url($product->image_path) }}" alt="{{ $product->name }}" class="h-40 w-full object-cover" loading="lazy">
        @else
            <div class="grid h-40 w-full place-items-center bg-slate-100 text-2xl font-black text-slate-300">
                {{ strtoupper(substr($product->name, 0, 1)) }}
            </div>
        @endif
        <div class="p-3">
            <p class="truncate text-sm font-bold">{{ $product->name }}</p>
            <p class="mt-0.5 text-xs text-slate-400">{{ $product->category?->name ?? '' }}</p>
            <div class="mt-1.5 flex items-center justify-between">
                <p class="text-sm font-black">
                    {{ number_format($product->sellingPrice(), 2) }}
                    @if ($product->discount_price !== null && $product->discount_price < $product->price)
                        <span class="font-normal text-xs text-slate-400 line-through">{{ number_format($product->price, 2) }}</span>
                    @endif
                </p>
                @if (! $product->veg)
                    <span class="badge-red">non-veg</span>
                @endif
            </div>
        </div>
    </a>
</div>
