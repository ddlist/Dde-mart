{{-- DDE-Mart Admin — product detail (original view, UI kit) --}}
<x-admin-layout title="Product">
    <x-page-head :title="$product->name" sub="Read-only catalog record.">
        <x-slot:action>
            @if (auth()->user()->canAccess('catalog', 'edit'))
                <x-btn href="{{ route('admin.products.edit', $product) }}">Edit</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-card title="Basics">
            <dl class="space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Price</dt><dd>{{ number_format($product->price, 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Discount</dt><dd>{{ $product->discount_price ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Stock</dt><dd>{{ $product->quantity }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Section / Category</dt><dd>{{ $product->section?->name ?? '—' }} / {{ $product->category?->name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Brand</dt><dd>{{ $product->brand?->name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Veg / Takeaway / Active</dt><dd>{{ $product->veg ? 'veg' : 'non-veg' }} / {{ $product->is_takeaway ? 'yes' : 'no' }} / {{ $product->is_active ? 'yes' : 'no' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Nutrition</dt><dd>{{ $product->calories ?? '—' }} cal · {{ $product->proteins ?? '—' }} protein · {{ $product->fats ?? '—' }} fat · {{ $product->grams ?? '—' }}g</dd></div>
            </dl>
            @if ($product->description)
                <div class="mt-3 text-sm text-slate-600">{!! $product->description !!}</div>
            @endif
        </x-card>

        <x-card title="Variants">
            @if ($product->attributeValues->isEmpty())
                <x-empty message="No variants linked." />
            @else
                <ul class="space-y-1 text-sm">
                    @foreach ($product->attributeValues as $value)
                        <li class="flex justify-between gap-2">
                            <span>{{ $value->attribute?->name }}: <strong>{{ $value->value }}</strong></span>
                            <span class="text-slate-500">
                                @if ($value->pivot->price_delta !== null)+{{ number_format($value->pivot->price_delta, 2) }}@endif
                                @if ($value->pivot->quantity !== null) · stock {{ $value->pivot->quantity }}@endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <x-card title="Add-ons">
            @if ($product->addons->isEmpty())
                <x-empty message="No add-ons." />
            @else
                <ul class="space-y-1 text-sm">
                    @foreach ($product->addons as $addon)
                        <li class="flex justify-between"><span>{{ $addon->name }}</span><span>{{ number_format($addon->price, 2) }}</span></li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card title="Specifications">
            @if (empty($product->specs))
                <x-empty message="No specifications." />
            @else
                <dl class="space-y-1 text-sm">
                    @foreach ($product->specs as $spec)
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ $spec['label'] }}</dt><dd>{{ $spec['value'] }}</dd></div>
                    @endforeach
                </dl>
            @endif
        </x-card>
    </div>
</x-admin-layout>
