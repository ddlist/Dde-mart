<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * DDE-Mart Admin — WalletEntry model (original). Read-only ledger rows.
 * Owner refs stay legacy strings until customer auth resolves them.
 */
class WalletEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id', 'owner_type', 'owner_ref', 'amount', 'kind',
        'method', 'status', 'note', 'order_ref', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'occurred_at' => 'datetime'];
    }
}
