<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/* DDE-Mart Admin — parcel master validation (original, transport group). */
class SaveParcelCategoryRequest extends CatalogRequest
{
    protected string $group = 'transport';

    public function rules(): array
    {
        return [
            'section_id' => ['nullable', 'integer', Rule::exists('sections', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150', Rule::unique('parcel_categories', 'slug')->ignore($this->currentId())],
            'image' => ['nullable', 'image', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
