<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — ParcelCategory model (original).
 */
class ParcelCategory extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'legacy_id', 'section_id', 'name', 'slug', 'image_path', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['is_active' => true];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
