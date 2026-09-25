{{-- DDE-Mart storefront — services browse (original view) --}}
<x-store-layout title="Services">
    <h1 class="mb-4 text-xl font-black tracking-tight">Home services</h1>

    @if ($categories->isEmpty())
        <x-empty message="No service categories yet." />
    @else
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($categories as $category)
                <a href="{{ route('shop.services.category', $category) }}"
                   class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md">
                    <p class="font-bold">{{ $category->title }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $category->children->pluck('title')->join(' · ') }}</p>
                </a>
            @endforeach
        </div>
    @endif
</x-store-layout>
