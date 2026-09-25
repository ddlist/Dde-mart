{{-- DDE-Mart Admin — support chat inbox (original view, UI kit, read-only) --}}
<x-admin-layout title="Support Chat">
    <x-page-head title="Support Chat" sub="Read-only threads. Replies ship with the apps API." />

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.chats.index') }}" class="flex gap-2">
            <x-select name="status" onchange="this.form.submit()">
                <option value="">All</option>
                @foreach (['open', 'closed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Thread</th><th class="th">Audience</th><th class="th">Last message</th><th class="th">Status</th><th class="th text-right">Open</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($threads as $thread)
                    <tr>
                        <td class="td font-semibold">{{ $thread->subject ?? 'Thread #'.$thread->id }}</td>
                        <td class="td text-xs text-slate-500">{{ $thread->audience }}</td>
                        <td class="td max-w-xs truncate text-xs text-slate-500">{{ $thread->last_message }}</td>
                        <td class="td">
                            <span @class(['badge', 'badge-amber' => $thread->status === 'open', 'badge-slate' => $thread->status !== 'open'])>{{ $thread->status }}</span>
                        </td>
                        <td class="td text-right">
                            <x-btn variant="row" href="{{ route('admin.chats.show', $thread) }}">View</x-btn>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="No threads." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $threads->links() }}</div>
</x-admin-layout>
