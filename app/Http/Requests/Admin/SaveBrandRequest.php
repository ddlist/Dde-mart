<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/* DDE-Mart Admin — brand validation (original). */
class SaveBrandRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'section_id' => ['nullable', 'integer', Rule::exists('sections', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', ...$this->uniqueOn('brands', $this->currentId())],
            'image' => ['nullable', 'image', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
