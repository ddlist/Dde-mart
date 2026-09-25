<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — RideStatusHistory model (original). Append-only timeline.
 */
class RideStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'ride_status_history';

    protected $fillable = ['ride_id', 'from_status', 'to_status', 'changed_by', 'note'];

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }
}
