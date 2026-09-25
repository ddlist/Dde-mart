<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/*
 * DDE-Mart Admin — Provider model (original). role=provider accounts.
 */
class Provider extends Authenticatable
{
    use HasFactory, HasApiTokens;

    public const STATUSES = ['pending', 'active', 'suspended', 'rejected'];

    public const TRANSITIONS = [
        'pending' => ['active', 'rejected'],
        'active' => ['suspended'],
        'suspended' => ['active'],
        'rejected' => ['active'],
    ];

    protected $fillable = ['legacy_id', 'name', 'phone', 'email', 'address', 'status'];

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['status' => 'pending'];

    public function services(): HasMany
    {
        return $this->hasMany(ProviderService::class);
    }

    public function workers(): HasMany
    {
        return $this->hasMany(ProviderWorker::class);
    }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, static::TRANSITIONS[$this->status] ?? [], true);
    }
}
