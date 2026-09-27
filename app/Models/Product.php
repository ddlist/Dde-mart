<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * DDE-Mart Admin — Product model (original).
 * Unifies Firestore `vendor_products` docs into MySQL. `vendor_id` stays a plain
 * column until the Vendors module adds the FK. Review aggregates are computed,
 * not stored (legacy stored reviewsCount/reviewsSum).
 */
class Product extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'legacy_id', 'legacy_ref', 'section_id', 'category_id', 'brand_id', 'vendor_id',
        'name', 'slug', 'description', 'price', 'discount_price', 'quantity',
        'veg', 'is_takeaway', 'calories', 'proteins', 'fats', 'grams',
        'image_path', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'veg' => 'boolean',
            'is_takeaway' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'vendor_id');
    }

    public function addons(): HasMany
    {
        return $this->hasMany(ProductAddon::class)->orderBy('sort_order');
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_attribute_value');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ItemReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', 'approved');
    }

    /** Effective selling price (discount wins when positive and lower).
     * Imported rows use discount_price = 0 for "no discount" — that must
     * never zero out the price (it did: quote/checkout totals came out 0).
     */
    public function sellingPrice(): float
    {
        if ($this->discount_price !== null
            && (float) $this->discount_price > 0
            && $this->discount_price < $this->price) {
            return (float) $this->discount_price;
        }

        return (float) $this->price;
    }
}
