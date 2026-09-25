<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * DDE-Mart Admin — Complaint model (original).
 */
class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id', 'title', 'description', 'customer_name', 'driver_name',
        'order_ref', 'status', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['status' => 'open'];
}
