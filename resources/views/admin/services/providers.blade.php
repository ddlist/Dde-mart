{{-- DDE-Mart Admin — providers directory (original view, UI kit) --}}
<x-admin-layout title="Providers">
    <x-page-head title="Providers" sub="On-demand service businesses." />

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.providers.index') }}" class="flex flex-wrap gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search name or phone…" class="min-w-52 flex-1" />
            <x-select name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (\App\Models\Provider::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Provider</th><th class="th">Services</th><th class="th">Workers</th><th class="th">Status</th><th class="th text-right">Open</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($providers as $provider)
                    <tr>
                        <td class="td">
                            <p class="font-semibold">{{ $provider->name }}</p>
                            <p class="text-xs text-slate-400">{{ $provider->phone }}</p>
                        </td>
                        <td class="td text-slate-500">{{ $provider->services_count }}</td>
                        <td class="td text-slate-500">{{ $provider->workers_count }}</td>
                        <td class="td">
                            <span @class([
                                'badge', 'badge-amber' => $provider->status === 'pending', 'badge-green' => $provider->status === 'active',
                                'badge-slate' => $provider->status === 'suspended', 'badge-red' => $provider->status === 'rejected',
                            ])>{{ $provider->status }}</span>
                        </td>
                        <td class="td text-right">
                            <x-btn variant="row" href="{{ route('admin.providers.show', $provider) }}">Open</x-btn>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="No providers." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $providers->links() }}</div>
</x-admin-layout>
