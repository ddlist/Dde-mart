<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * DDE-Mart Admin — ParcelOrder model (original).
 * Machine: placed → accepted → shipped → completed (+cancelled/rejected).
 */
class ParcelOrder extends Model
{
    use HasFactory;

    public const STATUSES = [
        'placed' => 'Placed', 'accepted' => 'Accepted', 'shipped' => 'Shipped',
        'completed' => 'Completed', 'cancelled' => 'Cancelled', 'rejected' => 'Rejected',
    ];

    public const TRANSITIONS = [
        'placed' => ['accepted', 'rejected', 'cancelled'],
        'accepted' => ['shipped', 'cancelled'],
        'shipped' => ['completed'],
        'completed' => [],
        'cancelled' => [],
        'rejected' => [],
    ];

    protected $fillable = [
        'number', 'legacy_id', 'sender_name', 'sender_phone', 'sender_address',
        'receiver_name', 'receiver_phone', 'receiver_address',
        'category_id', 'weight_id', 'distance_km', 'subtotal', 'discount', 'total',
        'payment_method', 'collect_by_receiver', 'driver_id', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'sender_address' => 'array',
            'receiver_address' => 'array',
            'distance_km' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'collect_by_receiver' => 'boolean',
        ];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['status' => 'placed', 'payment_method' => 'cod', 'collect_by_receiver' => false];

    protected static function booted(): void
    {
        static::created(function (ParcelOrder $order) {
            if (! $order->number) {
                $order->number = sprintf('P-DDE-%s-%05d', $order->created_at->format('Ym'), $order->id);
                $order->saveQuietly();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ParcelCategory::class);
    }

    public function weight(): BelongsTo
    {
        return $this->belongsTo(ParcelWeight::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(ParcelStatusHistory::class)->orderBy('created_at');
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
