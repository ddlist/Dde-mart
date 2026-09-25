<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * DDE-Mart Admin — ProviderBooking model (original).
 * Machine: placed → accepted → ongoing → completed (+cancelled/rejected).
 */
class ProviderBooking extends Model
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
        'number', 'legacy_id', 'customer_name', 'customer_phone', 'provider_id',
        'service_id', 'worker_id', 'address', 'scheduled_at', 'subtotal',
        'discount', 'extra_charges', 'total', 'payment_method', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'extra_charges' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['status' => 'placed', 'payment_method' => 'cod'];

    protected static function booted(): void
    {
        static::created(function (ProviderBooking $booking) {
            if (! $booking->number) {
                $booking->number = sprintf('BK-DDE-%s-%05d', $booking->created_at->format('Ym'), $booking->id);
                $booking->saveQuietly();
            }
        });
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ProviderService::class, 'service_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(ProviderWorker::class, 'worker_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class)->orderBy('created_at');
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
