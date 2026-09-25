<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * DDE-Mart Admin — CarMake model (original).
 */
class CarMake extends Model
{
    use HasFactory;

    protected $fillable = ['legacy_id', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['is_active' => true];

    public function models(): HasMany
    {
        return $this->hasMany(CarModel::class);
    }
}
