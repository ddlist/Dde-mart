<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * DDE-Mart Admin — SosAlert model (original). Safety alerts, resolve-only.
 */
class SosAlert extends Model
{
    use HasFactory;

    protected $fillable = ['legacy_id', 'order_ref', 'latitude', 'longitude', 'status', 'occurred_at'];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'occurred_at' => 'datetime',
        ];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['status' => 'open'];
}
