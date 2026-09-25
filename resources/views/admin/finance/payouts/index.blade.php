{{-- DDE-Mart Admin — payout requests list (original view, UI kit) --}}
<x-admin-layout title="Payouts">
    <x-page-head title="Payouts" sub="Approve now — money moves in D8c." />

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.payouts.index') }}" class="flex flex-wrap gap-2">
            <x-select name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-select>
            <x-select name="type" onchange="this.form.submit()">
                <option value="">All requesters</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">#</th><th class="th">Requester</th><th class="th">Amount</th><th class="th">Method</th><th class="th">Status</th><th class="th">Requested</th><th class="th text-right">Open</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($payouts as $payout)
                    <tr>
                        <td class="td font-mono">{{ $payout->id }}</td>
                        <td class="td">
                            {{ $payout->requester_name ?? '—' }}
                            <span class="block text-xs text-slate-400">{{ $payout->requester_type }} #{{ $payout->requester_id }}</span>
                        </td>
                        <td class="td font-semibold">{{ number_format($payout->amount, 2) }}</td>
                        <td class="td text-xs text-slate-500">{{ $payout->method }}</td>
                        <td class="td">
                            <span @class([
                                'badge', 'badge-amber' => in_array($payout->status, ['pending', 'approved']),
                                'badge-green' => $payout->status === 'paid', 'badge-red' => $payout->status === 'rejected',
                            ])>{{ $payout->status }}</span>
                        </td>
                        <td class="td text-xs text-slate-500">{{ $payout->created_at->format('d M Y, H:i') }}</td>
                        <td class="td text-right">
                            <x-btn variant="row" href="{{ route('admin.payouts.show', $payout) }}">View</x-btn>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="td"><x-empty message="No payout requests." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $payouts->links() }}</div>
</x-admin-layout>
