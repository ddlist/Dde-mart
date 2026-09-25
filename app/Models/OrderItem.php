<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — OrderItem model (original). Snapshot rows: names/prices/extras
 * are frozen at purchase time so later catalog edits never rewrite history.
 */
class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'product_id', 'name', 'price', 'quantity', 'extras', 'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'extras' => 'array',
            'price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function extrasTotal(): float
    {
        return (float) collect($this->extras ?? [])->sum('price');
    }
}
