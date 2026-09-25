<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * DDE-Mart Admin — Advertisement model (original). Vendor-paid promos.
 * Single `status` replaces legacy's overlapping status/isPaused/paymentStatus trio.
 */
class Advertisement extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'approved', 'active', 'paused', 'rejected', 'expired'];

    public const TRANSITIONS = [
        'pending' => ['approved', 'rejected'],
        'approved' => ['active', 'rejected'],
        'active' => ['paused', 'expired'],
        'paused' => ['active', 'expired'],
        'rejected' => [],
        'expired' => [],
    ];

    protected $fillable = [
        'title', 'description', 'vendor_id', 'type',
        'cover_path', 'profile_path', 'video_url',
        'starts_at', 'ends_at', 'priority',
        'show_rating', 'show_review', 'status', 'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'show_rating' => 'boolean',
            'show_review' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = [
        'type' => 'banner',
        'status' => 'pending',
        'payment_status' => 'pending',
        'show_rating' => true,
        'show_review' => true,
    ];

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, static::TRANSITIONS[$this->status] ?? [], true);
    }
}
