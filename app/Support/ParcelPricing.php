<?php

namespace App\Support;

use App\Models\ParcelWeight;
use App\Models\Setting;

/*
 * DDE-Mart — parcel pricing (original support).
 * Single formula shared by shop + API quote/book: weight slab charge plus
 * per-km rate, floored at the configured delivery minimum.
 */
class ParcelPricing
{
    public static function quote(ParcelWeight $weight, float $km): float
    {
        $perKm = (float) Setting::get('parcel_per_km', 2);
        $floor = (float) Setting::get('delivery_min', 0);

        return round(max($floor, $weight->delivery_charge + $km * $perKm), 2);
    }
}
