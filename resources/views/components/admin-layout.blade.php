@php
use App\Models\Setting;

$siteName = Setting::get('site_name', 'DDE-Mart');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} — {{ $siteName }} Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 text-slate-900 antialiased">
@php
$nav = [
    ['admin.dashboard', 'Dashboard', 'grid', null, 'admin.dashboard'],
    ['admin.roles.index', 'Roles', 'shield', 'roles', 'admin.roles.*'],
    ['admin.users.index', 'Staff', 'users', 'users', 'admin.users.*'],
    ['admin.orders.index', 'Orders', 'cart', 'orders', 'admin.orders.*'],
    ['admin.reviews.index', 'Reviews', 'star', 'orders', 'admin.reviews.*'],
    ['admin.stores.index', 'Stores', 'store', 'stores', 'admin.stores.*'],
    ['admin.drivers.index', 'Drivers', 'user', 'drivers', 'admin.drivers.*'],
    ['admin.verifications.queue', 'Verification', 'check', 'drivers', 'admin.verifications.*'],
    ['admin.owners.index', 'Owners', 'users', 'owners', 'admin.owners.*'],
    ['admin.doc-types.index', 'Doc Types', 'doc', 'drivers', 'admin.doc-types.*'],
];
$groups = [
    'Transport' => [
        ['admin.parcel-orders.index', 'Parcel Orders', 'cart', 'transport', 'admin.parcel-orders.*'],
        ['admin.parcel-categories.index', 'Parcel Cats', 'tag', 'transport', 'admin.parcel-categories.*'],
        ['admin.parcel-weights.index', 'Parcel Weights', 'doc', 'transport', 'admin.parcel-weights.*'],
        ['admin.rental-orders.index', 'Rental Orders', 'clock', 'transport', 'admin.rental-orders.*'],
        ['admin.rental-packages.index', 'Rental Packages', 'cube', 'transport', 'admin.rental-packages.*'],
        ['admin.rental-types.index', 'Vehicle Types', 'store', 'transport', 'admin.rental-types.*'],
        ['admin.rides.index', 'Rides', 'pin', 'transport', 'admin.rides.*'],
        ['admin.fleet.index', 'Fleet', 'cog', 'transport', 'admin.fleet.*'],
        ['admin.provider-bookings.index', 'Bookings', 'ticket', 'transport', 'admin.provider-bookings.*'],
        ['admin.providers.index', 'Providers', 'users', 'transport', 'admin.providers.*'],
        ['admin.provider-categories.index', 'Service Cats', 'tag', 'transport', 'admin.provider-categories.*'],
        ['admin.provider-services.index', 'Services', 'star', 'transport', 'admin.provider-services.*'],
        ['admin.provider-workers.index', 'Workers', 'user', 'transport', 'admin.provider-workers.*'],
        ['admin.dinein.index', 'Dine-in', 'grid', 'transport', 'admin.dinein.*'],
    ],
    'Catalog' => [
        ['admin.sections.index', 'Sections', 'grid', 'catalog', 'admin.sections.*'],
        ['admin.categories.index', 'Categories', 'tag', 'catalog', 'admin.categories.*'],
        ['admin.brands.index', 'Brands', 'star', 'catalog', 'admin.brands.*'],
        ['admin.products.index', 'Products', 'cube', 'catalog', 'admin.products.*'],
        ['admin.attributes.index', 'Attributes', 'doc', 'catalog', 'admin.attributes.*'],
        ['admin.banners.index', 'Banners', 'eye', 'catalog', 'admin.banners.*'],
    ],
    'Promotions' => [
        ['admin.coupons.index', 'Coupons', 'ticket', 'promotions', 'admin.coupons.*'],
        ['admin.ads.index', 'Advertisements', 'bell', 'promotions', 'admin.ads.*'],
        ['admin.gifts.index', 'Gift Cards', 'card', 'promotions', 'admin.gifts.*'],
    ],
    'Finance' => [
        ['admin.taxes.index', 'Taxes', 'doc', 'finance', 'admin.taxes.*'],
        ['admin.currencies.index', 'Currencies', 'card', 'finance', 'admin.currencies.*'],
        ['admin.plans.index', 'Plans', 'star', 'finance', 'admin.plans.*'],
        ['admin.payouts.index', 'Payouts', 'wallet', 'finance', 'admin.payouts.*'],
        ['admin.disbursements.index', 'Disbursements', 'wallet', 'finance', 'admin.disbursements.*'],
        ['admin.subscriptions.index', 'Subscriptions', 'card', 'finance', 'admin.subscriptions.*'],
        ['admin.gift-orders.index', 'Gift Orders', 'ticket', 'finance', 'admin.gift-orders.*'],
        ['admin.wallet.index', 'Wallet', 'card', 'finance', 'admin.wallet.*'],
        ['admin.referrals.index', 'Referrals', 'ticket', 'finance', 'admin.referrals.*'],
        ['admin.reports.sales', 'Reports', 'chart', 'reports', 'admin.reports.*'],
    ],
    'Content' => [
        ['admin.zones.index', 'Zones', 'pin', 'content', 'admin.zones.*'],
        ['admin.notifications.index', 'Notifications', 'bell', 'content', 'admin.notifications.*'],
        ['admin.push.index', 'Push Templates', 'mail', 'content', 'admin.push.*'],
        ['admin.emails.index', 'Email Templates', 'mail', 'content', 'admin.emails.*'],
        ['admin.pages.index', 'Pages', 'doc', 'content', 'admin.pages.*'],
        ['admin.stories.index', 'Stories', 'eye', 'content', 'admin.stories.*'],
        ['admin.presets.index', 'Filters', 'tag', 'content', 'admin.presets.*'],
        ['admin.complaints.index', 'Complaints', 'bell', 'content', 'admin.complaints.*'],
        ['admin.sos.index', 'SOS', 'pin', 'content', 'admin.sos.*'],
        ['admin.chats.index', 'Chat', 'mail', 'content', 'admin.chats.*'],
        ['admin.slides.index', 'Onboarding', 'star', 'content', 'admin.slides.*'],
        ['admin.blocks.index', 'Blocks', 'grid', 'content', 'admin.blocks.*'],
        ['admin.scheduled.index', 'Scheduled', 'clock', 'content', 'admin.scheduled.*'],
        ['admin.email.compose', 'Send Email', 'mail', 'content', 'admin.email.*'],
        ['admin.ops.map', 'Live Map', 'globe', 'content', 'admin.ops.map'],
        ['admin.ops.maintenance', 'Maintenance', 'cog', 'content', 'admin.ops.maintenance'],
        ['admin.languages.index', 'Languages', 'globe', 'content', 'admin.languages.*'],
    ],
];
$user = auth()->user();
@endphp

<div class="min-h-full lg:flex">
    {{-- Mobile backdrop + drawer use vanilla JS (no dependency) --}}
    <div id="nav-backdrop" class="fixed inset-0 z-30 hidden bg-slate-950/60 lg:hidden"></div>

    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 hidden w-72 shrink-0 flex-col bg-slate-950 text-slate-200 lg:static lg:flex lg:w-64">
        <div class="flex h-16 items-center gap-2.5 border-b border-white/10 px-5">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-500 font-black text-slate-950">D</span>
            <div class="flex-1">
                <p class="text-sm font-bold tracking-wide">{{ $siteName }}</p>
                <p class="text-xs text-slate-400">Admin Panel</p>
            </div>
            <button id="nav-close" class="rounded-lg p-1.5 text-slate-400 hover:bg-white/10 lg:hidden" aria-label="Close menu">
                <x-icon name="x" class="h-5 w-5" />
            </button>
        </div>

        <nav class="flex-1 space-y-0.5 overflow-y-auto p-3">
            @foreach ($nav as [$routeName, $label, $icon, $gate, $pattern])
                @if (! $gate || $user?->canAccess($gate))
                    <a href="{{ route($routeName) }}"
                       class="{{ request()->routeIs($pattern) ? 'nav-link-active' : 'nav-link-idle' }}">
                        <x-icon :name="$icon" class="h-5 w-5 shrink-0 opacity-80" />
                        {{ $label }}
                    </a>
                @endif
            @endforeach

            @foreach ($groups as $group => $items)
                @php $visible = collect($items)->filter(fn ($i) => $user?->canAccess($i[3]))->values(); @endphp
                @if ($visible->isNotEmpty() || $group === 'Content' && $user?->canAccess('content'))
                    <p class="nav-group">{{ $group }}</p>
                    @foreach ($visible as [$routeName, $label, $icon, $gate, $pattern])
                        <a href="{{ route($routeName) }}"
                           class="{{ request()->routeIs($pattern) ? 'nav-link-active' : 'nav-link-idle' }}">
                            <x-icon :name="$icon" class="h-5 w-5 shrink-0 opacity-80" />
                            {{ $label }}
                        </a>
                    @endforeach
                    @if ($group === 'Content' && $user?->canAccess('content'))
                        <a href="{{ route('admin.settings.edit') }}"
                           class="{{ request()->routeIs('admin.settings.*') ? 'nav-link-active' : 'nav-link-idle' }}">
                            <x-icon name="cog" class="h-5 w-5 shrink-0 opacity-80" />
                            Settings
                        </a>
                    @endif
                @endif
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="flex items-center gap-2.5">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-emerald-500/20 text-xs font-bold text-emerald-300">
                    {{ strtoupper(substr($user?->name ?? 'A', 0, 1)) }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold">{{ $user?->name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ $user?->role?->name ?? 'No role' }}</p>
                </div>
            </div>
        </div>
    </aside>

    {{-- Main column --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-slate-200 bg-white/85 px-4 backdrop-blur sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <button id="nav-open" class="rounded-xl border border-slate-200 p-2 text-slate-600 hover:bg-slate-50 lg:hidden" aria-label="Open menu">
                    <x-icon name="menu" class="h-5 w-5" />
                </button>
                <div class="min-w-0">
                    <h1 class="truncate text-base font-black tracking-tight">{{ $title ?? 'Dashboard' }}</h1>
                    <p class="hidden text-xs text-slate-500 sm:block">DDE-Mart control center</p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-sm">
                <a href="{{ route('admin.profile.edit') }}" title="My profile"
                   class="hidden rounded-xl border border-slate-200 p-2 text-slate-600 hover:bg-slate-50 sm:block">
                    <x-icon name="user" class="h-5 w-5" />
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button title="Sign out" class="rounded-xl border border-slate-200 p-2 text-slate-600 hover:bg-red-50 hover:text-red-700">
                        <x-icon name="out" class="h-5 w-5" />
                    </button>
                </form>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 p-4 sm:p-6">
            @if (session('success'))
                <div class="alert-ok">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert-err">{{ session('error') }}</div>
            @endif
            {{ $slot }}
        </main>

        <footer class="border-t border-slate-200 px-6 py-3 text-center text-xs text-slate-400">
            {{ $siteName }} Admin · clean rebuild
        </footer>
    </div>
</div>

<script>
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('nav-backdrop');
    const open = () => { sidebar.classList.remove('hidden'); sidebar.classList.add('flex'); backdrop.classList.remove('hidden'); };
    const close = () => { if (window.innerWidth < 1024) { sidebar.classList.add('hidden'); sidebar.classList.remove('flex'); backdrop.classList.add('hidden'); } };
    document.getElementById('nav-open')?.addEventListener('click', open);
    document.getElementById('nav-close')?.addEventListener('click', close);
    backdrop?.addEventListener('click', close);
</script>
</body>
</html>
