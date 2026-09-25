{{-- DDE-Mart Admin — provider services (original view, UI kit) --}}
<x-admin-layout title="Services">
    <x-page-head title="Services" sub="Bookable line items." />

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.provider-services.index') }}" class="flex gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search services…" class="flex-1" />
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Service</th><th class="th">Provider</th><th class="th">Price</th><th class="th">Status</th><th class="th text-right">Toggle</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($services as $service)
                    <tr>
                        <td class="td">
                            <p class="font-semibold">{{ $service->title }}</p>
                            <p class="text-xs text-slate-400">{{ $service->category?->title ?? '' }}</p>
                        </td>
                        <td class="td text-xs text-slate-500">{{ $service->provider?->name ?? '—' }}</td>
                        <td class="td font-semibold">{{ number_format($service->price, 2) }}</td>
                        <td class="td"><x-status-pill :active="$service->is_active" /></td>
                        <td class="td text-right">
                            @if (auth()->user()->canAccess('transport', 'edit'))
                                <form method="POST" action="{{ route('admin.provider-services.toggle', $service) }}" class="inline">
                                    @csrf
                                    <x-btn variant="row">{{ $service->is_active ? 'Hide' : 'Show' }}</x-btn>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="No services." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $services->links() }}</div>
</x-admin-layout>
