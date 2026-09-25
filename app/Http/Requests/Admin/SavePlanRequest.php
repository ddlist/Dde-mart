<?php

namespace App\Http\Requests\Admin;

use App\Models\SubscriptionPlan;
use Illuminate\Validation\Rule;

/* DDE-Mart Admin — subscription plan validation (original). */
class SavePlanRequest extends CatalogRequest
{
    protected string $group = 'finance';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:free,paid'],
            'price' => ['required', 'numeric', 'min:0'],
            'validity_days' => ['required', 'integer', 'min:1'],
            'item_limit' => ['nullable', 'integer', 'min:-1'],
            'order_limit' => ['nullable', 'integer', 'min:-1'],
            'is_commission_plan' => ['nullable', 'boolean'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', Rule::in(SubscriptionPlan::FEATURES)],
            'section_id' => ['nullable', 'integer', Rule::exists('sections', 'id')],
            'image' => ['nullable', 'image', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
