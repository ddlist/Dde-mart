<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/* DDE-Mart Admin — rental package validation (original). */
class SaveRentalPackageRequest extends CatalogRequest
{
    protected string $group = 'transport';

    public function rules(): array
    {
        return [
            'vehicle_type_id' => ['nullable', 'integer', Rule::exists('rental_vehicle_types', 'id')],
            'section_id' => ['nullable', 'integer', Rule::exists('sections', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'base_fare' => ['required', 'numeric', 'min:0'],
            'included_hours' => ['nullable', 'numeric', 'min:0'],
            'included_km' => ['nullable', 'numeric', 'min:0'],
            'extra_km_fare' => ['nullable', 'numeric', 'min:0'],
            'extra_minute_fare' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
