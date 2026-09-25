<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — Destination model (original). Popular cab destinations.
 */
class Destination extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id', 'section_id', 'title', 'latitude', 'longitude', 'image_path', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['is_active' => true];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
