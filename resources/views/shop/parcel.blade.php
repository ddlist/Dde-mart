{{-- DDE-Mart storefront — parcel booking (original view) --}}
<x-store-layout title="Send a Parcel">
    <h1 class="mb-4 text-xl font-black tracking-tight">Send a parcel</h1>

    <form method="POST" action="{{ route('shop.parcel.book') }}" class="grid max-w-3xl gap-4 lg:grid-cols-2">
        @csrf
        <x-card title="Sender">
            <div class="space-y-3">
                <x-field label="Name" for="s-name"><x-input id="s-name" name="sender_name" required /></x-field>
                <x-field label="Phone" for="s-phone"><x-input id="s-phone" name="sender_phone" required /></x-field>
                <x-field label="Pickup address" for="s-addr"><x-textarea id="s-addr" name="sender_address" rows="2" required /></x-field>
            </div>
        </x-card>
        <x-card title="Receiver">
            <div class="space-y-3">
                <x-field label="Name" for="r-name"><x-input id="r-name" name="receiver_name" required /></x-field>
                <x-field label="Phone" for="r-phone"><x-input id="r-phone" name="receiver_phone" required /></x-field>
                <x-field label="Dropoff address" for="r-addr"><x-textarea id="r-addr" name="receiver_address" rows="2" required /></x-field>
            </div>
        </x-card>
        <x-card title="Parcel" class="lg:col-span-2">
            <div class="grid gap-3 sm:grid-cols-3">
                <x-field label="Category" for="p-cat">
                    <x-select id="p-cat" name="category_id">
                        <option value="">—</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Weight slab" for="p-weight" :error="$errors->first('weight_id')">
                    <x-select id="p-weight" name="weight_id" required>
                        @foreach ($weights as $weight)
                            <option value="{{ $weight->id }}">{{ $weight->title }} ({{ number_format($weight->delivery_charge, 2) }})</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Distance (km)" for="p-dist" :error="$errors->first('distance_km')">
                    <x-input id="p-dist" name="distance_km" type="number" step="0.1" min="0" required value="1" />
                </x-field>
            </div>
            <x-field label="Notes" for="p-notes" class="mt-3">
                <x-input id="p-notes" name="notes" />
            </x-field>
            <x-btn class="mt-4">Book pickup (pay on delivery)</x-btn>
        </x-card>
    </form>
</x-store-layout>
