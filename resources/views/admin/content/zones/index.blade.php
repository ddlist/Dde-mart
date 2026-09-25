{{-- DDE-Mart Admin — zones list (original view, UI kit) --}}
<x-admin-layout title="Zones">
    <x-page-head title="Zones" sub="Service-area circles.">
        <x-slot:action>
            @if (auth()->user()->canAccess('content', 'create'))
                <x-btn href="{{ route('admin.zones.create') }}"><x-icon name="plus" class="h-4 w-4" /> New zone</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Zone</th><th class="th">Center</th><th class="th">Radius</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($zones as $zone)
                    <tr>
                        <td class="td font-semibold">{{ $zone->name }}</td>
                        <td class="td text-xs text-slate-500">{{ $zone->latitude }}, {{ $zone->longitude }}</td>
                        <td class="td">{{ $zone->radius_km }} km</td>
                        <td class="td"><x-status-pill :active="$zone->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('content', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.zones.edit', $zone) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('content', 'delete'))
                                    <form method="POST" action="{{ route('admin.zones.destroy', $zone) }}"
                                          onsubmit="return confirm('Delete zone {{ $zone->name }}?')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="No zones yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $zones->links() }}</div>
</x-admin-layout>
