{{-- DDE-Mart Admin — notification log (original view, UI kit) --}}
<x-admin-layout title="Notifications">
    <x-page-head title="Notifications" sub="Every broadcast and automated push, with its outcome.">
        <x-slot:action>
            @if (auth()->user()->canAccess('content', 'create'))
                <x-btn href="{{ route('admin.notifications.create') }}"><x-icon name="plus" class="h-4 w-4" /> New broadcast</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.notifications.index') }}" class="flex gap-2">
            <x-select name="status" onchange="this.form.submit()">
                <option value="">All outcomes</option>
                @foreach (['queued', 'sent', 'failed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Subject</th><th class="th">Audience</th><th class="th">Outcome</th><th class="th">By</th><th class="th">Sent</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($notifications as $notification)
                    <tr>
                        <td class="td">
                            <p class="font-semibold">{{ $notification->subject }}</p>
                            <p class="max-w-md truncate text-xs text-slate-400">{{ $notification->message }}</p>
                            @if ($notification->failure)
                                <p class="text-xs text-red-500">{{ $notification->failure }}</p>
                            @endif
                        </td>
                        <td class="td text-xs text-slate-500">{{ $notification->audience }}</td>
                        <td class="td">
                            <span @class([
                                'badge', 'badge-slate' => $notification->status === 'queued',
                                'badge-green' => $notification->status === 'sent', 'badge-red' => $notification->status === 'failed',
                            ])>{{ $notification->status }}</span>
                        </td>
                        <td class="td text-xs text-slate-500">{{ $notification->sender?->name ?? 'system' }}</td>
                        <td class="td text-xs text-slate-500">{{ $notification->created_at->format('d M Y, H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="No notifications yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $notifications->links() }}</div>
</x-admin-layout>
