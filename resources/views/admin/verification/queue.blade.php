{{-- DDE-Mart Admin — verification queue (original view, UI kit) --}}
<x-admin-layout title="Verification">
    <x-page-head title="Verification queue" sub="Pendings first. Drivers and stores." />

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.verifications.queue') }}" class="flex gap-2">
            <x-select name="status" onchange="this.form.submit()">
                <option value="">All outcomes</option>
                @foreach (\App\Models\Verification::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Subject</th><th class="th">Document</th><th class="th">Files</th><th class="th">Status</th><th class="th text-right">Review</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($verifications as $verification)
                    <tr>
                        <td class="td">
                            @php $owner = $verification->verifiable; @endphp
                            @if ($owner instanceof \App\Models\Driver)
                                <a href="{{ route('admin.drivers.show', $owner) }}" class="font-semibold text-emerald-700">{{ $owner->name }}</a>
                                <span class="block text-xs text-slate-400">driver</span>
                            @elseif ($owner instanceof \App\Models\Store)
                                <a href="{{ route('admin.stores.edit', $owner) }}" class="font-semibold text-emerald-700">{{ $owner->name }}</a>
                                <span class="block text-xs text-slate-400">store</span>
                            @else
                                <span class="text-slate-400">orphaned #{{ $verification->id }}</span>
                            @endif
                        </td>
                        <td class="td text-xs">{{ $verification->type?->title ?? '—' }}</td>
                        <td class="td">
                            <div class="flex gap-1.5">
                                @foreach (['front_path', 'back_path'] as $field)
                                    @if ($verification->{$field})
                                        <a href="{{ \App\Support\Images::url($verification->{$field}) }}" target="_blank" rel="noopener"
                                           class="rounded-lg border border-slate-200 px-2 py-1 text-[11px] font-semibold hover:bg-slate-50">
                                            {{ str_starts_with($field, 'front') ? 'Front' : 'Back' }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </td>
                        <td class="td">
                            <span @class([
                                'badge', 'badge-amber' => $verification->status === 'pending',
                                'badge-green' => $verification->status === 'approved', 'badge-red' => $verification->status === 'rejected',
                            ])>{{ $verification->status }}</span>
                        </td>
                        <td class="td">
                            @if ($verification->status === 'pending' && auth()->user()->canAccess('drivers', 'edit'))
                                <form method="POST" action="{{ route('admin.verifications.review', $verification) }}" class="flex justify-end gap-2">
                                    @csrf
                                    <x-btn variant="row" name="to" value="approved">Approve</x-btn>
                                    <x-btn variant="row-danger" name="to" value="rejected">Reject</x-btn>
                                </form>
                            @else
                                <span class="text-xs text-slate-400">{{ $verification->reviewer?->name ?? '' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="Queue is clear." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $verifications->links() }}</div>
</x-admin-layout>
