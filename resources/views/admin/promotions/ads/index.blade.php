{{-- DDE-Mart Admin — advertisements list (original view, UI kit) --}}
<x-admin-layout title="Advertisements">
    <x-page-head title="Advertisements" sub="Vendor-paid promos with approval workflow.">
        <x-slot:action>
            @if (auth()->user()->canAccess('promotions', 'create'))
                <x-btn href="{{ route('admin.ads.create') }}"><x-icon name="plus" class="h-4 w-4" /> New ad</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.ads.index') }}" class="flex flex-wrap gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search ads…" class="min-w-52 flex-1" />
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
                <tr><th class="th">Ad</th><th class="th">Schedule</th><th class="th">Payment</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($ads as $ad)
                    <tr>
                        <td class="td">
                            <div class="flex items-center gap-3">
                                @if ($ad->cover_path)
                                    <img src="{{ \App\Support\Images::url($ad->cover_path) }}" alt="" class="h-10 w-16 rounded-lg object-cover">
                                @endif
                                <div>
                                    <p class="font-semibold">{{ $ad->title }}</p>
                                    <p class="text-xs text-slate-400">{{ $ad->type }} · priority {{ $ad->priority }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="td text-xs text-slate-500">
                            {{ $ad->starts_at?->format('d M Y') ?? '—' }} → {{ $ad->ends_at?->format('d M Y') ?? '—' }}
                        </td>
                        <td class="td">
                            <span @class(['badge', 'badge-green' => $ad->payment_status === 'paid', 'badge-amber' => $ad->payment_status !== 'paid'])>{{ $ad->payment_status }}</span>
                        </td>
                        <td class="td text-xs font-bold uppercase text-slate-600">{{ $ad->status }}</td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('promotions', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.ads.edit', $ad) }}">Manage</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('promotions', 'delete'))
                                    <form method="POST" action="{{ route('admin.ads.destroy', $ad) }}"
                                          onsubmit="return confirm('Delete ad {{ $ad->title }}?')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="No advertisements yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $ads->links() }}</div>
</x-admin-layout>
