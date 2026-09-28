{{-- DDE-Mart Admin — product form (original view, UI kit) --}}
<x-admin-layout title="{{ $product->exists ? 'Edit product' : 'New product' }}">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="max-w-3xl space-y-5">
        @csrf @if ($method !== 'POST') @method($method) @endif

        <x-card title="Basics">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-field label="Section" for="section_id">
                    <x-select id="section_id" name="section_id">
                        <option value="">—</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected((string) old('section_id', $product->section_id) === (string) $section->id)>{{ $section->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Category" for="category_id">
                    <x-select id="category_id" name="category_id">
                        <option value="">—</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Brand" for="brand_id">
                    <x-select id="brand_id" name="brand_id">
                        <option value="">—</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product->brand_id) === (string) $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Store" for="vendor_id">
                    <x-select id="vendor_id" name="vendor_id">
                        <option value="">—</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" @selected((string) old('vendor_id', $product->vendor_id) === (string) $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
            </div>
            <div class="mt-4 space-y-4">
                <x-field label="Name" for="name" :error="$errors->first('name')">
                    <x-input id="name" name="name" required value="{{ old('name', $product->name) }}" />
                </x-field>
                <x-field label="Slug (blank = auto)" for="slug">
                    <x-input id="slug" name="slug" value="{{ old('slug', $product->slug) }}" />
                </x-field>
                <x-field label="Description" for="description">
                    <x-textarea id="description" name="description">{{ old('description', $product->description) }}</x-textarea>
                </x-field>
                @include('admin.catalog.partials.image-field', ['model' => $product])
            </div>
        </x-card>

        <x-card title="Pricing & stock">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field label="Price" for="price" :error="$errors->first('price')">
                    <x-input id="price" name="price" type="number" step="0.01" min="0" required value="{{ old('price', $product->price) }}" />
                </x-field>
                <x-field label="Discount price" for="discount_price" :error="$errors->first('discount_price')">
                    <x-input id="discount_price" name="discount_price" type="number" step="0.01" min="0" value="{{ old('discount_price', $product->discount_price) }}" />
                </x-field>
                <x-field label="Stock" for="quantity">
                    <x-input id="quantity" name="quantity" type="number" min="0" value="{{ old('quantity', $product->quantity ?? 0) }}" />
                </x-field>
            </div>
            <div class="mt-4 flex flex-wrap gap-5">
                <x-check name="veg" label="Veg" :checked="old('veg', $product->veg ?? true)" />
                <x-check name="is_takeaway" label="Takeaway" :checked="old('is_takeaway', $product->is_takeaway ?? false)" />
                <x-check name="is_active" label="Active" :checked="old('is_active', $product->is_active ?? true)" />
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-4">
                @foreach (['calories' => 'Calories', 'proteins' => 'Proteins', 'fats' => 'Fats', 'grams' => 'Grams'] as $field => $label)
                    <x-field :label="$label" :for="$field">
                        <x-input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $product->{$field}) }}" />
                    </x-field>
                @endforeach
            </div>
        </x-card>

        <x-card title="Attributes & variants" sub="Manage axes under Catalog → Attributes. Optional per-value price delta and stock.">
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($attributes as $attribute)
                    <fieldset class="rounded-xl border border-slate-200 p-3">
                        <legend class="px-1 text-xs font-bold uppercase tracking-wider text-slate-500">{{ $attribute->name }}</legend>
                        <div class="space-y-2">
                            @foreach ($attribute->values as $value)
                                @php($pivot = $product->attributeValues->firstWhere('id', $value->id)?->pivot)
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-check name="attributes[]" :value="$value->id" :label="$value->value"
                                        :checked="in_array($value->id, old('attributes', $product->attributeValues->pluck('id')->all() ?? []))" />
                                    <x-input name="variants[{{ $value->id }}][price]" type="number" step="0.01" min="0"
                                        value="{{ old('variants.'.$value->id.'.price', $pivot?->price_delta) }}" placeholder="+ price" class="w-24" />
                                    <x-input name="variants[{{ $value->id }}][quantity]" type="number" step="1" min="0"
                                        value="{{ old('variants.'.$value->id.'.quantity', $pivot?->quantity) }}" placeholder="qty" class="w-20" />
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            </div>
        </x-card>

        <x-card title="Specifications">
            <x-slot:action>
                <x-btn variant="row" type="button" id="add-spec">+ Add row</x-btn>
            </x-slot:action>
            <div id="specs" class="space-y-2">
                @foreach (old('specs', $product->specs ?? []) as $i => $spec)
                    <div class="flex gap-2">
                        <x-input name="specs[{{ $i }}][label]" value="{{ $spec['label'] ?? '' }}" placeholder="Label" class="flex-1" />
                        <x-input name="specs[{{ $i }}][value]" value="{{ $spec['value'] ?? '' }}" placeholder="Value" class="flex-1" />
                        <x-btn variant="row-danger" type="button" onclick="this.parentElement.remove()">✕</x-btn>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card title="Add-ons">
            <x-slot:action>
                <x-btn variant="row" type="button" id="add-addon">+ Add add-on</x-btn>
            </x-slot:action>
            <div id="addons" class="space-y-2">
                @foreach (old('addons', $product->addons->map(fn ($a) => ['name' => $a->name, 'price' => $a->price])->all() ?? []) as $i => $addon)
                    <div class="flex gap-2">
                        <x-input name="addons[{{ $i }}][name]" value="{{ $addon['name'] ?? '' }}" placeholder="e.g. Extra cheese" class="flex-1" />
                        <x-input name="addons[{{ $i }}][price]" type="number" step="0.01" min="0" value="{{ $addon['price'] ?? '' }}" placeholder="0.00" class="w-28" />
                        <x-btn variant="row-danger" type="button" onclick="this.parentElement.remove()">✕</x-btn>
                    </div>
                @endforeach
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn>{{ $product->exists ? 'Save changes' : 'Create product' }}</x-btn>
            <x-btn variant="ghost" href="{{ route('admin.products.index') }}">Cancel</x-btn>
        </div>
    </form>

    <script>
        document.getElementById('add-addon').addEventListener('click', () => {
            const list = document.getElementById('addons');
            const i = list.children.length + Date.now();
            const row = document.createElement('div');
            row.className = 'flex gap-2';
            row.innerHTML = `<input name="addons[${i}][name]" type="text" placeholder="e.g. Extra cheese" class="input flex-1">
                <input name="addons[${i}][price]" type="number" step="0.01" min="0" placeholder="0.00" class="input w-28">
                <button type="button" class="btn-danger-outline">✕</button>`;
            row.querySelector('button').addEventListener('click', () => row.remove());
            list.appendChild(row);
        });
        document.getElementById('add-spec').addEventListener('click', () => {
            const list = document.getElementById('specs');
            const i = list.children.length + Date.now();
            const row = document.createElement('div');
            row.className = 'flex gap-2';
            row.innerHTML = `<input name="specs[${i}][label]" type="text" placeholder="Label" class="input flex-1">
                <input name="specs[${i}][value]" type="text" placeholder="Value" class="input flex-1">
                <button type="button" class="btn-danger-outline">✕</button>`;
            row.querySelector('button').addEventListener('click', () => row.remove());
            list.appendChild(row);
        });
    </script>
</x-admin-layout>
