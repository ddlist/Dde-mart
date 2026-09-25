<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — PlanSubscription model (original). Plan purchase history.
 */
class PlanSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id', 'subscriber_type', 'subscriber_id', 'subscriber_ref',
        'plan_id', 'amount', 'status', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['subscriber_type' => 'owner', 'status' => 'active'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }
}
