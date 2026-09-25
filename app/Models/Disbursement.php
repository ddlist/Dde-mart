<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/*
 * DDE-Mart Admin — Disbursement batch (original). Groups approved payouts into
 * one money movement; marking paid cascades to member payouts.
 */
class Disbursement extends Model
{
    use HasFactory;

    protected $fillable = ['method', 'status', 'note', 'handled_by', 'paid_at'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['method' => 'bank', 'status' => 'pending'];

    public function payouts(): BelongsToMany
    {
        return $this->belongsToMany(PayoutRequest::class, 'disbursement_payout');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function total(): float
    {
        return (float) $this->payouts()->sum('amount');
    }

    public function markPaid(?User $by = null): void
    {
        $this->update(['status' => 'paid', 'handled_by' => $by?->id, 'paid_at' => now()]);

        foreach ($this->payouts()->where('status', 'approved')->get() as $payout) {
            $payout->transition('paid', $by);
        }
    }
}
