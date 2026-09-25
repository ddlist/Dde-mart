{{-- DDE-Mart Admin — onboarding slides (original view, UI kit) --}}
<x-admin-layout title="Onboarding">
    <x-page-head title="Onboarding" sub="App intro slides per audience." />

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card title="Add slide">
            <form method="POST" action="{{ route('admin.slides.store') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <x-field label="Title" for="sl-title">
                    <x-input id="sl-title" name="title" required />
                </x-field>
                <x-field label="Description" for="sl-desc">
                    <x-textarea id="sl-desc" name="description" rows="2" />
                </x-field>
                <x-field label="Audience" for="sl-aud">
                    <x-select id="sl-aud" name="audience">
                        @foreach (['customer', 'vendor', 'driver', 'provider', 'worker'] as $audience)
                            <option value="{{ $audience }}">{{ ucfirst($audience) }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Order" for="sl-order">
                    <x-input id="sl-order" name="sort_order" type="number" min="0" value="0" />
                </x-field>
                <x-field label="Image" for="sl-image">
                    <input id="sl-image" name="image" type="file" accept="image/*" class="file">
                </x-field>
                <x-btn>Add slide</x-btn>
            </form>
        </x-card>

        <div class="grid content-start gap-4 sm:grid-cols-2 lg:col-span-2">
            @forelse ($slides as $slide)
                <x-card>
                    @if ($slide->image_path)
                        <img src="{{ \App\Support\Images::url($slide->image_path) }}" alt="" class="h-32 w-full rounded-xl object-cover">
                    @endif
                    <p class="mt-2 text-sm font-bold">{{ $slide->title }}</p>
                    <p class="text-xs text-slate-500">{{ $slide->audience }} · order {{ $slide->sort_order }}</p>
                    <div class="mt-2">
                        <form method="POST" action="{{ route('admin.slides.destroy', $slide) }}"
                              onsubmit="return confirm('Delete slide?')">
                            @csrf @method('DELETE')
                            <x-btn variant="row-danger">Delete</x-btn>
                        </form>
                    </div>
                </x-card>
            @empty
                <div class="col-span-full"><x-empty message="No slides." /></div>
            @endforelse
        </div>
    </div>
    <div class="mt-4">{{ $slides->links() }}</div>
</x-admin-layout>
