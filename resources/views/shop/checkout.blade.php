{{-- DDE-Mart storefront — checkout (original view) --}}
<x-store-layout title="Checkout">
    <h1 class="mb-4 text-xl font-black tracking-tight">Checkout</h1>

    <form method="POST" action="{{ route('shop.checkout.place') }}" class="grid gap-4 lg:grid-cols-3">
        @csrf
        <div class="space-y-4 lg:col-span-2">
            <x-card title="Delivery details">
                <div class="space-y-4">
                    <x-field label="Full address" for="co-address" :error="$errors->first('address')">
                        <x-textarea id="co-address" name="address" rows="2" required>{{ old('address', session('shop.address')) }}</x-textarea>
                    </x-field>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="Phone" for="co-phone" :error="$errors->first('phone')">
                            <x-input id="co-phone" name="phone" required value="{{ old('phone', $customer->phone) }}" />
                        </x-field>
                        <x-field label="Schedule (optional)" for="co-sched">
                            <x-input id="co-sched" name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at') }}" />
                        </x-field>
                    </div>
                    <x-field label="Notes (optional)" for="co-notes">
                        <x-input id="co-notes" name="notes" value="{{ old('notes') }}" />
                    </x-field>
                </div>
            </x-card>

            <x-card title="Payment method">
                <div class="space-y-2">
                    @foreach ($methods as $method)
                        <label class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 px-4 py-3 text-sm hover:bg-slate-50">
                            <span class="flex items-center gap-2">
                                <input type="radio" name="payment_method" value="{{ $method['key'] }}"
                                    @checked(old('payment_method', 'cod') === $method['key']) class="check">
                                <span class="font-semibold">{{ $method['label'] }}</span>
                            </span>
                            @if ($method['key'] === 'wallet')
                                <span class="text-xs text-slate-500">balance {{ number_format($balance, 2) }}</span>
                            @endif
                        </label>
                    @endforeach
                </div>
                @error('payment_method')<p class="field-error">{{ $message }}</p>@enderror
            </x-card>
        </div>

        <x-card title="Order total">
            <dl class="space-y-1.5 text-sm">
                <div class="flex justify-between text-slate-500"><dt>Subtotal</dt><dd>{{ number_format($quote['subtotal'], 2) }}</dd></div>
                @if ($quote['discount'] > 0)
                    <div class="flex justify-between text-emerald-700"><dt>Discount</dt><dd>−{{ number_format($quote['discount'], 2) }}</dd></div>
                @endif
                <div class="flex justify-between text-slate-500"><dt>Delivery</dt><dd>{{ number_format($quote['delivery'], 2) }}</dd></div>
                <div class="flex justify-between text-slate-500"><dt>Tax</dt><dd>{{ number_format($quote['tax'], 2) }}</dd></div>
                <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-black"><dt>Total</dt><dd>{{ number_format($quote['total'], 2) }}</dd></div>
            </dl>
            <x-btn class="mt-4 w-full">Place order</x-btn>
            <p class="hint mt-2">Online methods redirect to the gateway, then back for confirmation.</p>
        </x-card>
    </form>
</x-store-layout>
