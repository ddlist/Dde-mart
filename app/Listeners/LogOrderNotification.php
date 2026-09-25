<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use Illuminate\Support\Facades\Log;

/*
 * DDE-Mart Admin — transition audit log (original listener).
 * Placeholder for the D9 customer/driver notifier (verified HTTP client, never
 * the legacy raw-curl-with-disabled-TLS approach).
 */
class LogOrderNotification
{
    public function handle(OrderStatusChanged $event): void
    {
        Log::info('Order status changed', [
            'order' => $event->order->number,
            'from' => $event->from,
            'to' => $event->to,
            'by' => $event->changedBy?->email,
        ]);
    }
}
