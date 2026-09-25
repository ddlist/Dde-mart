<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * DDE-Mart Admin — RentalVehicleType model (original).
 */
class RentalVehicleType extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'legacy_id', 'section_id', 'name', 'slug', 'capacity',
        'description', 'icon_path', 'is_active',
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

    public function packages(): HasMany
    {
        return $this->hasMany(RentalPackage::class, 'vehicle_type_id');
    }
}
