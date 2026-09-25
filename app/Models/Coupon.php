<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — Coupon model (original).
 * Unifies coupons/parcel_coupons/rental_coupons/providers_coupons/promos via `scope`.
 */
class Coupon extends Model
{
    use HasFactory;

    public const SCOPES = ['all', 'food', 'parcel', 'rental'];

    public const TYPES = ['percentage', 'fixed'];

    protected $fillable = [
        'legacy_id', 'code', 'description', 'discount_type', 'discount_value',
        'min_order', 'max_discount', 'usage_limit', 'used_count',
        'scope', 'section_id', 'vendor_id', 'is_public',
        'starts_at', 'expires_at', 'image_path', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'min_order' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = [
        'discount_type' => 'percentage',
        'scope' => 'all',
        'is_public' => true,
        'is_active' => true,
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function isUsable(float $subtotal = 0): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        if ($subtotal < (float) $this->min_order) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        return ! ($this->expires_at && $now->gt($this->expires_at));
    }

    /** Discount amount for a subtotal, honoring type + max cap. */
    public function calculateDiscount(float $subtotal): float
    {
        if (! $this->isUsable($subtotal)) {
            return 0;
        }

        $amount = $this->discount_type === 'percentage'
            ? $subtotal * ((float) $this->discount_value / 100)
            : (float) $this->discount_value;

        if ($this->max_discount !== null) {
            $amount = min($amount, (float) $this->max_discount);
        }

        return round(min($amount, $subtotal), 2);
    }
}
