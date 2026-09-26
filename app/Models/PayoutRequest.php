<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use InvalidArgumentException;

/*
 * DDE-Mart Admin — PayoutRequest model (original).
 * Unifies vendor/driver/provider/owner payouts + legacy payout-request docs.
 * pending → approved → paid, or pending → rejected. No deletes (money trail).
 * Live gateway execution is deferred to D8c (needs real credentials).
 */
class PayoutRequest extends Model
{
    use HasFactory;

    public const REQUESTERS = ['vendor', 'driver', 'provider', 'owner', 'worker'];

    public const METHODS = ['bank', 'paypal', 'stripe', 'razorpay', 'flutterwave', 'cash'];

    public const TRANSITIONS = [
        'pending' => ['approved', 'rejected'],
        'approved' => ['paid'],
        'rejected' => [],
        'paid' => [],
    ];

    protected $fillable = [
        'requester_type', 'requester_id', 'requester_name',
        'amount', 'method', 'method_details',
        'status', 'admin_note', 'handled_by', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method_details' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['method' => 'bank', 'status' => 'pending'];

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function disbursements(): BelongsToMany
    {
        return $this->belongsToMany(Disbursement::class, 'disbursement_payout');
    }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, static::TRANSITIONS[$this->status] ?? [], true);
    }

    public function transition(string $to, ?User $by = null, ?string $note = null): static
    {
        if (! $this->canTransitionTo($to)) {
            throw new InvalidArgumentException(
                "Cannot move payout #{$this->id} from [{$this->status}] to [{$to}]."
            );
        }

        $this->status = $to;
        $this->handled_by = $by?->id;
        $this->admin_note = $note ?? $this->admin_note;

        if ($to === 'paid') {
            $this->paid_at = now();
        }

        $this->save();

        return $this;
    }
}
