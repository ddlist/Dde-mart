<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — ProviderService model (original). Bookable services.
 */
class ProviderService extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id', 'provider_id', 'category_id', 'title', 'description',
        'price', 'discount_price', 'price_unit', 'image_path', 'is_active',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'discount_price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['is_active' => true];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProviderCategory::class, 'category_id');
    }
}
