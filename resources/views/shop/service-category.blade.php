{{-- DDE-Mart storefront — service category + booking (original view) --}}
<x-store-layout title="{{ $category->title }}">
    <h1 class="mb-4 text-xl font-black tracking-tight">{{ $category->title }}</h1>

    @if ($services->isEmpty())
        <x-empty message="No services here yet." />
    @else
        <div class="grid gap-3 lg:grid-cols-2">
            @foreach ($services as $service)
                <x-card title="{{ $service->title }}" sub="{{ $service->provider?->name ?? '' }}">
                    <p class="text-2xl font-black">{{ number_format($service->price, 2) }}
                        <span class="text-xs font-normal text-slate-400">{{ $service->price_unit ?? '' }}</span>
                    </p>
                    @if ($service->description)
                        <p class="mt-1 text-sm text-slate-500">{{ $service->description }}</p>
                    @endif
                    <form method="POST" action="{{ route('shop.services.book') }}" class="mt-3 grid grid-cols-2 gap-2">
                        @csrf
                        <input type="hidden" name="service_id" value="{{ $service->id }}">
                        <x-input name="address" required placeholder="Service address" class="col-span-2" />
                        <x-input name="scheduled_at" type="datetime-local" required />
                        <x-btn>Book</x-btn>
                    </form>
                </x-card>
            @endforeach
        </div>
        <div class="mt-4">{{ $services->links() }}</div>
    @endif
</x-store-layout>
