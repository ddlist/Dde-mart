{{-- DDE-Mart Admin — scheduled pushes (original view, UI kit) --}}
<x-admin-layout title="Scheduled">
    <x-page-head title="Scheduled Pushes" sub="Sent by the schedule:send command (cron)." />

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card title="Schedule push">
            <form method="POST" action="{{ route('admin.scheduled.store') }}" class="space-y-3">
                @csrf
                <x-field label="Audience" for="sc-aud">
                    <x-select id="sc-aud" name="audience">
                        @foreach (\App\Models\Notification::AUDIENCES as $audience)
                            <option value="{{ $audience }}">{{ ucfirst($audience) }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Subject" for="sc-sub">
                    <x-input id="sc-sub" name="subject" required />
                </x-field>
                <x-field label="Message" for="sc-msg">
                    <x-textarea id="sc-msg" name="message" rows="3" required />
                </x-field>
                <x-field label="Send at" for="sc-at">
                    <x-input id="sc-at" name="send_at" type="datetime-local" required />
                </x-field>
                <x-btn>Schedule</x-btn>
            </form>
        </x-card>

        <div class="table-card lg:col-span-2">
            <table class="min-w-full">
                <thead class="thead">
                    <tr><th class="th">Subject</th><th class="th">Send at</th><th class="th">Status</th><th class="th text-right">Cancel</th></tr>
                </thead>
                <tbody class="tbody-row">
                    @forelse ($scheduled as $item)
                        <tr>
                            <td class="td">
                                <p class="font-semibold">{{ $item->subject }}</p>
                                <p class="max-w-xs truncate text-xs text-slate-400">{{ $item->message }}</p>
                            </td>
                            <td class="td text-xs text-slate-500">{{ $item->send_at->format('d M Y, H:i') }}</td>
                            <td class="td text-xs uppercase text-slate-500">{{ $item->status }}</td>
                            <td class="td text-right">
                                @if ($item->status === 'scheduled')
                                    <form method="POST" action="{{ route('admin.scheduled.destroy', $item) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Cancel</x-btn>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="td"><x-empty message="Nothing scheduled." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $scheduled->links() }}</div>
</x-admin-layout>
