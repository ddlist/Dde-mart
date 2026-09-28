{{-- DDE-Mart Admin — store detail (original view, UI kit) --}}
<x-admin-layout title="Store">
    <x-page-head :title="$store->name" sub="Storefront, gallery, hours, offers and recent orders.">
        <x-slot:action>
            @if (auth()->user()->canAccess('stores', 'edit'))
                <x-btn href="{{ route('admin.stores.edit', $store) }}">Edit</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Orders" :value="$store->orders()->count()" hint="all time" />
        <x-stat-card label="Items" :value="$store->products_count ?? $store->products()->count()" hint="catalog size" />
        <x-stat-card label="Revenue" :value="number_format($revenue, 2)" hint="completed orders" />
        <x-stat-card label="Status" :value="ucfirst($store->status)" :hint="$store->is_open ? 'Open now' : 'Closed'" />
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <x-card title="Store info">
            <dl class="space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Owner</dt><dd>{{ $store->owner?->name ?? $store->owner_name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Phone</dt><dd>{{ $store->phone ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Section</dt><dd>{{ $store->section?->name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Zone</dt><dd>{{ $store->zone?->name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Commission</dt><dd>{{ $store->commission_type }} {{ $store->commission_value }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Delivery</dt><dd>fee {{ $store->delivery_fee }} · min {{ $store->min_order }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Plan</dt><dd>{{ $store->plan?->name ?? '—' }}</dd></div>
            </dl>
            @if ($store->description)
                <p class="mt-3 text-sm text-slate-600">{{ $store->description }}</p>
            @endif
            @if ($store->address)
                <p class="mt-1 text-sm text-slate-500">{{ $store->address }}</p>
            @endif
        </x-card>

        <x-card title="Gallery">
            @if ($store->images->isEmpty())
                <x-empty message="No gallery photos yet." />
            @else
                <div class="grid grid-cols-4 gap-2">
                    @foreach ($store->images as $image)
                        <div class="relative">
                            <img src="{{ asset('storage/'.$image->path) }}" alt="" class="h-20 w-full rounded object-cover" />
                            @if (auth()->user()->canAccess('stores', 'edit'))
                                <form method="POST" action="{{ route('admin.stores.gallery.destroy', [$store, $image]) }}" onsubmit="return confirm('Remove this photo?')">
                                    @csrf @method('DELETE')
                                    <button class="absolute right-1 top-1 rounded bg-red-600 px-1.5 text-xs text-white">×</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
            @if (auth()->user()->canAccess('stores', 'edit'))
                <form method="POST" action="{{ route('admin.stores.gallery.store', $store) }}" enctype="multipart/form-data" class="mt-3 flex gap-2">
                    @csrf
                    <x-input type="file" name="photos[]" multiple accept="image/*" />
                    <x-btn>Add</x-btn>
                </form>
            @endif
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <x-card title="Working hours">
            @if ($store->hours->isEmpty())
                <p class="text-sm text-slate-400">No hours set — open all week by default.</p>
            @else
                <ul class="space-y-1 text-sm">
                    @foreach ($store->hours as $slot)
                        <li class="flex justify-between">
                            <span>{{ \App\Models\StoreHour::DAYS[$slot->day] ?? $slot->day }}</span>
                            <span>{{ $slot->opens_at ?? '—' }} – {{ $slot->closes_at ?? '—' }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if (auth()->user()->canAccess('stores', 'edit'))
                <form method="POST" action="{{ route('admin.stores.hours.store', $store) }}" class="mt-3 space-y-2">
                    @csrf
                    <div class="flex gap-2">
                        <x-select name="hours[0][day]">
                            @for ($d = 0; $d < 7; $d++)
                                <option value="{{ $d }}">{{ \App\Models\StoreHour::DAYS[$d] }}</option>
                            @endfor
                        </x-select>
                        <x-input name="hours[0][opens_at]" type="time" />
                        <x-input name="hours[0][closes_at]" type="time" />
                    </div>
                    <p class="hint">Saving replaces the whole weekly schedule. Add one row per open day.</p>
                    <x-btn>Save hours</x-btn>
                </form>
            @endif
        </x-card>

        <x-card title="Special offers">
            @if ($store->offers->isEmpty())
                <x-empty message="No offer slots. Discounts apply per day and time window." />
            @else
                <ul class="space-y-1 text-sm">
                    @foreach ($store->offers as $offer)
                        <li class="flex items-center justify-between gap-2">
                            <span>{{ ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$offer->day] ?? $offer->day }} {{ $offer->opens_at }}–{{ $offer->closes_at }} · {{ $offer->discount }}{{ $offer->discount_type === 'percentage' ? '%' : '' }}</span>
                            @if (auth()->user()->canAccess('stores', 'edit'))
                                <form method="POST" action="{{ route('admin.stores.offers.destroy', [$store, $offer]) }}" onsubmit="return confirm('Remove this slot?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-bold text-red-600">Remove</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
            @if (auth()->user()->canAccess('stores', 'edit'))
                <form method="POST" action="{{ route('admin.stores.offers.store', $store) }}" class="mt-3 flex flex-wrap gap-2">
                    @csrf
                    <x-select name="day">
                        @for ($d = 0; $d < 7; $d++)
                            <option value="{{ $d }}">{{ ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$d] }}</option>
                        @endfor
                    </x-select>
                    <x-input name="opens_at" type="time" required />
                    <x-input name="closes_at" type="time" required />
                    <x-input name="discount" type="number" step="0.01" min="0" placeholder="Value" required class="w-24" />
                    <x-select name="discount_type">
                        <option value="percentage">%</option>
                        <option value="fixed">Fixed</option>
                    </x-select>
                    <x-btn>Add slot</x-btn>
                </form>
            @endif
        </x-card>
    </div>

    <x-card title="Recent orders" class="mt-4">
        @if ($orders->isEmpty())
            <x-empty message="No orders yet." />
        @else
            <div class="table-card">
                <table class="min-w-full">
                    <thead class="thead">
                        <tr><th class="th">Number</th><th class="th">Status</th><th class="th text-right">Total</th></tr>
                    </thead>
                    <tbody class="tbody-row">
                        @foreach ($orders as $order)
                            <tr>
                                <td class="td">{{ $order->number }}</td>
                                <td class="td"><span class="badge-slate">{{ $order->status }}</span></td>
                                <td class="td text-right">{{ number_format($order->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-admin-layout>
