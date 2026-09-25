<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/* DDE-Mart Admin — store validation (original). */
class SaveStoreRequest extends CatalogRequest
{
    protected string $group = 'stores';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('stores', 'slug')->ignore($this->currentId())],
            'description' => ['nullable', 'string'],
            'owner_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
            'section_id' => ['nullable', 'integer', Rule::exists('sections', 'id')],
            'zone_id' => ['nullable', 'integer', Rule::exists('zones', 'id')],
            'owner_id' => ['nullable', 'integer', Rule::exists('owners', 'id')],
            'commission_type' => ['required', 'in:percentage,fixed'],
            'commission_value' => ['required', 'numeric', 'min:0'],
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'delivery_per_km' => ['nullable', 'numeric', 'min:0'],
            'is_open' => ['nullable', 'boolean'],
            'self_delivery' => ['nullable', 'boolean'],
            'subscription_plan_id' => ['nullable', 'integer', Rule::exists('subscription_plans', 'id')],
        ];
    }
}
