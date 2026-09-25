{{-- DDE-Mart Admin — filter presets (original view, UI kit) --}}
<x-admin-layout title="Store Filters">
    <x-page-head title="Store Filters" sub="Search toggles vendors can enable." />

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card title="Add preset">
            <form method="POST" action="{{ route('admin.presets.store') }}" class="flex gap-2">
                @csrf
                <x-input name="name" required placeholder="e.g. Outdoor Seating" class="flex-1" />
                <x-btn>Add</x-btn>
            </form>
        </x-card>

        <div class="table-card lg:col-span-2">
            <table class="min-w-full">
                <thead class="thead">
                    <tr><th class="th">Preset</th><th class="th text-right">Actions</th></tr>
                </thead>
                <tbody class="tbody-row">
                    @forelse ($presets as $preset)
                        <tr>
                            <td class="td font-semibold">{{ $preset->name }}</td>
                            <td class="td text-right">
                                <form method="POST" action="{{ route('admin.presets.destroy', $preset) }}"
                                      onsubmit="return confirm('Delete preset?')" class="inline">
                                    @csrf @method('DELETE')
                                    <x-btn variant="row-danger">Delete</x-btn>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="td"><x-empty message="No presets yet." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $presets->links() }}</div>
</x-admin-layout>
