{{-- DDE-Mart Admin — subscription history (original view, UI kit, read-only) --}}
<x-admin-layout title="Subscriptions">
    <x-page-head title="Subscriptions" sub="Plan purchase history." />

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="flex gap-2">
            <x-select name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (['active', 'expired', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">#</th><th class="th">Subscriber</th><th class="th">Plan</th><th class="th">Amount</th><th class="th">Period</th><th class="th">Status</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($subscriptions as $subscription)
                    <tr>
                        <td class="td font-mono">{{ $subscription->id }}</td>
                        <td class="td text-xs">{{ $subscription->subscriber_type }}<span class="block font-mono text-slate-400">{{ $subscription->subscriber_ref }}</span></td>
                        <td class="td">{{ $subscription->plan?->name ?? '—' }}</td>
                        <td class="td font-semibold">{{ number_format($subscription->amount, 2) }}</td>
                        <td class="td text-xs text-slate-500">{{ $subscription->starts_at?->format('d M Y') ?? '—' }} → {{ $subscription->ends_at?->format('d M Y') ?? '—' }}</td>
                        <td class="td text-xs uppercase text-slate-500">{{ $subscription->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td"><x-empty message="No subscriptions." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $subscriptions->links() }}</div>
</x-admin-layout>
