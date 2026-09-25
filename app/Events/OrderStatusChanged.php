<?php

namespace App\Events;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/*
 * DDE-Mart Admin — fired after every valid status transition (original event).
 * D9 will attach the FCM notifier here; the D7 listener only logs.
 */
class OrderStatusChanged
{
    use Dispatchable;

    public function __construct(
        public Order $order,
        public ?string $from,
        public string $to,
        public ?User $changedBy = null,
        public ?string $note = null,
    ) {}
}
