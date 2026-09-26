{{-- DDE-Mart Admin — SOS inbox (original view, UI kit) --}}
<x-admin-layout title="SOS Alerts">
    <x-page-head title="SOS Alerts" sub="Safety alerts — resolve-only." />

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.sos.index') }}" class="flex gap-2">
            <x-select name="status" onchange="this.form.submit()">
                <option value="">All</option>
                @foreach (['open', 'resolved'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Order</th><th class="th">Reporter</th><th class="th">Location</th><th class="th">Raised</th><th class="th">Status</th><th class="th text-right">Resolve</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($alerts as $alert)
                    <tr>
                        <td class="td font-mono text-xs">{{ $alert->order_ref ?? '—' }}</td>
                        <td class="td font-mono text-xs">{{ $alert->reporter_ref ? ($alert->reporter_type.':'.$alert->reporter_ref) : '—' }}</td>
                        <td class="td text-xs">
                            @if ($alert->latitude)
                                <a href="https://www.google.com/maps?q={{ $alert->latitude }},{{ $alert->longitude }}"
                                   target="_blank" rel="noopener" class="text-emerald-700">
                                    {{ $alert->latitude }}, {{ $alert->longitude }} ↗
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="td text-xs text-slate-500">{{ $alert->occurred_at?->format('d M Y, H:i') ?? $alert->created_at->format('d M Y, H:i') }}</td>
                        <td class="td">
                            <span @class(['badge', 'badge-red' => $alert->status === 'open', 'badge-green' => $alert->status !== 'open'])>{{ $alert->status }}</span>
                        </td>
                        <td class="td text-right">
                            @if ($alert->status === 'open' && auth()->user()->canAccess('content', 'edit'))
                                <form method="POST" action="{{ route('admin.sos.resolve', $alert) }}" class="inline">
                                    @csrf
                                    <x-btn variant="row">Resolve</x-btn>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td"><x-empty message="No SOS alerts." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $alerts->links() }}</div>
</x-admin-layout>
