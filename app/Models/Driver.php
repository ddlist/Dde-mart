<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/*
 * DDE-Mart Admin — Driver model (original). kind: ride (marketplace), delivery
 * (store-attached rider), fleet (company-owned). Status machine included.
 */
class Driver extends Authenticatable
{
    use HasFactory, HasApiTokens;

    public const KINDS = ['ride', 'delivery', 'fleet'];

    public const STATUSES = ['pending', 'active', 'suspended', 'rejected'];

    public const TRANSITIONS = [
        'pending' => ['active', 'rejected'],
        'active' => ['suspended'],
        'suspended' => ['active'],
        'rejected' => ['active'],
    ];

    protected $fillable = [
        'legacy_id', 'kind', 'name', 'phone', 'email', 'photo_path', 'vehicle_info',
        'zone_id', 'store_id', 'owner_id', 'latitude', 'longitude', 'location_updated_at',
        'status', 'is_online',
        'bank_name', 'bank_branch', 'bank_holder', 'bank_account', 'bank_other',
    ];

    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'location_updated_at' => 'datetime',
        ];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['kind' => 'ride', 'status' => 'pending', 'is_online' => false];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function verifications(): MorphMany
    {
        return $this->morphMany(Verification::class, 'verifiable');
    }

    public function pushTokens(): MorphMany
    {
        return $this->morphMany(PushToken::class, 'tokenable');
    }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, static::TRANSITIONS[$this->status] ?? [], true);
    }
}
