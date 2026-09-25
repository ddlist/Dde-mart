<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — ParcelStatusHistory model (original). Append-only timeline.
 */
class ParcelStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'parcel_status_history';

    protected $fillable = ['parcel_order_id', 'from_status', 'to_status', 'changed_by', 'note'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ParcelOrder::class, 'parcel_order_id');
    }
}
