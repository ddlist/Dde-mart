{{-- DDE-Mart Admin — stores list (original view, UI kit) --}}
<x-admin-layout title="Stores">
    <x-page-head title="Stores" sub="Vendor storefronts and approval">
        <x-slot:action>
            @if (auth()->user()->canAccess('stores', 'create'))
                <x-btn href="{{ route('admin.stores.create') }}"><x-icon name="plus" class="h-4 w-4" /> New store</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.stores.index') }}" class="flex flex-wrap gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search name, owner, phone…" class="min-w-52 flex-1" />
            <x-select name="section" onchange="this.form.submit()">
                <option value="">All sections</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected((string) request('section') === (string) $section->id)>{{ $section->name }}</option>
                @endforeach
            </x-select>
            <x-select name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Store</th><th class="th">Section / Zone</th><th class="th">Products</th><th class="th">Commission</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($stores as $store)
                    <tr>
                        <td class="td">
                            <div class="flex items-center gap-3">
                                @if ($store->image_path)
                                    <img src="{{ \App\Support\Images::url($store->image_path) }}" alt="" class="h-10 w-10 rounded-xl object-cover">
                                @else
                                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-100 font-black text-emerald-700">{{ strtoupper(substr($store->name, 0, 1)) }}</span>
                                @endif
                                <div>
                                    <p class="font-semibold">{{ $store->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $store->owner_name }} · {{ $store->phone }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="td text-xs text-slate-500">{{ $store->section?->name ?? '—' }} / {{ $store->zone?->name ?? '—' }}</td>
                        <td class="td text-slate-500">{{ $store->products_count }}</td>
                        <td class="td text-xs text-slate-500">{{ $store->commission_type === 'percentage' ? $store->commission_value.'%' : $store->commission_value }}</td>
                        <td class="td">
                            <span @class([
                                'badge', 'badge-amber' => $store->status === 'pending', 'badge-green' => $store->status === 'active',
                                'badge-slate' => $store->status === 'suspended', 'badge-red' => $store->status === 'rejected',
                            ])>{{ $store->status }}</span>
                            @if (! $store->is_open)
                                <span class="badge-slate">closed</span>
                            @endif
                        </td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                <x-btn variant="row" href="{{ route('admin.stores.show', $store) }}">View</x-btn>
                                @if (auth()->user()->canAccess('stores', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.stores.edit', $store) }}">Manage</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('stores', 'delete'))
                                    <form method="POST" action="{{ route('admin.stores.destroy', $store) }}"
                                          onsubmit="return confirm('Delete store {{ $store->name }}? Blocked if products exist.')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td"><x-empty message="No stores yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $stores->links() }}</div>
</x-admin-layout>
