<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — ProviderWorker model (original). Provider staff.
 */
class ProviderWorker extends Model
{
    use HasFactory;

    protected $fillable = ['legacy_id', 'provider_id', 'name', 'phone', 'email', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['is_active' => true];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
