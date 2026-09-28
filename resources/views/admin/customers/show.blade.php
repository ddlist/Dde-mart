{{-- DDE-Mart Admin — customer detail (original view, UI kit) --}}
<x-admin-layout title="Customer">
    <x-page-head :title="$customer->name" sub="End-user account.">
        <x-slot:action>
            @if (auth()->user()->canAccess('users', 'edit'))
                <x-btn href="{{ route('admin.customers.edit', $customer) }}">Edit</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <div class="grid gap-4 md:grid-cols-3">
        <x-card title="Profile">
            <dl class="space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Phone</dt><dd>{{ $customer->phone }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd>{{ $customer->email ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Status</dt><dd>{{ $customer->is_active ? 'Active' : 'Inactive' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Wallet</dt><dd class="font-semibold">{{ number_format($balance, 2) }}</dd></div>
            </dl>

            @if (auth()->user()->canAccess('users', 'edit'))
                <form method="POST" action="{{ route('admin.customers.topup', $customer) }}" class="mt-4 space-y-3 border-t pt-4">
                    @csrf
                    <x-field label="Top-up amount" for="topup-amount" :error="$errors->first('amount')">
                        <x-input id="topup-amount" name="amount" type="number" step="0.01" min="0.01" required />
                    </x-field>
                    <x-field label="Note (optional)" for="topup-note">
                        <x-input id="topup-note" name="note" />
                    </x-field>
                    <x-btn>Add wallet amount</x-btn>
                </form>
            @endif
        </x-card>

        <x-card title="Recent orders" class="md:col-span-2">
            @if ($orders->isEmpty())
                <x-empty message="No orders on record." />
            @else
                <div class="table-card">
                    <table class="min-w-full">
                        <thead class="thead">
                            <tr><th class="th">Number</th><th class="th">Status</th><th class="th text-right">Total</th></tr>
                        </thead>
                        <tbody class="tbody-row">
                            @foreach ($orders as $order)
                                <tr>
                                    <td class="td">{{ $order->number }}</td>
                                    <td class="td"><span class="badge-slate">{{ $order->status }}</span></td>
                                    <td class="td text-right">{{ number_format($order->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>

    <x-card title="Wallet ledger" class="mt-4">
        @if ($entries->isEmpty())
            <x-empty message="No wallet activity." />
        @else
            <div class="table-card">
                <table class="min-w-full">
                    <thead class="thead">
                        <tr><th class="th">Date</th><th class="th">Note</th><th class="th">Method</th><th class="th text-right">Amount</th></tr>
                    </thead>
                    <tbody class="tbody-row">
                        @foreach ($entries as $entry)
                            <tr>
                                <td class="td text-slate-500">{{ $entry->occurred_at ?? $entry->created_at }}</td>
                                <td class="td">{{ $entry->note ?? $entry->kind }}</td>
                                <td class="td">{{ $entry->method ?? '—' }}</td>
                                <td class="td text-right">{{ number_format($entry->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-admin-layout>
