{{-- DDE-Mart Admin — payout detail + workflow (original view, UI kit) --}}
<x-admin-layout title="Payout #{{ $payout->id }}">
    <div class="mb-4">
        <a href="{{ route('admin.payouts.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-800">
            <x-icon name="back" class="h-4 w-4" /> All payouts
        </a>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card title="Request" class="lg:col-span-2">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-xs text-slate-400">Requester</dt><dd class="font-semibold">{{ $payout->requester_name ?? '—' }} <span class="font-normal text-slate-400">({{ $payout->requester_type }} #{{ $payout->requester_id }})</span></dd></div>
                <div><dt class="text-xs text-slate-400">Amount</dt><dd class="text-lg font-black">{{ number_format($payout->amount, 2) }}</dd></div>
                <div><dt class="text-xs text-slate-400">Method</dt><dd class="font-semibold">{{ $payout->method }}</dd></div>
                <div><dt class="text-xs text-slate-400">Status</dt><dd class="font-bold uppercase">{{ $payout->status }}</dd></div>
                <div><dt class="text-xs text-slate-400">Requested</dt><dd>{{ $payout->created_at->format('d M Y, H:i') }}</dd></div>
                <div><dt class="text-xs text-slate-400">Paid at</dt><dd>{{ $payout->paid_at?->format('d M Y, H:i') ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-400">Handled by</dt><dd>{{ $payout->handler?->name ?? '—' }}</dd></div>
            </dl>
            @if ($payout->method_details)
                <h3 class="mb-1 mt-4 text-xs font-bold uppercase tracking-wider text-slate-400">Method details</h3>
                <pre class="overflow-x-auto rounded-xl bg-slate-50 p-3 text-xs">{{ json_encode($payout->method_details, JSON_PRETTY_PRINT) }}</pre>
            @endif
            @if ($payout->admin_note)
                <p class="alert-warn mt-3 !mb-0">Admin note: {{ $payout->admin_note }}</p>
            @endif
            <p class="mt-3 rounded-xl bg-sky-50 p-3 text-xs text-sky-800">Live gateway execution arrives in D8c — approval here only authorizes, it does not move money yet.</p>
        </x-card>

        <div>
            @if (auth()->user()->canAccess('finance', 'edit'))
                <x-card title="Move payout">
                    @if (empty($allowed))
                        <p class="text-sm text-slate-400">Terminal state.</p>
                    @else
                        <form method="POST" action="{{ route('admin.payouts.transition', $payout) }}" class="space-y-3">
                            @csrf
                            <x-select name="to">
                                @foreach ($allowed as $next)
                                    <option value="{{ $next }}">{{ ucfirst($next) }}</option>
                                @endforeach
                            </x-select>
                            <x-input name="admin_note" maxlength="1000" placeholder="Admin note (optional)" />
                            <x-btn class="w-full">Confirm move</x-btn>
                        </form>
                    @endif
                    @if ($payout->status === 'approved' && ! in_array($payout->method, ['bank', 'cash'], true))
                        <form method="POST" action="{{ route('admin.payouts.execute', $payout) }}" class="mt-3"
                              onsubmit="return confirm('Execute {{ number_format($payout->amount, 2) }} via {{ $payout->method }}? Real money moves.')">
                            @csrf
                            <x-btn variant="dark" class="w-full">Execute via {{ $payout->method }}</x-btn>
                        </form>
                    @endif
                </x-card>
            @endif
        </div>
    </div>
</x-admin-layout>
