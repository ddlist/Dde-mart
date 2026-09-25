{{-- DDE-Mart Admin — disbursement batches (original view, UI kit) --}}
<x-admin-layout title="Disbursements">
    <x-page-head title="Disbursements" sub="Grouped approved payouts, paid as one movement.">
        <x-slot:action>
            @if (auth()->user()->canAccess('finance', 'edit'))
                <x-btn href="{{ route('admin.disbursements.create') }}"><x-icon name="plus" class="h-4 w-4" /> New batch</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">#</th><th class="th">Payouts</th><th class="th">Method</th><th class="th">Status</th><th class="th">Handled by</th><th class="th text-right">Open</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($batches as $batch)
                    <tr>
                        <td class="td font-mono">{{ $batch->id }}</td>
                        <td class="td">{{ $batch->payouts_count }}</td>
                        <td class="td text-xs text-slate-500">{{ $batch->method }}</td>
                        <td class="td">
                            <span @class(['badge', 'badge-amber' => $batch->status === 'pending', 'badge-green' => $batch->status === 'paid'])>{{ $batch->status }}</span>
                        </td>
                        <td class="td text-xs text-slate-500">{{ $batch->handler?->name ?? '—' }}</td>
                        <td class="td text-right">
                            <x-btn variant="row" href="{{ route('admin.disbursements.show', $batch) }}">Open</x-btn>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td"><x-empty message="No batches yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $batches->links() }}</div>
</x-admin-layout>
