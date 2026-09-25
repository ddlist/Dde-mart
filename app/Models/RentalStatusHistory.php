<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — RentalStatusHistory model (original). Append-only timeline.
 */
class RentalStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'rental_status_history';

    protected $fillable = ['rental_order_id', 'from_status', 'to_status', 'changed_by', 'note'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(RentalOrder::class, 'rental_order_id');
    }
}
