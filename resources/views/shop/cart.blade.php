{{-- DDE-Mart storefront — cart (original view) --}}
<x-store-layout title="Cart">
    <h1 class="mb-4 text-xl font-black tracking-tight">Your cart</h1>

    @if ($error)
        <div class="alert-err">{{ $error }} — some items were removed or changed.</div>
    @endif

    @if (! $quote || empty($quote['lines']))
        <x-empty message="Your cart is empty.">
            <x-slot:action>
                <x-btn href="{{ route('shop.home') }}">Browse the shop</x-btn>
            </x-slot:action>
        </x-empty>
    @else
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="space-y-3 lg:col-span-2">
                @foreach ($quote['lines'] as $line)
                    <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold">{{ $line['name'] }}</p>
                            @foreach ($line['extras'] as $extra)
                                <p class="text-xs text-slate-400">+ {{ $extra['name'] }}</p>
                            @endforeach
                            <p class="mt-0.5 text-sm font-black">{{ number_format($line['subtotal'], 2) }}</p>
                        </div>
                        <form method="POST" action="{{ route('shop.cart.update') }}" class="flex items-center gap-1.5">
                            @csrf
                            <input type="hidden" name="key" value="{{ $line['_key'] }}">
                            <x-input name="quantity" type="number" min="0" max="99" value="{{ $line['quantity'] }}" class="!w-20" />
                            <x-btn variant="row">Set</x-btn>
                        </form>
                        <form method="POST" action="{{ route('shop.cart.remove') }}">
                            @csrf
                            <input type="hidden" name="key" value="{{ $line['_key'] }}">
                            <button class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600" title="Remove">
                                <x-icon name="x" class="h-4 w-4" />
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>

            <x-card title="Summary">
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between text-slate-500"><dt>Subtotal</dt><dd>{{ number_format($quote['subtotal'], 2) }}</dd></div>
                    @if ($quote['discount'] > 0)
                        <div class="flex justify-between text-emerald-700"><dt>Discount @if($quote['coupon']) ({{ $quote['coupon']['code'] }}) @endif</dt><dd>−{{ number_format($quote['discount'], 2) }}</dd></div>
                    @endif
                    <div class="flex justify-between text-slate-500"><dt>Delivery</dt><dd>{{ number_format($quote['delivery'], 2) }}</dd></div>
                    <div class="flex justify-between text-slate-500"><dt>Tax</dt><dd>{{ number_format($quote['tax'], 2) }}</dd></div>
                    <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-black"><dt>Total</dt><dd>{{ number_format($quote['total'], 2) }}</dd></div>
                </dl>

                <form method="POST" action="{{ route('shop.cart.coupon') }}" class="mt-4 flex gap-2">
                    @csrf
                    <x-input name="coupon_code" placeholder="Coupon code" value="{{ session('shop.coupon') }}" class="flex-1" />
                    <x-btn variant="dark">Apply</x-btn>
                </form>

                <p class="hint mt-4">Checkout with account sign-in arrives next — your cart is saved.</p>
                <x-btn class="mt-2 w-full" disabled>Checkout (soon)</x-btn>
            </x-card>
        </div>
    @endif
</x-store-layout>
