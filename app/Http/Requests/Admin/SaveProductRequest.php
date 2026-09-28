<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/* DDE-Mart Admin — product validation (original). */
class SaveProductRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'section_id' => ['nullable', 'integer', Rule::exists('sections', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')],
            'vendor_id' => ['nullable', 'integer', Rule::exists('stores', 'id')],
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', ...$this->uniqueOn('products', $this->currentId())],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'veg' => ['nullable', 'boolean'],
            'is_takeaway' => ['nullable', 'boolean'],
            'calories' => ['nullable', 'string', 'max:50'],
            'proteins' => ['nullable', 'string', 'max:50'],
            'fats' => ['nullable', 'string', 'max:50'],
            'grams' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'attributes' => ['nullable', 'array'],
            'attributes.*' => ['integer', 'exists:attribute_values,id'],
            'variants' => ['nullable', 'array'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.quantity' => ['nullable', 'integer', 'min:0'],
            'specs' => ['nullable', 'array', 'max:20'],
            'specs.*.label' => ['nullable', 'string', 'max:100', 'required_with:specs.*.value'],
            'specs.*.value' => ['nullable', 'string', 'max:500'],
            'addons' => ['nullable', 'array', 'max:30'],
            // Blank rows are ignored by the controller; a priced row needs a name.
            'addons.*.name' => ['nullable', 'string', 'max:100', 'required_with:addons.*.price'],
            'addons.*.price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
