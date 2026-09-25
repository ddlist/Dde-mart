<?php

namespace App\Http\Requests\Admin;

/* DDE-Mart Admin — attribute + inline values validation (original). */
class SaveAttributeRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', ...$this->uniqueOn('attributes', $this->currentId())],
            'is_active' => ['nullable', 'boolean'],
            'values' => ['nullable', 'array', 'max:50'],
            'values.*.id' => ['nullable', 'integer', 'exists:attribute_values,id'],
            // Blank rows are ignored by the controller.
            'values.*.value' => ['nullable', 'string', 'max:100'],
        ];
    }
}
