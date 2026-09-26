<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * DDE-Mart Admin — Order model (original).
 * Canonical statuses: placed → accepted → preparing → shipped → completed,
 * with cancelled / rejected as early exits. Terminal: completed, cancelled, rejected.
 */
class Order extends Model
{
    use HasFactory;

    public const PLACED = 'placed';

    public const ACCEPTED = 'accepted';

    public const PREPARING = 'preparing';

    public const SHIPPED = 'shipped';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    public const REJECTED = 'rejected';

    public const STATUSES = [
        self::PLACED => 'Placed',
        self::ACCEPTED => 'Accepted',
        self::PREPARING => 'Preparing',
        self::SHIPPED => 'Shipped',
        self::COMPLETED => 'Completed',
        self::CANCELLED => 'Cancelled',
        self::REJECTED => 'Rejected',
    ];

    /** Allowed next statuses per current status. */
    public const TRANSITIONS = [
        self::PLACED => [self::ACCEPTED, self::REJECTED, self::CANCELLED],
        self::ACCEPTED => [self::PREPARING, self::SHIPPED, self::CANCELLED],
        self::PREPARING => [self::SHIPPED, self::CANCELLED],
        self::SHIPPED => [self::COMPLETED],
        self::COMPLETED => [],
        self::CANCELLED => [],
        self::REJECTED => [],
    ];

    protected $fillable = [
        'number', 'type', 'customer_id', 'customer_name', 'customer_email', 'customer_phone',
        'vendor_id', 'driver_id', 'section_id', 'address', 'payment_method',
        'subtotal', 'discount', 'delivery_charge', 'tip', 'tax', 'total',
        'coupon_code', 'notes', 'status', 'scheduled_at', 'estimated_prep_minutes',
        'dispatch_expires_at', 'rejected_driver_ids',
    ];

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = [
        'type' => 'food',
        'status' => 'placed',
        'payment_method' => 'cod',
    ];

    protected function casts(): array
    {
        return [
            'address' => 'array',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'delivery_charge' => 'decimal:2',
            'tip' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'scheduled_at' => 'datetime',
            'dispatch_expires_at' => 'datetime',
            'rejected_driver_ids' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Order $order) {
            // Human-readable number, assigned post-insert (race-free).
            if (! $order->number || $order->number === 'pending') {
                $order->number = sprintf('DDE-%s-%05d', $order->created_at->format('Ym'), $order->id);
                $order->saveQuietly();
            }
        });
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /** Drivers already offered (and timed out) for this order. */
    public function rejectedDriverIds(): array
    {
        return array_values(array_filter(
            array_map('intval', (array) ($this->rejected_driver_ids ?? []))
        ));
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, static::TRANSITIONS[$this->status] ?? [], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::COMPLETED, self::CANCELLED, self::REJECTED], true);
    }

    public function statusLabel(): string
    {
        return static::STATUSES[$this->status] ?? $this->status;
    }
}
