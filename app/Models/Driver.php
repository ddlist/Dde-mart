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
        'zone_id', 'store_id', 'status', 'is_online',
    ];

    protected function casts(): array
    {
        return ['is_online' => 'boolean'];
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

    public function verifications(): MorphMany
    {
        return $this->morphMany(Verification::class, 'verifiable');
    }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, static::TRANSITIONS[$this->status] ?? [], true);
    }
}
