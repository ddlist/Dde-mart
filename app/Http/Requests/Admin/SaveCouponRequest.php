<?php

namespace App\Http\Requests\Admin;

use App\Models\Coupon;
use Illuminate\Validation\Rule;

/* DDE-Mart Admin — coupon validation (original). */
class SaveCouponRequest extends CatalogRequest
{
    protected string $group = 'promotions';

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($this->currentId())],
            'description' => ['nullable', 'string'],
            'discount_type' => ['required', Rule::in(Coupon::TYPES)],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'scope' => ['required', Rule::in(Coupon::SCOPES)],
            'section_id' => ['nullable', 'integer', Rule::exists('sections', 'id')],
            'is_public' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => ['nullable', 'image', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
