{{-- DDE-Mart storefront — landing page (original view, reskin) --}}
<x-store-layout title="Welcome">
    {{-- Hero --}}
    <div class="overflow-hidden rounded-3xl bg-slate-950 text-white">
        <div class="grid gap-6 p-8 sm:grid-cols-2 sm:p-12">
            <div class="flex flex-col justify-center">
                <p class="text-xs font-bold uppercase tracking-widest text-emerald-400">Food · Grocery · Rides · Services</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-5xl">Everything your city delivers.</h1>
                <p class="mt-3 text-slate-300">Order food, book rides, hire trusted pros — one account, one wallet, one app.</p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <x-btn href="{{ route('shop.home') }}">Start shopping</x-btn>
                    <x-btn href="{{ route('shop.register') }}">Create account</x-btn>
                </div>
                <div class="mt-6 flex gap-6 text-sm">
                    <div><p class="text-xl font-black">{{ $stats['stores'] }}+</p><p class="text-slate-400">Stores</p></div>
                    <div><p class="text-xl font-black">{{ $stats['products'] }}+</p><p class="text-slate-400">Products</p></div>
                    <div><p class="text-xl font-black">{{ $stats['drivers'] }}+</p><p class="text-slate-400">Riders</p></div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                @foreach ($sections->take(4) as $section)
                    <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <p class="font-bold">{{ $section->name }}</p>
                        <p class="text-xs text-slate-300">{{ $section->service_type ?? 'delivery' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Verticals --}}
    <h2 class="mb-3 mt-10 text-lg font-black tracking-tight">One platform, every need</h2>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Food delivery', 'Restaurants and groceries at your door.', 'shop.home'],
            ['Send parcels', 'Same-day courier pickup.', 'shop.parcel'],
            ['Rent rides', 'Cars with drivers, by the hour.', 'shop.rental'],
            ['Home services', 'Cleaners, fixers and pros.', 'shop.services'],
        ] as [$title, $sub, $route])
            <a href="{{ route($route) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                <p class="font-bold">{{ $title }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $sub }}</p>
            </a>
        @endforeach
    </div>

    {{-- How it works --}}
    <h2 class="mb-3 mt-10 text-lg font-black tracking-tight">How it works</h2>
    <div class="grid gap-3 sm:grid-cols-3">
        @foreach (['Pick what you need' => 'Browse stores, services and rides.', 'Pay your way' => 'Cash, wallet or card — secured checkout.', 'Track live' => 'Timelines on every order, ride and booking.'] as $title => $sub)
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <p class="font-bold">{{ $title }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $sub }}</p>
            </div>
        @endforeach
    </div>

    {{-- CMS blocks --}}
    @if ($blocks->isNotEmpty())
        <div class="mt-10 grid gap-3 sm:grid-cols-2">
            @foreach ($blocks as $block)
                <div class="rounded-2xl bg-slate-950 p-6 text-white">
                    <p class="font-bold">{{ $block->title }}</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-slate-300">{{ $block->body }}</p>
                </div>
            @endforeach
        </div>
    @endif
</x-store-layout>
