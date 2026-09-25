<?php

namespace App\Services;

use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\User;
use InvalidArgumentException;

/*
 * DDE-Mart Admin — the only legal way to move an order (original service).
 * Enforces the transition map, persists the change, appends history, fires the event.
 */
class OrderStatus
{
    public static function transition(Order $order, string $to, ?User $by = null, ?string $note = null): Order
    {
        if (! array_key_exists($to, Order::STATUSES)) {
            throw new InvalidArgumentException("Unknown order status [{$to}].");
        }

        if (! $order->canTransitionTo($to)) {
            throw new InvalidArgumentException(
                "Cannot move order {$order->number} from [{$order->status}] to [{$to}]."
            );
        }

        $from = $order->status;
        $order->status = $to;
        $order->save();

        $order->history()->create([
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $by?->id,
            'note' => $note,
        ]);

        OrderStatusChanged::dispatch($order->fresh(), $from, $to, $by, $note);

        return $order->fresh();
    }

    /** Next legal statuses for UI buttons. */
    public static function allowedFor(Order $order): array
    {
        return Order::TRANSITIONS[$order->status] ?? [];
    }
}
