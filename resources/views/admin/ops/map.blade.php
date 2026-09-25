{{-- DDE-Mart Admin — live map (original view, graceful without key) --}}
<x-admin-layout title="Live Map">
    <x-page-head title="Live Map" sub="Stores and zones. Needs a Maps browser key for tiles." />

    @if (! $key)
        <div class="alert-warn max-w-3xl">
            No <code>MAPS_KEY</code> configured — showing the coordinate directory instead of tiles.
        </div>
    @endif

    @if ($key)
        <div id="map" class="mb-4 h-96 w-full rounded-2xl border border-slate-200"></div>
        <script src="https://maps.googleapis.com/maps/api/js?key={{ $key }}"></script>
        <script>
            const map = new google.maps.Map(document.getElementById('map'), { zoom: 11, center: { lat: 24.86, lng: 67.0 } });
            @foreach ($stores as $store)
                new google.maps.Marker({ position: { lat: {{ $store->latitude }}, lng: {{ $store->longitude }} }, map, title: @json($store->name) });
            @endforeach
            @foreach ($zones as $zone)
                new google.maps.Circle({ center: { lat: {{ $zone->latitude }}, lng: {{ $zone->longitude }} }, radius: {{ $zone->radius_km * 1000 }}, map, fillOpacity: 0.08, strokeOpacity: 0.4 });
            @endforeach
        </script>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <x-card title="Stores ({{ $stores->count() }})">
            <ul class="max-h-64 space-y-1 overflow-y-auto text-sm">
                @foreach ($stores as $store)
                    <li class="flex justify-between"><span>{{ $store->name }}</span><span class="text-slate-400">{{ $store->latitude }}, {{ $store->longitude }}</span></li>
                @endforeach
            </ul>
        </x-card>
        <x-card title="Zones ({{ $zones->count() }})">
            <ul class="max-h-64 space-y-1 overflow-y-auto text-sm">
                @foreach ($zones as $zone)
                    <li class="flex justify-between"><span>{{ $zone->name }}</span><span class="text-slate-400">{{ $zone->radius_km }} km</span></li>
                @endforeach
            </ul>
        </x-card>
    </div>
</x-admin-layout>
