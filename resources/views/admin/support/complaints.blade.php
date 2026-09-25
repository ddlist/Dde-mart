{{-- DDE-Mart Admin — complaints inbox (original view, UI kit) --}}
<x-admin-layout title="Complaints">
    <x-page-head title="Complaints" sub="Customer and driver grievances." />

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.complaints.index') }}" class="flex gap-2">
            <x-select name="status" onchange="this.form.submit()">
                <option value="">All</option>
                @foreach (['open', 'resolved', 'dismissed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Title</th><th class="th">Parties</th><th class="th">Order</th><th class="th">Status</th><th class="th text-right">Resolve</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($complaints as $complaint)
                    <tr>
                        <td class="td">
                            <p class="font-semibold">{{ $complaint->title }}</p>
                            <p class="max-w-md text-xs text-slate-400">{{ $complaint->description }}</p>
                        </td>
                        <td class="td text-xs text-slate-500">{{ $complaint->customer_name }} / {{ $complaint->driver_name }}</td>
                        <td class="td font-mono text-xs">{{ $complaint->order_ref ?? '—' }}</td>
                        <td class="td">
                            <span @class([
                                'badge', 'badge-amber' => $complaint->status === 'open',
                                'badge-green' => $complaint->status === 'resolved', 'badge-slate' => $complaint->status === 'dismissed',
                            ])>{{ $complaint->status }}</span>
                        </td>
                        <td class="td">
                            @if ($complaint->status === 'open' && auth()->user()->canAccess('content', 'edit'))
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.complaints.resolve', $complaint) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="to" value="resolved">
                                        <x-btn variant="row">Resolve</x-btn>
                                    </form>
                                    <form method="POST" action="{{ route('admin.complaints.resolve', $complaint) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="to" value="dismissed">
                                        <x-btn variant="row-danger">Dismiss</x-btn>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="No complaints." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $complaints->links() }}</div>
</x-admin-layout>
