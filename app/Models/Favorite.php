<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart storefront — Favorite model (original). Customer hearts.
 */
class Favorite extends Model
{
    use HasFactory;

    public const TYPES = ['product', 'store'];

    protected $fillable = ['customer_id', 'favorite_type', 'favorite_id'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
