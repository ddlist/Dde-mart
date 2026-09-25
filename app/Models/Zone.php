<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * DDE-Mart Admin — Zone model (original). Service-area circles for now;
 * polygon support lands with the geo upgrade (later module).
 */
class Zone extends Model
{
    use HasFactory;

    protected $fillable = ['legacy_id', 'name', 'latitude', 'longitude', 'radius_km', 'is_active'];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'radius_km' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['is_active' => true];

    /** Haversine distance (km) from the zone center to a point. Null without center. */
    public function distanceTo(?float $lat, ?float $lng): ?float
    {
        if ($lat === null || $lng === null || $this->latitude === null || $this->longitude === null) {
            return null;
        }

        $earth = 6371;
        $dLat = deg2rad($lat - (float) $this->latitude);
        $dLng = deg2rad($lng - (float) $this->longitude);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad((float) $this->latitude)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;

        return round(2 * $earth * asin(sqrt($a)), 2);
    }

    public function covers(?float $lat, ?float $lng): bool
    {
        $distance = $this->distanceTo($lat, $lng);

        return $distance !== null && $distance <= (float) $this->radius_km;
    }
}
