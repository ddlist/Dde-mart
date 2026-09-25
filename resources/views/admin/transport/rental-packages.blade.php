{{-- DDE-Mart Admin — rental packages (original view, UI kit) --}}
<x-admin-layout title="Rental Packages">
    <x-page-head title="Rental Packages" sub="Base fare + allowances + extra rates.">
        <x-slot:action>
            @if (auth()->user()->canAccess('transport', 'create'))
                <x-btn href="{{ route('admin.rental-packages.create') }}"><x-icon name="plus" class="h-4 w-4" /> New package</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Package</th><th class="th">Vehicle</th><th class="th">Base fare</th><th class="th">Includes</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($packages as $package)
                    <tr>
                        <td class="td font-semibold">{{ $package->name }}</td>
                        <td class="td text-xs text-slate-500">{{ $package->vehicleType?->name ?? '—' }}</td>
                        <td class="td font-semibold">{{ number_format($package->base_fare, 2) }}</td>
                        <td class="td text-xs text-slate-500">{{ $package->included_hours }}h · {{ $package->included_km }}km</td>
                        <td class="td"><x-status-pill :active="$package->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('transport', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.rental-packages.edit', $package) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('transport', 'delete'))
                                    <form method="POST" action="{{ route('admin.rental-packages.destroy', $package) }}"
                                          onsubmit="return confirm('Delete package?')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td"><x-empty message="No packages." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $packages->links() }}</div>
</x-admin-layout>
