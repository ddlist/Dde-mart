{{-- DDE-Mart Admin — advertisement form + status moves (original view, UI kit) --}}
<x-admin-layout title="{{ $ad->exists ? 'Manage ad' : 'New ad' }}">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="max-w-2xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="{{ $ad->exists ? 'Manage ad' : 'New ad' }}">
            <div class="space-y-4">
                <x-field label="Title" for="title" :error="$errors->first('title')">
                    <x-input id="title" name="title" required value="{{ old('title', $ad->title) }}" />
                </x-field>
                <x-field label="Description" for="description">
                    <x-textarea id="description" name="description" rows="2">{{ old('description', $ad->description) }}</x-textarea>
                </x-field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Type" for="type">
                        <x-input id="type" name="type" value="{{ old('type', $ad->type ?? 'banner') }}" />
                    </x-field>
                    <x-field label="Priority" for="priority">
                        <x-input id="priority" name="priority" type="number" min="0" value="{{ old('priority', $ad->priority ?? 0) }}" />
                    </x-field>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <span class="label">Cover image</span>
                        @if ($ad->cover_path)
                            <img src="{{ \App\Support\Images::url($ad->cover_path) }}" alt="" class="mb-2 h-20 rounded-xl object-cover">
                            <div class="mb-2"><x-check name="remove_cover" label="Remove" /></div>
                        @endif
                        <input name="cover" type="file" accept="image/*" class="file">
                    </div>
                    <div>
                        <span class="label">Profile image</span>
                        @if ($ad->profile_path)
                            <img src="{{ \App\Support\Images::url($ad->profile_path) }}" alt="" class="mb-2 h-20 w-20 rounded-xl object-cover">
                            <div class="mb-2"><x-check name="remove_profile" label="Remove" /></div>
                        @endif
                        <input name="profile" type="file" accept="image/*" class="file">
                    </div>
                </div>
                <x-field label="Video URL" for="video_url" :error="$errors->first('video_url')">
                    <x-input id="video_url" name="video_url" type="url" value="{{ old('video_url', $ad->video_url) }}" />
                </x-field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Starts" for="starts_at">
                        <x-input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $ad->starts_at?->format('Y-m-d\TH:i')) }}" />
                    </x-field>
                    <x-field label="Ends" for="ends_at">
                        <x-input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', $ad->ends_at?->format('Y-m-d\TH:i')) }}" />
                    </x-field>
                </div>
                <div class="flex flex-wrap gap-5">
                    <x-check name="show_rating" label="Show rating" :checked="old('show_rating', $ad->show_rating ?? true)" />
                    <x-check name="show_review" label="Show reviews" :checked="old('show_review', $ad->show_review ?? true)" />
                    <x-check name="payment_status" value="paid" label="Paid" :checked="old('payment_status', $ad->payment_status ?? 'pending') === 'paid'" />
                </div>
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $ad->exists ? 'Save changes' : 'Create ad' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.ads.index') }}">Cancel</x-btn>
        </div>
    </form>

    @if ($ad->exists && auth()->user()->canAccess('promotions', 'edit'))
        <x-card title="Status: {{ ucfirst($ad->status) }}" sub="Only legal moves are shown." class="mt-5 max-w-2xl">
            <div class="flex flex-wrap gap-2">
                @forelse (\App\Models\Advertisement::TRANSITIONS[$ad->status] ?? [] as $next)
                    <form method="POST" action="{{ route('admin.ads.transition', $ad) }}">
                        @csrf
                        <input type="hidden" name="to" value="{{ $next }}">
                        <x-btn variant="dark">Move to {{ $next }}</x-btn>
                    </form>
                @empty
                    <p class="text-sm text-slate-400">Terminal state.</p>
                @endforelse
            </div>
        </x-card>
    @endif
</x-admin-layout>
