<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * DDE-Mart Admin — DocumentType master (original). Unifies `documents` collection.
 * owner_type scopes which directory the document applies to.
 */
class DocumentType extends Model
{
    use HasFactory;

    public const OWNERS = ['driver', 'store', 'owner'];

    protected $fillable = [
        'legacy_id', 'title', 'owner_type', 'front_required', 'back_required', 'is_active',
    ];

    protected function casts(): array
    {
        return ['front_required' => 'boolean', 'back_required' => 'boolean', 'is_active' => 'boolean'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = [
        'owner_type' => 'driver', 'front_required' => true,
        'back_required' => false, 'is_active' => true,
    ];

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }
}
