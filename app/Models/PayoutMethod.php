<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * DDE-Mart Admin — saved withdraw method (original). One default per
 * requester; details hold bank fields (never secrets).
 */
class PayoutMethod extends Model
{
    use HasFactory;

    public const METHODS = ['bank', 'paypal', 'stripe', 'razorpay', 'flutterwave', 'cash'];

    protected $fillable = [
        'requester_type', 'requester_ref', 'method', 'details', 'is_default',
    ];

    protected function casts(): array
    {
        return ['details' => 'array', 'is_default' => 'boolean'];
    }
}
