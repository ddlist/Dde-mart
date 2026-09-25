<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — CabType model (original). Cab fare slabs per vehicle class.
 */
class CabType extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'legacy_id', 'section_id', 'name', 'slug', 'capacity', 'description',
        'base_fare', 'per_km_fare', 'min_fare', 'icon_path', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_fare' => 'decimal:2',
            'per_km_fare' => 'decimal:2',
            'min_fare' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['is_active' => true];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
