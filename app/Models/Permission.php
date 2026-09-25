<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — Permission model (original implementation).
 * One row per granted ability: (role_id, group, ability). Unique per triple.
 */
class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['role_id', 'group', 'ability'];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function key(): string
    {
        return "{$this->group}.{$this->ability}";
    }
}
