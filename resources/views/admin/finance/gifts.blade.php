{{-- DDE-Mart Admin — gift ledger (original view, UI kit) --}}
<x-admin-layout title="Gift Orders">
    <x-page-head title="Gift Orders" sub="Issued codes and redemption." />

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.gift-orders.index') }}" class="flex gap-2">
            <x-select name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (\App\Models\GiftOrder::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Code</th><th class="th">Card</th><th class="th">Amount</th><th class="th">Buyer</th><th class="th">Expires</th><th class="th">Status</th><th class="th text-right">Redeem</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($gifts as $gift)
                    <tr>
                        <td class="td font-mono font-bold">{{ $gift->code ?? '—' }}</td>
                        <td class="td">{{ $gift->gift?->title ?? '—' }}</td>
                        <td class="td font-semibold">{{ number_format($gift->amount, 2) }}</td>
                        <td class="td font-mono text-xs text-slate-500">{{ $gift->buyer_ref ?? '—' }}</td>
                        <td class="td text-xs text-slate-500">{{ $gift->expires_at?->format('d M Y') ?? '—' }}</td>
                        <td class="td">
                            <span @class([
                                'badge', 'badge-green' => $gift->status === 'active',
                                'badge-slate' => $gift->status !== 'active',
                            ])>{{ $gift->status }}</span>
                        </td>
                        <td class="td text-right">
                            @if ($gift->status === 'active' && auth()->user()->canAccess('finance', 'edit'))
                                <form method="POST" action="{{ route('admin.gift-orders.redeem', $gift) }}"
                                      onsubmit="return confirm('Redeem code {{ $gift->code }}?')" class="inline">
                                    @csrf
                                    <x-btn variant="row">Redeem</x-btn>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="td"><x-empty message="No gift orders." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $gifts->links() }}</div>
</x-admin-layout>
