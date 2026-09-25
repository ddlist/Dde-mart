<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/* DDE-Mart Admin — rental master validation (original). */
class SaveRentalTypeRequest extends CatalogRequest
{
    protected string $group = 'transport';

    public function rules(): array
    {
        return [
            'section_id' => ['nullable', 'integer', Rule::exists('sections', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150', Rule::unique('rental_vehicle_types', 'slug')->ignore($this->currentId())],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'image', 'max:2048'],
            'remove_icon' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
