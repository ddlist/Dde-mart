{{-- DDE-Mart Admin — products list (original view, UI kit) --}}
<x-admin-layout title="Products">
    <x-page-head title="Products" sub="Sellable items across stores.">
        <x-slot:action>
            @if (auth()->user()->canAccess('catalog', 'create'))
                <x-btn href="{{ route('admin.products.create') }}"><x-icon name="plus" class="h-4 w-4" /> New product</x-btn>
            @endif
        </x-slot:action>
    </x-page-head>

    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.products.index') }}" class="flex flex-wrap gap-2">
            <x-input name="search" value="{{ request('search') }}" placeholder="Search products…" class="min-w-52 flex-1" />
            <x-select name="section" onchange="this.form.submit()">
                <option value="">All sections</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected((string) request('section') === (string) $section->id)>{{ $section->name }}</option>
                @endforeach
            </x-select>
            <x-select name="category" onchange="this.form.submit()">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </x-select>
            <x-select name="brand" onchange="this.form.submit()">
                <option value="">All brands</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->id }}" @selected((string) request('brand') === (string) $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </x-select>
            <x-btn variant="dark">Filter</x-btn>
        </form>
    </x-card>

    <div class="table-card">
        <table class="min-w-full">
            <thead class="thead">
                <tr><th class="th">Product</th><th class="th">Category / Brand</th><th class="th">Price</th><th class="th">Stock</th><th class="th">Status</th><th class="th text-right">Actions</th></tr>
            </thead>
            <tbody class="tbody-row">
                @forelse ($products as $product)
                    <tr>
                        <td class="td">
                            <div class="flex items-center gap-3">
                                @if ($product->image_path)
                                    <img src="{{ \App\Support\Images::url($product->image_path) }}" alt="" class="h-10 w-10 rounded-xl object-cover">
                                @endif
                                <div>
                                    <p class="font-semibold">{{ $product->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $product->veg ? 'veg' : 'non-veg' }}
                                        @if ($product->is_takeaway) · takeaway @endif</p>
                                </div>
                            </div>
                        </td>
                        <td class="td text-xs text-slate-500">{{ $product->category?->name ?? '—' }} / {{ $product->brand?->name ?? '—' }}</td>
                        <td class="td">
                            <span class="font-semibold">{{ $product->sellingPrice() }}</span>
                            @if ($product->discount_price !== null && $product->discount_price < $product->price)
                                <span class="text-xs text-slate-400 line-through">{{ $product->price }}</span>
                            @endif
                        </td>
                        <td class="td text-slate-500">{{ $product->quantity }}</td>
                        <td class="td"><x-status-pill :active="$product->is_active" /></td>
                        <td class="td">
                            <div class="flex justify-end gap-2">
                                @if (auth()->user()->canAccess('catalog', 'edit'))
                                    <x-btn variant="row" href="{{ route('admin.products.edit', $product) }}">Edit</x-btn>
                                @endif
                                @if (auth()->user()->canAccess('catalog', 'delete'))
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                                          onsubmit="return confirm('Delete product {{ $product->name }}?')">
                                        @csrf @method('DELETE')
                                        <x-btn variant="row-danger">Delete</x-btn>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td"><x-empty message="No products yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $products->links() }}</div>
</x-admin-layout>
