<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * DDE-Mart Admin — Section model (original). Business verticals (food, grocery…).
 * Unifies Firestore `sections` docs into MySQL.
 */
class Section extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'legacy_id', 'name', 'slug', 'service_type', 'color', 'image_path',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class);
    }
}
