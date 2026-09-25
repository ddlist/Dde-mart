<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * DDE-Mart Admin — CatalogAttribute model (original).
 * Named to avoid clashing with PHP 8's built-in #[Attribute]. Table: `attributes`.
 * Unifies Firestore `vendor_attributes` docs into MySQL.
 */
class CatalogAttribute extends Model
{
    use HasFactory, HasSlug;

    protected $table = 'attributes';

    protected $fillable = ['name', 'slug', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id')->orderBy('sort_order');
    }
}
