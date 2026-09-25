<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/* DDE-Mart Admin — category validation (original). */
class SaveCategoryRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'section_id' => ['nullable', 'integer', Rule::exists('sections', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', ...$this->uniqueOn('categories', $this->currentId())],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'show_in_homepage' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
