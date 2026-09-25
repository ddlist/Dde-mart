<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * DDE-Mart Admin — Brand model (original).
 * Unifies Firestore `brands` docs into MySQL.
 */
class Brand extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = ['legacy_id', 'section_id', 'name', 'slug', 'image_path', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
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
