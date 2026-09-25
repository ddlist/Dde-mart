<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * DDE-Mart Admin — ProviderCategory model (original). Nested via parent_id.
 */
class ProviderCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id', 'parent_id', 'section_id', 'level', 'title', 'image_path', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['is_active' => true];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProviderCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProviderCategory::class, 'parent_id')->orderBy('title');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
