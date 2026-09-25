<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — SubscriptionPlan model (original).
 * Null limits mean unlimited. `features` holds capability flags (mobile app, chat…).
 */
class SubscriptionPlan extends Model
{
    use HasFactory;

    public const FEATURES = ['ownerMobileApp', 'chat', 'qrCodeGenerate', 'reports', 'advertisements'];

    protected $fillable = [
        'name', 'description', 'type', 'price', 'validity_days',
        'item_limit', 'order_limit', 'is_commission_plan',
        'features', 'section_id', 'image_path', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_commission_plan' => 'boolean',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = [
        'type' => 'free',
        'is_commission_plan' => false,
        'is_active' => true,
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
