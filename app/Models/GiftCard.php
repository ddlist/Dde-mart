<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * DDE-Mart Admin — GiftCard model (original).
 * Adds the missing `amount` (legacy stored none on the card). Purchases arrive
 * with customer accounts (later module).
 */
class GiftCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id', 'title', 'message', 'amount', 'expiry_days', 'image_path', 'is_active',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'is_active' => 'boolean'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['is_active' => true];
}
