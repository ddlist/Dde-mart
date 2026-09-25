<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * DDE-Mart Admin — Language model (original). Exactly one default, like currencies.
 */
class Language extends Model
{
    use HasFactory;

    protected $table = 'languages';

    protected $fillable = ['code', 'name', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['is_default' => false, 'is_active' => true];
}
