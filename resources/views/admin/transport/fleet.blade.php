{{-- DDE-Mart Admin — fleet masters (original view, UI kit) --}}
<x-admin-layout title="Fleet">
    <x-page-head title="Fleet" sub="Makes, models, cab types and destinations." />

    <div class="grid gap-4 lg:grid-cols-2">
        <x-card title="Car makes & models">
            <form method="POST" action="{{ route('admin.fleet.makes.store') }}" class="mb-3 flex gap-2">
                @csrf
                <x-input name="name" required placeholder="e.g. Audi" class="flex-1" />
                <x-btn>Add make</x-btn>
            </form>
            <ul class="space-y-2 text-sm">
                @forelse ($makes as $make)
                    <li class="rounded-xl border border-slate-100 p-2.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold">{{ $make->name }}</span>
                            <form method="POST" action="{{ route('admin.fleet.makes.destroy', $make) }}"
                                  onsubmit="return confirm('Delete make? Blocked if models exist.')">
                                @csrf @method('DELETE')
                                <x-btn variant="row-danger">Delete</x-btn>
                            </form>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">{{ $make->models->pluck('name')->join(', ') ?: 'no models' }}</p>
                        <form method="POST" action="{{ route('admin.fleet.models.store') }}" class="mt-2 flex gap-2">
                            @csrf
                            <input type="hidden" name="car_make_id" value="{{ $make->id }}">
                            <x-input name="name" required placeholder="Add model…" class="flex-1" />
                            <x-btn variant="row">Add</x-btn>
                        </form>
                    </li>
                @empty
                    <x-empty message="No makes yet." />
                @endforelse
            </ul>
        </x-card>

        <div class="space-y-4">
            <x-card title="Cab types">
                <form method="POST" action="{{ route('admin.fleet.types.store') }}" enctype="multipart/form-data" class="grid grid-cols-2 gap-2">
                    @csrf
                    <x-input name="name" required placeholder="e.g. Hatchback" />
                    <x-input name="capacity" type="number" min="1" placeholder="Seats" />
                    <x-input name="base_fare" type="number" step="0.01" min="0" placeholder="Base fare" />
                    <x-input name="per_km_fare" type="number" step="0.01" min="0" placeholder="Per km" />
                    <x-btn class="col-span-2">Add cab type</x-btn>
                </form>
                <ul class="mt-3 space-y-1.5 text-sm">
                    @foreach ($types as $type)
                        <li class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2">
                            <span>{{ $type->name }} <span class="text-xs text-slate-400">· {{ $type->capacity ?? '—' }} seats</span></span>
                            <form method="POST" action="{{ route('admin.fleet.types.destroy', $type) }}"
                                  onsubmit="return confirm('Delete type?')">
                                @csrf @method('DELETE')
                                <x-btn variant="row-danger">Delete</x-btn>
                            </form>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-3">{{ $types->links() }}</div>
            </x-card>

            <x-card title="Destinations">
                <form method="POST" action="{{ route('admin.fleet.destinations.store') }}" enctype="multipart/form-data" class="grid grid-cols-2 gap-2">
                    @csrf
                    <x-input name="title" required placeholder="e.g. Vadodara" />
                    <x-input name="latitude" type="number" step="0.0000001" placeholder="Latitude" />
                    <x-input name="longitude" type="number" step="0.0000001" placeholder="Longitude" />
                    <x-btn>Add destination</x-btn>
                </form>
                <ul class="mt-3 space-y-1.5 text-sm">
                    @foreach ($destinations as $destination)
                        <li class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2">
                            <span>{{ $destination->title }}</span>
                            <form method="POST" action="{{ route('admin.fleet.destinations.destroy', $destination) }}"
                                  onsubmit="return confirm('Delete destination?')">
                                @csrf @method('DELETE')
                                <x-btn variant="row-danger">Delete</x-btn>
                            </form>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-3">{{ $destinations->links() }}</div>
            </x-card>
        </div>
    </div>
</x-admin-layout>
