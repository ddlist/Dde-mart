<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * DDE-Mart Admin — Ride model (original).
 * Machine: placed → accepted → ongoing → completed (+cancelled/rejected).
 */
class Ride extends Model
{
    use HasFactory;

    public const STATUSES = [
        'placed' => 'Placed', 'accepted' => 'Accepted', 'ongoing' => 'Ongoing',
        'completed' => 'Completed', 'cancelled' => 'Cancelled', 'rejected' => 'Rejected',
    ];

    public const TRANSITIONS = [
        'placed' => ['accepted', 'rejected', 'cancelled'],
        'accepted' => ['ongoing', 'cancelled'],
        'ongoing' => ['completed'],
        'completed' => [],
        'cancelled' => [],
        'rejected' => [],
    ];

    protected $fillable = [
        'number', 'legacy_id', 'customer_name', 'customer_phone', 'driver_id',
        'cab_type_id', 'source', 'destination', 'distance_km', 'subtotal',
        'discount', 'tip', 'total', 'payment_method', 'booking_at',
        'started_at', 'ended_at', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tip' => 'decimal:2',
            'total' => 'decimal:2',
            'booking_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['status' => 'placed', 'payment_method' => 'cod'];

    protected static function booted(): void
    {
        static::created(function (Ride $ride) {
            if (! $ride->number) {
                $ride->number = sprintf('CAB-DDE-%s-%05d', $ride->created_at->format('Ym'), $ride->id);
                $ride->saveQuietly();
            }
        });
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function cabType(): BelongsTo
    {
        return $this->belongsTo(CabType::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(RideStatusHistory::class)->orderBy('created_at');
    }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, static::TRANSITIONS[$this->status] ?? [], true);
    }

    public function statusLabel(): string
    {
        return static::STATUSES[$this->status] ?? $this->status;
    }
}
