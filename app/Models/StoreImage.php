<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — store gallery image (original).
 */
class StoreImage extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'path', 'sort_order'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
