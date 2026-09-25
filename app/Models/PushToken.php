<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/*
 * DDE-Mart API — PushToken model (original). One row per owner+token.
 */
class PushToken extends Model
{
    use HasFactory;

    protected $fillable = ['tokenable_type', 'tokenable_id', 'token', 'platform'];

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['platform' => 'android'];

    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }
}
