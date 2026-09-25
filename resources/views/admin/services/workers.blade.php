{{-- DDE-Mart Admin — provider workers (original view, UI kit) --}}
<x-admin-layout title="Workers">
    <x-page-head title="Workers" sub="Provider staff." />

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.provider-workers.index') }}" class="flex gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search workers…" class="flex-1" />
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Worker</th><th class="th">Provider</th><th class="th">Status</th><th class="th text-right">Toggle</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($workers as $worker)
                    <tr>
                        <td class="td">
                            <p class="font-semibold">{{ $worker->name }}</p>
                            <p class="text-xs text-slate-400">{{ $worker->phone }}</p>
                        </td>
                        <td class="td text-xs text-slate-500">{{ $worker->provider?->name ?? '—' }}</td>
                        <td class="td"><x-status-pill :active="$worker->is_active" /></td>
                        <td class="td text-right">
                            @if (auth()->user()->canAccess('transport', 'edit'))
                                <form method="POST" action="{{ route('admin.provider-workers.toggle', $worker) }}" class="inline">
                                    @csrf
                                    <x-btn variant="row">{{ $worker->is_active ? 'Deactivate' : 'Activate' }}</x-btn>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="td"><x-empty message="No workers." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $workers->links() }}</div>
</x-admin-layout>
