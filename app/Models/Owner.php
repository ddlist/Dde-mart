<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/*
 * DDE-Mart Admin — Owner model (original). Store-owner accounts (role=vendor
 * app users). Status machine mirrors drivers/stores.
 */
class Owner extends Authenticatable
{
    use HasFactory, HasApiTokens;

    public const STATUSES = ['pending', 'active', 'suspended', 'rejected'];

    public const TRANSITIONS = [
        'pending' => ['active', 'rejected'],
        'active' => ['suspended'],
        'suspended' => ['active'],
        'rejected' => ['active'],
    ];

    protected $fillable = ['legacy_id', 'name', 'phone', 'email', 'status'];

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['status' => 'pending'];

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class, 'owner_id');
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
