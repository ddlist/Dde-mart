{{-- DDE-Mart storefront — home (original view) --}}
<x-store-layout title="Home">
    @if ($banners->isNotEmpty())
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($banners->take(2) as $banner)
                <div class="relative overflow-hidden rounded-2xl">
                    @if ($banner->image_path)
                        <img src="{{ \App\Support\Images::url($banner->image_path) }}" alt="{{ $banner->title }}" class="h-44 w-full object-cover sm:h-56">
                    @endif
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/70 to-transparent p-4">
                        <p class="font-black text-white">{{ $banner->title }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($sections->isNotEmpty())
        <h2 class="mb-3 mt-8 text-lg font-black tracking-tight">Shop by section</h2>
        <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            @foreach ($sections as $section)
                <form method="POST" action="{{ route('shop.section.store') }}">
                    @csrf
                    <input type="hidden" name="section_id" value="{{ $section->id }}">
                    <button class="w-full rounded-2xl border border-slate-200 bg-white p-3 text-center shadow-sm transition hover:shadow-md">
                        @if ($section->image_path)
                            <img src="{{ \App\Support\Images::url($section->image_path) }}" alt="" class="mx-auto h-14 w-14 rounded-xl object-cover">
                        @else
                            <span class="mx-auto grid h-14 w-14 place-items-center rounded-xl font-black"
                                  style="background:{{ $section->color ?? '#e2e8f0' }}">{{ strtoupper(substr($section->name, 0, 1)) }}</span>
                        @endif
                        <p class="mt-2 truncate text-xs font-bold">{{ $section->name }}</p>
                    </button>
                </form>
            @endforeach
        </div>
    @endif

    @if ($promos->isNotEmpty())
        <h2 class="mb-3 mt-8 text-lg font-black tracking-tight">Offers for you</h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($promos as $promo)
                <div class="rounded-2xl border border-dashed border-emerald-300 bg-emerald-50 p-4">
                    <p class="font-mono text-lg font-black text-emerald-800">{{ $promo->code }}</p>
                    <p class="mt-0.5 text-xs text-emerald-700">{{ $promo->description ?? ($promo->discount_type === 'percentage' ? $promo->discount_value.'% off' : $promo->discount_value.' off') }}</p>
                </div>
            @endforeach
        </div>
    @endif

    @if ($stores->isNotEmpty())
        <div class="mb-3 mt-8 flex items-center justify-between">
            <h2 class="text-lg font-black tracking-tight">Popular stores</h2>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ($stores as $store)
                <a href="{{ route('shop.stores.show', $store->slug) }}" class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm transition hover:shadow-md">
                    @if ($store->image_path)
                        <img src="{{ \App\Support\Images::url($store->image_path) }}" alt="" class="h-20 w-full rounded-xl object-cover">
                    @endif
                    <p class="mt-2 truncate text-sm font-bold">{{ $store->name }}</p>
                    <p class="text-xs {{ $store->is_open ? 'text-emerald-600' : 'text-slate-400' }}">
                        {{ $store->is_open ? 'Open' : 'Closed' }}
                    </p>
                </a>
            @endforeach
        </div>
    @endif

    @if ($products->isNotEmpty())
        <h2 class="mb-3 mt-8 text-lg font-black tracking-tight">Fresh picks</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                @include('shop.partials.product-card', ['product' => $product])
            @endforeach
        </div>
    @endif

    @if ($sections->isEmpty() && $stores->isEmpty() && $products->isEmpty())
        <x-empty message="The shop is being stocked — check back soon." />
    @endif
</x-store-layout>
