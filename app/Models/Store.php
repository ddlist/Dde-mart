<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/*
 * DDE-Mart Admin — Store model (original). Unifies Firestore `vendors` docs.
 * Status machine: pending → active|rejected, active ⇄ suspended, rejected → active.
 */
class Store extends Model
{
    use HasFactory, HasSlug;

    public const STATUSES = ['pending', 'active', 'suspended', 'rejected'];

    public const TRANSITIONS = [
        'pending' => ['active', 'rejected'],
        'active' => ['suspended'],
        'suspended' => ['active'],
        'rejected' => ['active'],
    ];

    protected $fillable = [
        'legacy_id', 'name', 'slug', 'description', 'owner_name', 'phone', 'address',
        'latitude', 'longitude', 'image_path', 'section_id', 'zone_id', 'owner_id',
        'status', 'is_open', 'commission_type', 'commission_value',
        'min_order', 'delivery_fee', 'delivery_per_km', 'self_delivery',
        'subscription_plan_id',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'commission_value' => 'decimal:2',
            'min_order' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'delivery_per_km' => 'decimal:2',
            'is_open' => 'boolean',
            'self_delivery' => 'boolean',
        ];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = [
        'status' => 'pending',
        'commission_type' => 'percentage',
        'is_open' => true,
        'self_delivery' => false,
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'vendor_id');
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
