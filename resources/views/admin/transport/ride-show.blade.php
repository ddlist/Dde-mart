{{-- DDE-Mart Admin — ride detail (original view, UI kit) --}}
<x-admin-layout title="Ride {{ $ride->number }}">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('admin.rides.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-800">
            <x-icon name="back" class="h-4 w-4" /> All rides
        </a>
        <button onclick="window.print()" class="no-print rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Print</button>
        <span class="badge-sky">{{ $ride->statusLabel() }}</span>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-card title="Trip">
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-slate-400">Customer</dt><dd class="font-semibold">{{ $ride->customer_name }} · {{ $ride->customer_phone }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Cab</dt><dd>{{ $ride->cabType?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Route</dt><dd>{{ $ride->source ?? '—' }} → {{ $ride->destination ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Distance</dt><dd>{{ $ride->distance_km ?? '—' }} km</dd></div>
                    <div><dt class="text-xs text-slate-400">Times</dt><dd>{{ $ride->booking_at?->format('d M H:i') ?? '—' }} / {{ $ride->started_at?->format('d M H:i') ?? '—' }} / {{ $ride->ended_at?->format('d M H:i') ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Money ({{ $ride->payment_method }})</dt><dd class="text-lg font-black">{{ number_format($ride->total, 2) }}</dd></div>
                </dl>
                @if ($ride->notes)
                    <p class="alert-warn mt-3 !mb-0">Note: {{ $ride->notes }}</p>
                @endif
            </x-card>

            <x-card title="Timeline">
                @if ($ride->history->isEmpty())
                    <p class="text-sm text-slate-400">No transitions yet.</p>
                @else
                    <ol class="space-y-3">
                        @foreach ($ride->history as $entry)
                            <li class="flex gap-3 text-sm">
                                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500"></span>
                                <div>
                                    <p class="font-semibold">{{ $entry->from_status ? ($statuses[$entry->from_status] ?? $entry->from_status).' → ' : '' }}{{ $statuses[$entry->to_status] ?? $entry->to_status }}</p>
                                    <p class="text-xs text-slate-400">{{ $entry->created_at->format('d M Y, H:i') }}
                                        @if ($entry->changedBy) · by {{ $entry->changedBy->name }} @endif
                                        @if ($entry->note) · “{{ $entry->note }}” @endif</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-card>
        </div>

        <div class="space-y-4">
            <x-card title="Driver">
                @if (auth()->user()->canAccess('transport', 'edit'))
                    <form method="POST" action="{{ route('admin.rides.assign', $ride) }}" class="flex gap-2">
                        @csrf
                        <x-select name="driver_id" class="flex-1">
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}" @selected($ride->driver_id === $driver->id)>{{ $driver->name }}</option>
                            @endforeach
                        </x-select>
                        <x-btn variant="dark">Assign</x-btn>
                    </form>
                @else
                    <p class="text-sm">{{ $ride->driver?->name ?? 'unassigned' }}</p>
                @endif
            </x-card>

            @if (auth()->user()->canAccess('transport', 'edit'))
                <x-card title="Move ride">
                    @if (empty($allowed))
                        <p class="text-sm text-slate-400">Terminal state.</p>
                    @else
                        <form method="POST" action="{{ route('admin.rides.transition', $ride) }}" class="space-y-3">
                            @csrf
                            <x-select name="to">
                                @foreach ($allowed as $next)
                                    <option value="{{ $next }}">{{ $statuses[$next] }}</option>
                                @endforeach
                            </x-select>
                            <x-input name="note" maxlength="500" placeholder="Note (optional)" />
                            <x-btn class="w-full">Confirm move</x-btn>
                        </form>
                    @endif
                </x-card>
            @endif
        </div>
    </div>
</x-admin-layout>
