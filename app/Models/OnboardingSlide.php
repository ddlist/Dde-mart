<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * DDE-Mart Admin — OnboardingSlide model (original). App intro content.
 */
class OnboardingSlide extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id', 'title', 'description', 'image_path', 'audience', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['audience' => 'customer', 'is_active' => true];
}
