{{-- DDE-Mart storefront — dine-in (original view) --}}
<x-store-layout title="Book a Table">
    <h1 class="mb-4 text-xl font-black tracking-tight">Book a table</h1>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-card title="New reservation">
            <form method="POST" action="{{ route('shop.dinein.book') }}" class="space-y-3">
                @csrf
                <x-field label="Store" for="dn-store">
                    <x-select id="dn-store" name="store_id">
                        <option value="">—</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <div class="grid grid-cols-2 gap-3">
                    <x-field label="Guests" for="dn-guests">
                        <x-input id="dn-guests" name="guests" type="number" min="1" max="30" value="2" required />
                    </x-field>
                    <x-field label="Date & time" for="dn-when">
                        <x-input id="dn-when" name="booked_for" type="datetime-local" required />
                    </x-field>
                </div>
                <x-field label="Occasion" for="dn-occ">
                    <x-input id="dn-occ" name="occasion" />
                </x-field>
                <x-btn class="w-full">Request table</x-btn>
            </form>
        </x-card>

        <x-card title="My reservations">
            @if ($mine->isEmpty())
                <p class="text-sm text-slate-400">No reservations yet.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($mine as $booking)
                        <li class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2">
                            <span>{{ $booking->booked_for?->format('d M H:i') }} · {{ $booking->guests }} guests</span>
                            <span class="text-xs uppercase text-slate-400">{{ $booking->status }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-store-layout>
