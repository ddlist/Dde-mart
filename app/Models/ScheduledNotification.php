<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * DDE-Mart Admin — ScheduledNotification model (original). Timed pushes sent by
 * the notifications:send command (wire it to cron for production).
 */
class ScheduledNotification extends Model
{
    use HasFactory;

    protected $fillable = ['audience', 'subject', 'message', 'send_at', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['send_at' => 'datetime'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['audience' => 'customer', 'status' => 'scheduled'];

    public function isDue(): bool
    {
        return $this->status === 'scheduled' && $this->send_at->isPast();
    }
}
