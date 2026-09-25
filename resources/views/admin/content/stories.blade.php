{{-- DDE-Mart Admin — stories moderation (original view, UI kit) --}}
<x-admin-layout title="Stories">
    <x-page-head title="Stories" sub="Vendor story videos." />

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.stories.index') }}" class="flex gap-2">
            <x-select name="status" onchange="this.form.submit()">
                <option value="">All</option>
                @foreach (['active', 'removed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($stories as $story)
            <x-card>
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-bold">{{ $story->store?->name ?? 'Unknown store' }}</p>
                    <span @class(['badge', 'badge-green' => $story->status === 'active', 'badge-slate' => $story->status !== 'active'])>{{ $story->status }}</span>
                </div>
                @if ($story->thumbnail)
                    <img src="{{ \App\Support\Images::url($story->thumbnail) }}" alt="" class="mt-2 h-32 w-full rounded-xl object-cover">
                @endif
                <div class="mt-3 flex gap-2">
                    @if ($story->video_url)
                        <x-btn variant="row" href="{{ $story->video_url }}">Video</x-btn>
                    @endif
                    @if (auth()->user()->canAccess('content', 'edit'))
                        <form method="POST" action="{{ route('admin.stories.toggle', $story) }}" class="inline">
                            @csrf
                            <x-btn variant="row">{{ $story->status === 'active' ? 'Remove' : 'Restore' }}</x-btn>
                        </form>
                        <form method="POST" action="{{ route('admin.stories.destroy', $story) }}"
                              onsubmit="return confirm('Delete this story?')" class="inline">
                            @csrf @method('DELETE')
                            <x-btn variant="row-danger">Delete</x-btn>
                        </form>
                    @endif
                </div>
            </x-card>
        @empty
            <div class="col-span-full"><x-empty message="No stories." /></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $stories->links() }}</div>
</x-admin-layout>
