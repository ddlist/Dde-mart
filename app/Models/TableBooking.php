<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — TableBooking model (original). Dine-in reservations.
 */
class TableBooking extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'confirmed', 'seated', 'completed', 'cancelled'];

    public const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['seated', 'cancelled'],
        'seated' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'legacy_id', 'store_id', 'guest_name', 'guest_phone', 'guest_email',
        'guests', 'booked_for', 'occasion', 'special_request', 'status',
    ];

    protected function casts(): array
    {
        return ['booked_for' => 'datetime'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['status' => 'pending'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, static::TRANSITIONS[$this->status] ?? [], true);
    }
}
