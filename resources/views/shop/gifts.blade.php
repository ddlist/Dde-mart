{{-- DDE-Mart storefront — gift cards (original view) --}}
<x-store-layout title="Gift Cards">
    <h1 class="mb-4 text-xl font-black tracking-tight">Gift cards</h1>

    @if ($cards->isEmpty())
        <x-empty message="No gift cards right now." />
    @else
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($cards as $card)
                <x-card>
                    @if ($card->image_path)
                        <img src="{{ \App\Support\Images::url($card->image_path) }}" alt="" class="h-28 w-full rounded-xl object-cover">
                    @endif
                    <p class="mt-2 font-bold">{{ $card->title }}</p>
                    <p class="text-2xl font-black">{{ number_format($card->amount, 2) }}</p>
                    <form method="POST" action="{{ route('shop.gifts.buy') }}" class="mt-2 flex gap-2">
                        @csrf
                        <input type="hidden" name="gift_id" value="{{ $card->id }}">
                        <x-select name="payment_method" class="!w-auto">
                            <option value="cod">Pay on delivery</option>
                            <option value="wallet">Wallet</option>
                        </x-select>
                        <x-btn>Buy</x-btn>
                    </form>
                </x-card>
            @endforeach
        </div>
    @endif

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <x-card title="Redeem a code">
            <form method="POST" action="{{ route('shop.gifts.redeem') }}" class="flex gap-2">
                @csrf
                <x-input name="code" required placeholder="GIFT-XXXXXXXX" class="flex-1 font-mono uppercase" />
                <x-btn variant="dark">Redeem</x-btn>
            </form>
        </x-card>
        <x-card title="My codes">
            @if ($mine->isEmpty())
                <p class="text-sm text-slate-400">No codes yet.</p>
            @else
                <ul class="space-y-1.5 font-mono text-sm">
                    @foreach ($mine as $order)
                        <li class="flex justify-between"><span>{{ $order->code }}</span><span class="uppercase text-slate-400">{{ $order->status }}</span></li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-store-layout>
