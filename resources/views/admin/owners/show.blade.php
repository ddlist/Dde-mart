{{-- DDE-Mart Admin — owner detail (original view, UI kit) --}}
<x-admin-layout title="{{ $owner->name }}">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('admin.owners.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-800">
            <x-icon name="back" class="h-4 w-4" /> All owners
        </a>
        <span @class([
            'badge', 'badge-amber' => $owner->status === 'pending', 'badge-green' => $owner->status === 'active',
            'badge-slate' => $owner->status === 'suspended', 'badge-red' => $owner->status === 'rejected',
        ])>{{ $owner->status }}</span>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Stores" :value="$owner->stores->count()" hint="linked storefronts" />
        <x-stat-card label="Fleet drivers" :value="$drivers->count()" hint="owner-linked riders" />
        <x-stat-card label="Store orders" :value="$orderCount" hint="all time" />
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-card title="Profile">
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-slate-400">Phone</dt><dd class="font-semibold">{{ $owner->phone ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Email</dt><dd class="font-semibold">{{ $owner->email ?? '—' }}</dd></div>
                </dl>
                @if (auth()->user()->canAccess('owners', 'edit'))
                    <div class="mt-3 flex gap-2">
                        <x-btn variant="row" href="{{ route('admin.owners.edit', $owner) }}">Edit</x-btn>
                        <form method="POST" action="{{ route('admin.owners.destroy', $owner) }}"
                              onsubmit="return confirm('Delete owner {{ $owner->name }}? Blocked if stores link.')">
                            @csrf @method('DELETE')
                            <x-btn variant="row-danger">Delete</x-btn>
                        </form>
                    </div>
                @endif
            </x-card>

            <x-card title="Bank details">
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-slate-400">Bank</dt><dd>{{ $owner->bank_name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Branch</dt><dd>{{ $owner->bank_branch ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Holder</dt><dd>{{ $owner->bank_holder ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Account</dt><dd>{{ $owner->bank_account ?? '—' }}</dd></div>
                </dl>
                @if ($owner->bank_other)
                    <p class="mt-2 text-sm text-slate-500">{{ $owner->bank_other }}</p>
                @endif
            </x-card>

            <x-card title="Fleet drivers" sub="{{ $drivers->count() }} linked">
                @if ($drivers->isEmpty())
                    <x-empty message="No fleet drivers linked." />
                @else
                    <ul class="space-y-2 text-sm">
                        @foreach ($drivers as $driver)
                            <li class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2">
                                <span class="font-semibold">{{ $driver->name }}</span>
                                <x-btn variant="row" href="{{ route('admin.drivers.show', $driver) }}">Open</x-btn>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card title="Stores" sub="{{ $owner->stores->count() }} linked">
                @if ($owner->stores->isEmpty())
                    <x-empty message="No stores linked yet." />
                @else
                    <ul class="space-y-2 text-sm">
                        @foreach ($owner->stores as $store)
                            <li class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2">
                                <span class="font-semibold">{{ $store->name }}</span>
                                <x-btn variant="row" href="{{ route('admin.stores.edit', $store) }}">Manage</x-btn>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>

        <div>
            @if (auth()->user()->canAccess('owners', 'edit'))
                <x-card title="Move owner">
                    @php $allowed = \App\Models\Owner::TRANSITIONS[$owner->status] ?? []; @endphp
                    @if (empty($allowed))
                        <p class="text-sm text-slate-400">Terminal state.</p>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($allowed as $next)
                                <form method="POST" action="{{ route('admin.owners.transition', $owner) }}">
                                    @csrf
                                    <input type="hidden" name="to" value="{{ $next }}">
                                    <x-btn variant="dark">Move to {{ $next }}</x-btn>
                                </form>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            @endif
        </div>
    </div>
</x-admin-layout>
