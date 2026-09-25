<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Services\FcmSender;

/*
 * DDE-Mart Admin — automated order push (original listener).
 * Renders the `order.{status}` template (if active) and sends to the audience topic,
 * recording the outcome in `notifications`. Silent when FCM isn't configured.
 */
class SendOrderStatusPush
{
    public function __construct(protected FcmSender $sender) {}

    public function handle(OrderStatusChanged $event): void
    {
        $template = NotificationTemplate::where('key', "order.{$event->to}")
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return;
        }

        $rendered = $template->render([
            'order_number' => $event->order->number,
            'customer' => $event->order->customer_name,
            'status' => $event->order->statusLabel(),
            'total' => $event->order->total,
        ]);

        $sent = $this->sender->sendToTopic(
            $template->audience,
            $rendered['subject'],
            $rendered['body'],
            ['order_id' => (string) $event->order->id, 'status' => $event->to],
        );

        Notification::create([
            'audience' => $template->audience,
            'subject' => $rendered['subject'],
            'message' => $rendered['body'],
            'status' => $sent ? 'sent' : 'failed',
            'failure' => $sent ? null : 'FCM not configured or send failed',
            'sent_by' => $event->changedBy?->id,
        ]);
    }
}
