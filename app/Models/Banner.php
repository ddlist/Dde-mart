<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — Banner model (original).
 * Unifies Firestore `banner_items` docs into MySQL.
 */
class Banner extends Model
{
    use HasFactory;

    public const REDIRECT_TYPES = ['none', 'product', 'category', 'store', 'url'];

    public const POSITIONS = ['home', 'top', 'middle', 'bottom'];

    protected $fillable = [
        'section_id', 'title', 'image_path', 'redirect_type', 'redirect_target',
        'position', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
