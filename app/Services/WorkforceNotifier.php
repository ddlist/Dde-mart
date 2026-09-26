<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Models\ParcelOrder;
use App\Models\ProviderBooking;
use App\Models\RentalOrder;
use App\Models\Ride;
use App\Models\TableBooking;

/*
 * DDE-Mart — workforce push fan-out (original service).
 * Customer checkouts/bookings notify the matching workforce topic
 * (vendors / providers / drivers apps subscribe to theirs), reusing the
 * admin-managed push templates when active, else a plain fallback.
 * Every attempt is logged in `notifications` like customer pushes.
 */
class WorkforceNotifier
{
    public function __construct(protected FcmSender $sender) {}

    public function orderPlaced(Order $order): void
    {
        $this->notify(
            'vendors',
            'workforce.order_placed',
            'New order received',
            "Order {$order->number} · {$order->total} — open the vendor app to accept it.",
            ['order_id' => (string) $order->id, 'kind' => 'order'],
            ['order_number' => $order->number, 'customer' => $order->customer_name, 'total' => $order->total],
        );
    }

    public function bookingPlaced(ProviderBooking $booking): void
    {
        $this->notify(
            'providers',
            'workforce.booking_placed',
            'New service booking',
            "Booking {$booking->number} · {$booking->total} — a customer needs your service.",
            ['booking_id' => (string) $booking->id, 'kind' => 'booking'],
            ['order_number' => $booking->number, 'customer' => $booking->customer_name, 'total' => $booking->total],
        );
    }

    public function dineInPlaced(TableBooking $booking): void
    {
        $this->notify(
            'vendors',
            'workforce.dinein_placed',
            'New table booking',
            "Table for {$booking->guests} ({$booking->guest_name}) — confirm it in the vendor app.",
            ['booking_id' => (string) $booking->id, 'kind' => 'dinein'],
            ['order_number' => "#{$booking->id}", 'customer' => $booking->guest_name, 'total' => $booking->guests],
        );
    }

    public function parcelPlaced(ParcelOrder $order): void
    {
        $this->notify(
            'drivers',
            'workforce.parcel_placed',
            'New parcel request',
            "Parcel {$order->number} · {$order->total} — open the driver app to accept it.",
            ['order_id' => (string) $order->id, 'kind' => 'parcel'],
            ['order_number' => $order->number, 'customer' => $order->sender_name, 'total' => $order->total],
        );
    }

    public function rentalPlaced(RentalOrder $order): void
    {
        $this->notify(
            'drivers',
            'workforce.rental_placed',
            'New rental request',
            "Rental {$order->number} · {$order->total} — open the driver app to accept it.",
            ['order_id' => (string) $order->id, 'kind' => 'rental'],
            ['order_number' => $order->number, 'customer' => $order->customer_name, 'total' => $order->total],
        );
    }

    public function rideRequested(Ride $ride): void
    {
        $this->notify(
            'drivers',
            'workforce.ride_requested',
            'New ride request',
            "Ride {$ride->number} · {$ride->total} — open the driver app to accept it.",
            ['ride_id' => (string) $ride->id, 'kind' => 'ride'],
            ['order_number' => $ride->number, 'customer' => $ride->customer_name, 'total' => $ride->total],
        );
    }

    public function rideAssigned(Ride $ride): void
    {
        $this->notify(
            'drivers',
            'workforce.ride_assigned',
            'Ride assigned to you',
            "Ride {$ride->number} was assigned to you — open the driver app.",
            ['ride_id' => (string) $ride->id, 'kind' => 'ride', 'driver_id' => (string) $ride->driver_id],
            ['order_number' => $ride->number, 'customer' => $ride->customer_name, 'total' => $ride->total],
        );
    }

    /** Auto-dispatch offer: order held for one driver until the deadline. */
    public function jobOffered(Order $order, int $driverId, int $seconds): void
    {
        $mins = (int) ceil($seconds / 60);

        $this->notify(
            'drivers',
            'workforce.job_offered',
            'New delivery offer',
            "Order {$order->number} · {$order->total} — accept within {$mins} min in the driver app.",
            ['order_id' => (string) $order->id, 'kind' => 'food', 'driver_id' => (string) $driverId],
            ['order_number' => $order->number, 'customer' => $order->customer_name, 'total' => $order->total],
        );
    }

    protected function notify(
        string $audience,
        string $templateKey,
        string $fallbackSubject,
        string $fallbackBody,
        array $data,
        array $vars,
    ): void {
        $subject = $fallbackSubject;
        $body = $fallbackBody;

        $template = NotificationTemplate::where('key', $templateKey)
            ->where('is_active', true)
            ->first();

        if ($template) {
            $rendered = $template->render($vars);
            $subject = $rendered['subject'];
            $body = $rendered['body'];
            $audience = $template->audience ?: $audience;
        }

        $sent = $this->sender->sendToTopic($audience, $subject, $body, $data);

        Notification::create([
            'audience' => $audience,
            'subject' => $subject,
            'message' => $body,
            'status' => $sent ? 'sent' : 'failed',
            'failure' => $sent ? null : 'FCM not configured or send failed',
        ]);
    }
}
