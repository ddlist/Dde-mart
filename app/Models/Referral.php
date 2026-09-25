<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * DDE-Mart Admin — Referral model (original). Read-only program pairs.
 */
class Referral extends Model
{
    use HasFactory;

    protected $fillable = ['legacy_id', 'code', 'referrer_ref'];
}
