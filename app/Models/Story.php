<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — Story model (original). Vendor story videos, moderated.
 */
class Story extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id', 'store_id', 'video_url', 'thumbnail', 'status', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['status' => 'active'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
