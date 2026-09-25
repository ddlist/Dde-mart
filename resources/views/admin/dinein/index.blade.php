{{-- DDE-Mart Admin — dine-in bookings (original view, UI kit) --}}
<x-admin-layout title="Table Bookings">
    <x-page-head title="Table Bookings" sub="Dine-in reservations." />

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card title="New booking">
            <form method="POST" action="{{ route('admin.dinein.store') }}" class="space-y-3">
                @csrf
                <x-field label="Store" for="di-store">
                    <x-select id="di-store" name="store_id">
                        <option value="">—</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Guest name" for="di-name">
                    <x-input id="di-name" name="guest_name" required />
                </x-field>
                <div class="grid grid-cols-2 gap-2">
                    <x-field label="Phone" for="di-phone">
                        <x-input id="di-phone" name="guest_phone" />
                    </x-field>
                    <x-field label="Guests" for="di-guests">
                        <x-input id="di-guests" name="guests" type="number" min="1" value="2" required />
                    </x-field>
                </div>
                <x-field label="Date & time" for="di-when">
                    <x-input id="di-when" name="booked_for" type="datetime-local" required />
                </x-field>
                <x-field label="Occasion" for="di-occasion">
                    <x-input id="di-occasion" name="occasion" />
                </x-field>
                <x-btn>Create booking</x-btn>
            </form>
        </x-card>

        <div class="table-card lg:col-span-2">
            <table class="min-w-full">
                <thead class="thead">
                    <tr><th class="th">Guest</th><th class="th">Store</th><th class="th">When</th><th class="th">Status</th><th class="th text-right">Move</th></tr>
                </thead>
                <tbody class="tbody-row">
                    @forelse ($bookings as $booking)
                        <tr>
                            <td class="td">
                                <p class="font-semibold">{{ $booking->guest_name }} <span class="text-xs font-normal text-slate-400">× {{ $booking->guests }}</span></p>
                                <p class="text-xs text-slate-400">{{ $booking->guest_phone }}</p>
                            </td>
                            <td class="td text-xs text-slate-500">{{ $booking->store?->name ?? '—' }}</td>
                            <td class="td text-xs text-slate-500">{{ $booking->booked_for?->format('d M H:i') ?? '—' }}</td>
                            <td class="td text-xs uppercase text-slate-500">{{ $booking->status }}</td>
                            <td class="td">
                                <div class="flex flex-wrap justify-end gap-1.5">
                                    @foreach (\App\Models\TableBooking::TRANSITIONS[$booking->status] ?? [] as $next)
                                        <form method="POST" action="{{ route('admin.dinein.transition', $booking) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="to" value="{{ $next }}">
                                            <x-btn variant="row">{{ $next }}</x-btn>
                                        </form>
                                    @endforeach
                                    <form method="POST" action="{{ route('admin.dinein.destroy', $booking) }}"
                                          onsubmit="return confirm('Delete booking?')" class="inline">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="td"><x-empty message="No bookings." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $bookings->links() }}</div>
</x-admin-layout>
