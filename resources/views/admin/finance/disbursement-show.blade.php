{{-- DDE-Mart Admin — disbursement detail (original view, UI kit) --}}
<x-admin-layout title="Batch #{{ $batch->id }}">
    <div class="mb-4">
        <a href="{{ route('admin.disbursements.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-800">
            <x-icon name="back" class="h-4 w-4" /> All batches
        </a>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card title="Batch #{{ $batch->id }}" class="lg:col-span-2">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-xs text-slate-400">Method</dt><dd class="font-semibold">{{ $batch->method }}</dd></div>
                <div><dt class="text-xs text-slate-400">Status</dt><dd class="font-bold uppercase">{{ $batch->status }}</dd></div>
                <div><dt class="text-xs text-slate-400">Total</dt><dd class="text-lg font-black">{{ number_format($batch->total(), 2) }}</dd></div>
                <div><dt class="text-xs text-slate-400">Handled by</dt><dd>{{ $batch->handler?->name ?? '—' }}</dd></div>
            </dl>
            @if ($batch->note)
                <p class="mt-3 text-sm text-slate-500">{{ $batch->note }}</p>
            @endif

            <h3 class="mb-2 mt-4 text-sm font-bold">Member payouts ({{ $batch->payouts->count() }})</h3>
            <ul class="space-y-1.5 text-sm">
                @foreach ($batch->payouts as $payout)
                    <li class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2">
                        <span>#{{ $payout->id }} · {{ $payout->requester_name }} — <span class="uppercase text-slate-400">{{ $payout->status }}</span></span>
                        <span class="font-bold">{{ number_format($payout->amount, 2) }}</span>
                    </li>
                @endforeach
            </ul>
        </x-card>

        <div>
            @if ($batch->status === 'pending' && auth()->user()->canAccess('finance', 'edit'))
                <x-card title="Pay batch">
                    <p class="mb-3 text-xs text-slate-500">Marks the batch paid and cascades to approved members.</p>
                    <form method="POST" action="{{ route('admin.disbursements.pay', $batch) }}"
                          onsubmit="return confirm('Mark batch #{{ $batch->id }} paid?')">
                        @csrf
                        <x-btn class="w-full">Mark paid</x-btn>
                    </form>
                </x-card>
            @endif
        </div>
    </div>
</x-admin-layout>
