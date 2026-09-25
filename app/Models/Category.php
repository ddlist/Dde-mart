<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * DDE-Mart Admin — Category model (original).
 * Unifies Firestore `vendor_categories` docs into MySQL.
 */
class Category extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'legacy_id', 'section_id', 'name', 'slug', 'description', 'image_path',
        'sort_order', 'show_in_homepage', 'is_active',
    ];

    protected function casts(): array
    {
        return ['show_in_homepage' => 'boolean', 'is_active' => 'boolean'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
