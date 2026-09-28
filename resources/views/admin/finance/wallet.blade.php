{{-- DDE-Mart Admin — wallet ledger (original view, UI kit, read-only) --}}
<x-admin-layout title="Wallet">
    <x-page-head title="Wallet ledger" sub="System entries plus manual adjustments.">
        <x-slot:action>
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($totals as $type => $balance)
                    <span class="badge-slate">{{ $type }}: {{ number_format($balance, 2) }}</span>
                @endforeach
                <x-btn href="{{ route('admin.wallet.summary') }}">Summary</x-btn>
            </div>
        </x-slot:action>
    </x-page-head>

    @if (auth()->user()->canAccess('finance', 'edit'))
        <x-card title="Manual adjustment" class="mb-4">
            <form method="POST" action="{{ route('admin.wallet.adjust') }}" class="flex flex-wrap items-end gap-2">
                @csrf
                <x-select name="owner_type" required>
                    @foreach (['customer', 'driver', 'vendor', 'owner', 'provider'] as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </x-select>
                <x-input name="owner_ref" placeholder="Owner ref (e.g. phone)" required class="min-w-44 flex-1" />
                <x-input name="amount" type="number" step="0.01" min="0.01" placeholder="Amount" required class="w-32" />
                <x-select name="direction">
                    <option value="credit">Credit</option>
                    <option value="debit">Debit</option>
                </x-select>
                <x-input name="note" placeholder="Note (optional)" class="min-w-44 flex-1" />
                <x-btn>Apply</x-btn>
            </form>
        </x-card>
    @endif

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.wallet.index') }}" class="flex flex-wrap gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search note, owner, order…" class="min-w-52 flex-1" />
            <x-select name="type" onchange="this.form.submit()">
                <option value="">All owners</option>
                @foreach (['customer', 'driver', 'vendor', 'provider'] as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">When</th><th class="th">Owner</th><th class="th">Note</th><th class="th">Method</th><th class="th">Status</th><th class="th text-right">Amount</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($entries as $entry)
                    <tr>
                        <td class="td text-xs text-slate-500">{{ $entry->occurred_at?->format('d M Y, H:i') ?? $entry->created_at->format('d M Y, H:i') }}</td>
                        <td class="td text-xs">{{ $entry->owner_type }}<span class="block font-mono text-slate-400">{{ $entry->owner_ref }}</span></td>
                        <td class="td max-w-xs truncate text-xs text-slate-500">{{ $entry->note }}</td>
                        <td class="td text-xs text-slate-500">{{ $entry->method ?? '—' }} · {{ $entry->kind }}</td>
                        <td class="td text-xs uppercase text-slate-500">{{ $entry->status }}</td>
                        <td class="td text-right font-semibold {{ $entry->amount < 0 ? 'text-red-600' : 'text-emerald-700' }}">
                            {{ number_format($entry->amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td"><x-empty message="No wallet entries." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $entries->links() }}</div>
</x-admin-layout>
