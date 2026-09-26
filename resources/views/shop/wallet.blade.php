{{-- DDE-Mart storefront — wallet (original view, UI kit) --}}
<x-store-layout title="Wallet">
    <div class="mx-auto max-w-2xl space-y-4">
        <x-card title="Balance">
            <p class="text-3xl font-black text-emerald-700">{{ number_format($balance, 2) }}</p>
        </x-card>

        <x-card title="Top up">
            @if (count($methods) === 0)
                <p class="text-sm text-slate-500">Online top-up is unavailable right now — no payment gateway is configured.</p>
            @else
                <form method="POST" action="{{ route('shop.wallet.topup') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label for="topup-amount" class="mb-1 block text-sm font-semibold text-slate-600">Amount</label>
                        <input id="topup-amount" type="number" name="amount" min="1" max="100000" step="0.01" required
                            value="{{ old('amount') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        @error('amount')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2">
                        @foreach ($methods as $method)
                            <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-3 text-sm hover:bg-slate-50">
                                <input type="radio" name="method" value="{{ $method['key'] }}"
                                    @checked(old('method', $methods[0]['key']) === $method['key'])>
                                <span class="font-semibold">{{ $method['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('method')<p class="field-error">{{ $message }}</p>@enderror
                    <x-btn>Top up via gateway</x-btn>
                    <p class="hint">You will be redirected to the gateway, then back here for confirmation.</p>
                </form>
            @endif
        </x-card>

        <x-card title="Ledger">
            @forelse ($entries as $entry)
                <div class="flex items-center justify-between border-b border-slate-100 py-2 text-sm last:border-0">
                    <div>
                        <p class="font-semibold">{{ $entry->note ?? $entry->kind }}</p>
                        <p class="text-xs text-slate-500">{{ $entry->occurred_at?->format('d M Y H:i') }} · {{ $entry->method }}</p>
                    </div>
                    <p class="font-black {{ $entry->amount >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                        {{ $entry->amount >= 0 ? '+' : '' }}{{ number_format($entry->amount, 2) }}
                    </p>
                </div>
            @empty
                <x-empty message="No wallet activity yet." />
            @endforelse
            <div class="mt-3">{{ $entries->links() }}</div>
        </x-card>
    </div>
</x-store-layout>
