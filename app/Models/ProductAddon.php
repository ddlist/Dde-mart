<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — ProductAddon model (original).
 * Replaces legacy parallel `addOnsTitle[]/addOnsPrice[]` arrays with real rows.
 */
class ProductAddon extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'name', 'price', 'sort_order'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
