{{-- DDE-Mart Admin — chat thread (original view, UI kit, read-only) --}}
<x-admin-layout title="Thread #{{ $thread->id }}">
    <div class="mb-4">
        <a href="{{ route('admin.chats.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-800">
            <x-icon name="back" class="h-4 w-4" /> All threads
        </a>
    </div>

    <x-card title="Thread #{{ $thread->id }} · {{ $thread->audience }}" class="mx-auto max-w-2xl">
        <div class="space-y-2">
            @forelse ($thread->messages as $message)
                <div class="max-w-[80%] rounded-2xl px-3 py-2 text-sm {{ $message->sender_ref === 'admin' ? 'ml-auto bg-emerald-600 text-white' : 'bg-slate-100' }}">
                    <p>{{ $message->body }}</p>
                    <p class="mt-0.5 text-[10px] opacity-70">{{ $message->sent_at?->format('d M H:i') }}</p>
                </div>
            @empty
                <x-empty message="No messages." />
            @endforelse
        </div>

        @if ($thread->status === 'open' && auth()->user()->canAccess('content', 'edit'))
            <form method="POST" action="{{ route('admin.chats.close', $thread) }}" class="mt-4">
                @csrf
                <x-btn variant="ghost">Close thread</x-btn>
            </form>
        @endif
    </x-card>
</x-admin-layout>
