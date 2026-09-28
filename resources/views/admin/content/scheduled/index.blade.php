{{-- DDE-Mart Admin — scheduled pushes (original view, UI kit) --}}
<x-admin-layout title="Scheduled pushes">
    <x-page-head title="Scheduled pushes" sub="Queued broadcasts delivered by the schedule:send cron.">
        <x-slot:action>
            @if (auth()->user()->canAccess('content', 'create'))
                <x-btn href="{{ route('admin.scheduled.create') }}"><x-icon name="plus" class="h-4 w-4" /> Schedule push</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Subject</th><th class="th">Audience</th><th class="th">Send at</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($items as $item)
                    <tr>
                        <td class="td">
                            <p class="font-semibold">{{ $item->subject }}</p>
                            <p class="max-w-md truncate text-xs text-slate-400">{{ $item->message }}</p>
                        </td>
                        <td class="td text-xs text-slate-500">{{ $item->audience }}</td>
                        <td class="td text-xs text-slate-500">{{ $item->send_at }}</td>
                        <td class="td"><span class="badge-slate">{{ $item->status }}</span></td>
                        <td class="td text-right">
                            @if ($item->status === 'scheduled' && auth()->user()->canAccess('content', 'edit'))
                                <form method="POST" action="{{ route('admin.scheduled.destroy', $item) }}" onsubmit="return confirm('Cancel this push?')">
                                    @csrf @method('DELETE')
                                    <x-btn variant="row-danger">Cancel</x-btn>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td"><x-empty message="Nothing scheduled." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</x-admin-layout>
