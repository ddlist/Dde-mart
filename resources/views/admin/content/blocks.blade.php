{{-- DDE-Mart Admin — homepage/footer content blocks (original view, UI kit) --}}
<x-admin-layout title="Content Blocks">
    <x-page-head title="Content Blocks" sub="Keyed homepage and footer slots for the storefront." />

    <div class="max-w-3xl space-y-4">
        @foreach ($blocks as $block)
            <x-card title="{{ $block->title }}" sub="key: {{ $block->key }}">
                <form method="POST" action="{{ route('admin.blocks.update', $block) }}" class="space-y-3">
                    @csrf @method('PUT')
                    <x-field label="Title" for="title-{{ $block->id }}">
                        <x-input id="title-{{ $block->id }}" name="title" required value="{{ $block->title }}" />
                    </x-field>
                    <x-field label="Body" for="body-{{ $block->id }}">
                        <x-textarea id="body-{{ $block->id }}" name="body" rows="4">{{ $block->body }}</x-textarea>
                    </x-field>
                    <div class="flex items-center gap-2">
                        <x-check name="is_active" label="Active" :checked="$block->is_active" />
                        <x-btn>Save</x-btn>
                    </div>
                </form>
            </x-card>
        @endforeach

        @if ($blocks->isEmpty())
            <x-empty message="No blocks yet — seed defaults from the seeder." />
        @endif
    </div>
</x-admin-layout>
