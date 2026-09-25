{{-- DDE-Mart Admin — rental order detail (original view, UI kit) --}}
<x-admin-layout title="Rental {{ $order->number }}">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('admin.rental-orders.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-800">
            <x-icon name="back" class="h-4 w-4" /> All rentals
        </a>
        <button onclick="window.print()" class="no-print rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Print</button>
        <span class="badge-sky">{{ $order->statusLabel() }}</span>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-card title="Trip">
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-slate-400">Customer</dt><dd class="font-semibold">{{ $order->customer_name }} · {{ $order->customer_phone }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Driver</dt><dd class="font-semibold">{{ $order->driver?->name ?? 'unassigned' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Package</dt><dd>{{ $order->package?->name ?? '—' }} ({{ $order->vehicleType?->name ?? '—' }})</dd></div>
                    <div><dt class="text-xs text-slate-400">Route</dt><dd>{{ $order->source ?? '—' }} → {{ $order->destination ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Booked / Start / End</dt><dd>{{ $order->booking_at?->format('d M H:i') ?? '—' }} / {{ $order->started_at?->format('d M H:i') ?? '—' }} / {{ $order->ended_at?->format('d M H:i') ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Money ({{ $order->payment_method }})</dt><dd class="text-lg font-black">{{ number_format($order->total, 2) }}</dd></div>
                </dl>
                @if ($order->notes)
                    <p class="alert-warn mt-3 !mb-0">Note: {{ $order->notes }}</p>
                @endif
            </x-card>

            <x-card title="Timeline">
                @if ($order->history->isEmpty())
                    <p class="text-sm text-slate-400">No transitions yet.</p>
                @else
                    <ol class="space-y-3">
                        @foreach ($order->history as $entry)
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

        <div>
            @if (auth()->user()->canAccess('transport', 'edit'))
                <x-card title="Move rental">
                    @if (empty($allowed))
                        <p class="text-sm text-slate-400">Terminal state.</p>
                    @else
                        <form method="POST" action="{{ route('admin.rental-orders.transition', $order) }}" class="space-y-3">
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
