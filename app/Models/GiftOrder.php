<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — GiftOrder model (original). Issued gift codes with redeem flow.
 */
class GiftOrder extends Model
{
    use HasFactory;

    public const STATUSES = ['active', 'redeemed', 'expired'];

    protected $fillable = [
        'legacy_id', 'gift_id', 'code', 'buyer_ref', 'amount', 'status', 'expires_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'expires_at' => 'datetime'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['status' => 'active'];

    public function gift(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }
}
