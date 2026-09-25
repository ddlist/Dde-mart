<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * DDE-Mart Admin — ReviewCriterion model (original). Rating axes (brightness…).
 * Distinct from catalog attributes (variant axes).
 */
class ReviewCriterion extends Model
{
    use HasFactory;

    protected $fillable = ['legacy_id', 'title', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['is_active' => true];
}
