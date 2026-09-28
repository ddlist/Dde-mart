<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — store special-offer timeslot (original).
 * Day-scoped discount windows; the global special-offer settings flag
 * gates whether storefronts advertise them.
 */
class StoreOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id', 'day', 'opens_at', 'closes_at', 'discount', 'discount_type',
    ];

    protected function casts(): array
    {
        return ['day' => 'integer', 'discount' => 'decimal:2'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
