<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * DDE-Mart Admin — ParcelWeight slab (original). Title + delivery charge.
 */
class ParcelWeight extends Model
{
    use HasFactory;

    protected $fillable = ['legacy_id', 'title', 'max_kg', 'delivery_charge', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['max_kg' => 'decimal:2', 'delivery_charge' => 'decimal:2', 'is_active' => 'boolean'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['is_active' => true];
}
