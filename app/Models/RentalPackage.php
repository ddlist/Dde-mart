<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — RentalPackage model (original). Base fare + included
 * allowances + extra rates.
 */
class RentalPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id', 'vehicle_type_id', 'section_id', 'name', 'description',
        'base_fare', 'included_hours', 'included_km', 'extra_km_fare',
        'extra_minute_fare', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_fare' => 'decimal:2',
            'included_hours' => 'decimal:2',
            'included_km' => 'decimal:2',
            'extra_km_fare' => 'decimal:2',
            'extra_minute_fare' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['is_active' => true];

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(RentalVehicleType::class, 'vehicle_type_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
