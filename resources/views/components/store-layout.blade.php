<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Shop' }} — {{ $shopSiteName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col bg-slate-50 text-slate-900 antialiased">
    {{-- Top strip --}}
    <div class="bg-slate-950 text-slate-200">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-2 px-4 py-1.5 text-xs">
            <a href="{{ route('shop.location') }}" class="inline-flex min-w-0 items-center gap-1 hover:text-white">
                <x-icon name="pin" class="h-4 w-4 shrink-0 text-emerald-400" />
                <span class="truncate">{{ session('shop.address', 'Set delivery location') }}</span>
            </a>
            <span class="hidden sm:inline">Free delivery over selected stores</span>
        </div>
    </div>

    {{-- Header --}}
    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
            <a href="{{ route('shop.home') }}" class="flex items-center gap-2">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-500 font-black text-slate-950">D</span>
                <span class="text-base font-black tracking-tight">{{ $shopSiteName }}</span>
            </a>
            <form method="GET" action="{{ route('shop.search') }}" class="hidden flex-1 md:block">
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input name="q" value="{{ request('q') }}" placeholder="Search products or stores…"
                        class="input !pl-9">
                </div>
            </form>
            <nav class="ml-auto flex items-center gap-1.5">
                <a href="{{ route('shop.cart') }}" class="relative rounded-xl border border-slate-200 p-2 text-slate-700 hover:bg-slate-50" title="Cart">
                    <x-icon name="cart" class="h-5 w-5" />
                    @if ($cartCount > 0)
                        <span class="absolute -right-1.5 -top-1.5 grid h-5 min-w-5 place-items-center rounded-full bg-emerald-600 px-1 text-[10px] font-bold text-white">{{ $cartCount }}</span>
                    @endif
                </a>
                @auth('customer')
                    <a href="{{ route('shop.profile') }}" class="rounded-xl border border-slate-200 p-2 text-slate-700 hover:bg-slate-50" title="{{ auth('customer')->user()->name }}">
                        <x-icon name="user" class="h-5 w-5" />
                    </a>
                @else
                    <a href="{{ route('shop.login') }}" class="rounded-xl bg-slate-900 px-3.5 py-2 text-sm font-bold text-white">Sign in</a>
                @endauth
            </nav>
        </div>
        <div class="border-t border-slate-100">
            <div class="mx-auto flex max-w-6xl items-center gap-1 overflow-x-auto px-4 py-2 text-sm">
                <form method="POST" action="{{ route('shop.section.store') }}" class="flex items-center gap-1">
                    @csrf
                    <button name="section_id" value="" class="rounded-lg px-3 py-1.5 font-semibold {{ ! session('shop.section_id') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">All</button>
                    @foreach ($navSections as $section)
                        <button name="section_id" value="{{ $section->id }}"
                            class="whitespace-nowrap rounded-lg px-3 py-1.5 font-semibold {{ (int) session('shop.section_id') === $section->id ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                            {{ $section->name }}
                        </button>
                    @endforeach
                </form>
                <span class="mx-1 h-5 w-px shrink-0 bg-slate-200"></span>
                <a href="{{ route('shop.parcel') }}" class="whitespace-nowrap rounded-lg px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-100">Parcel</a>
                <a href="{{ route('shop.rental') }}" class="whitespace-nowrap rounded-lg px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-100">Rental</a>
                <a href="{{ route('shop.services') }}" class="whitespace-nowrap rounded-lg px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-100">Services</a>
                <a href="{{ route('shop.dinein') }}" class="whitespace-nowrap rounded-lg px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-100">Dine-in</a>
                <a href="{{ route('shop.gifts') }}" class="whitespace-nowrap rounded-lg px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-100">Gifts</a>
                @auth('customer')
                    <a href="{{ route('shop.orders') }}" class="whitespace-nowrap rounded-lg px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-100">Orders</a>
                    <a href="{{ route('shop.favorites') }}" class="whitespace-nowrap rounded-lg px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-100">Favorites</a>
                @endauth
            </div>
        </div>
        <form method="GET" action="{{ route('shop.search') }}" class="px-4 pb-3 md:hidden">
            <input name="q" value="{{ request('q') }}" placeholder="Search products or stores…" class="input">
        </form>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6">
        @if (session('success'))
            <div class="alert-ok">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert-err">{{ session('error') }}</div>
        @endif
        {{ $slot }}
    </main>

    <footer class="mt-8 border-t border-slate-200 bg-white">
        <div class="mx-auto grid max-w-6xl gap-6 px-4 py-8 sm:grid-cols-3">
            <div>
                <p class="font-black">{{ $shopSiteName }}</p>
                <p class="mt-1 text-sm text-slate-500">Good food and groceries, delivered.</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Company</p>
                <ul class="mt-2 space-y-1 text-sm">
                    @foreach ($footerPages as $page)
                        <li><a href="{{ route('shop.pages.show', $page->slug) }}" class="text-slate-600 hover:text-slate-900">{{ $page->name }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Admin</p>
                <a href="{{ route('admin.dashboard') }}" class="mt-2 inline-block text-sm text-slate-600 hover:text-slate-900">Staff sign in →</a>
            </div>
        </div>
    </footer>
</body>
</html>
