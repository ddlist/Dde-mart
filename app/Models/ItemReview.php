<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — ItemReview model (original). Moderated product reviews.
 */
class ItemReview extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = [
        'legacy_id', 'product_id', 'store_id', 'author_name', 'rating',
        'comment', 'criteria_scores', 'status', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['criteria_scores' => 'array', 'occurred_at' => 'datetime'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['status' => 'pending'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
