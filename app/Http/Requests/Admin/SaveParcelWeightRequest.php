<?php

namespace App\Http\Requests\Admin;

/* DDE-Mart Admin — parcel weight + rental master validation (original). */
class SaveParcelWeightRequest extends CatalogRequest
{
    protected string $group = 'transport';

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'max_kg' => ['nullable', 'numeric', 'min:0'],
            'delivery_charge' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
