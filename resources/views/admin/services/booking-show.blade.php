{{-- DDE-Mart Admin — booking detail (original view, UI kit) --}}
<x-admin-layout title="Booking {{ $booking->number }}">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('admin.provider-bookings.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-800">
            <x-icon name="back" class="h-4 w-4" /> All bookings
        </a>
        <button onclick="window.print()" class="no-print rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Print</button>
        <span class="badge-sky">{{ $booking->statusLabel() }}</span>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-card title="Job">
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-slate-400">Customer</dt><dd class="font-semibold">{{ $booking->customer_name }} · {{ $booking->customer_phone }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Provider</dt><dd class="font-semibold">{{ $booking->provider?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Service</dt><dd>{{ $booking->service?->title ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Worker</dt><dd>{{ $booking->worker?->name ?? 'unassigned' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Address</dt><dd>{{ $booking->address ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Scheduled</dt><dd>{{ $booking->scheduled_at?->format('d M Y, H:i') ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Money ({{ $booking->payment_method }})</dt><dd class="text-lg font-black">{{ number_format($booking->total, 2) }}</dd></div>
                </dl>
                @if ($booking->notes)
                    <p class="alert-warn mt-3 !mb-0">Note: {{ $booking->notes }}</p>
                @endif
            </x-card>

            <x-card title="Timeline">
                @if ($booking->history->isEmpty())
                    <p class="text-sm text-slate-400">No transitions yet.</p>
                @else
                    <ol class="space-y-3">
                        @foreach ($booking->history as $entry)
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
            <x-card title="Worker">
                @if (auth()->user()->canAccess('transport', 'edit'))
                    <form method="POST" action="{{ route('admin.provider-bookings.assign', $booking) }}" class="flex gap-2">
                        @csrf
                        <x-select name="worker_id" class="flex-1">
                            @foreach ($workers as $worker)
                                <option value="{{ $worker->id }}" @selected($booking->worker_id === $worker->id)>{{ $worker->name }}</option>
                            @endforeach
                        </x-select>
                        <x-btn variant="dark">Assign</x-btn>
                    </form>
                @else
                    <p class="text-sm">{{ $booking->worker?->name ?? 'unassigned' }}</p>
                @endif
            </x-card>

            @if (auth()->user()->canAccess('transport', 'edit'))
                <x-card title="Move booking">
                    @if (empty($allowed))
                        <p class="text-sm text-slate-400">Terminal state.</p>
                    @else
                        <form method="POST" action="{{ route('admin.provider-bookings.transition', $booking) }}" class="space-y-3">
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
