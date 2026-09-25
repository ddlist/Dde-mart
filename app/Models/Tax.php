<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — Tax model (original).
 */
class Tax extends Model
{
    use HasFactory;

    protected $table = 'taxes';

    public const TYPES = ['percentage', 'fixed'];

    protected $fillable = [
        'country', 'title', 'type', 'value', 'section_id', 'is_active',
    ];

    protected function casts(): array
    {
        return ['value' => 'decimal:2', 'is_active' => 'boolean'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['type' => 'percentage', 'is_active' => true];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /** Tax amount for a base value. */
    public function calculate(float $base): float
    {
        return $this->type === 'percentage'
            ? round($base * ((float) $this->value / 100), 2)
            : (float) $this->value;
    }
}
