<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * DDE-Mart Admin — BookingStatusHistory model (original). Append-only timeline.
 */
class BookingStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'booking_status_history';

    protected $fillable = ['provider_booking_id', 'from_status', 'to_status', 'changed_by', 'note'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(ProviderBooking::class, 'provider_booking_id');
    }
}
