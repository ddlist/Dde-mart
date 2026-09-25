<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — Notification send log (original). Every broadcast + automated
 * push is recorded here with its outcome.
 */
class Notification extends Model
{
    use HasFactory;

    public const AUDIENCES = ['customer', 'vendor', 'driver', 'provider', 'worker', 'all'];

    protected $fillable = ['audience', 'subject', 'message', 'status', 'failure', 'sent_by'];

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['audience' => 'customer', 'status' => 'queued'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
