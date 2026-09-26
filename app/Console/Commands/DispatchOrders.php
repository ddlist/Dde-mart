<?php

namespace App\Console\Commands;

use App\Models\Driver;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Store;
use App\Services\OrderStatus;
use App\Services\WorkforceNotifier;
use Illuminate\Console\Command;

/*
 * DDE-Mart — food auto-dispatch (original command). Port of the legacy order
 * tracking function to queued Laravel logic: every minute, vendor-accepted
 * orders without a driver are offered to the nearest eligible driver with an
 * accept deadline; timeouts release + blacklist and re-offer; hopeless orders
 * auto-cancel. Entirely gated by `dispatch_auto` (pool mode when off).
 * Wire to cron via the scheduler: `* * * * * php artisan schedule:run`.
 */
class DispatchOrders extends Command
{
    protected $signature = 'dispatch:orders';

    protected $description = 'Offer accepted food orders to nearby drivers';

    public function handle(WorkforceNotifier $notifier): int
    {
        if (Setting::get('dispatch_auto', '0') !== '1') {
            $this->line('Auto-dispatch is off (dispatch_auto).');

            return self::SUCCESS;
        }

        $radiusKm = max(0.5, (float) Setting::get('dispatch_radius_km', 5));
        $acceptSeconds = max(15, (int) Setting::get('driver_accept_seconds', 60));
        $staleMinutes = max(1, (int) Setting::get('dispatch_location_stale_minutes', 30));
        $cancelMinutes = max(1, (int) Setting::get('order_auto_cancel_minutes', 30));

        $orders = Order::where('type', 'food')
            ->where('status', Order::ACCEPTED)
            ->where(function ($q) {
                $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
            })
            ->orderBy('id')
            ->get();

        $offered = 0;
        $released = 0;
        $cancelled = 0;

        foreach ($orders as $order) {
            // 1. Release timed-out offers back to the pool (blacklisting the driver).
            if ($order->driver_id && $order->dispatch_expires_at && $order->dispatch_expires_at->isPast()) {
                $rejected = $order->rejectedDriverIds();
                $rejected[] = (int) $order->driver_id;
                $order->update([
                    'driver_id' => null,
                    'dispatch_expires_at' => null,
                    'rejected_driver_ids' => array_values(array_unique($rejected)),
                ]);
                $order->history()->create([
                    'from_status' => Order::ACCEPTED, 'to_status' => Order::ACCEPTED,
                    'note' => 'Dispatch offer timed out',
                ]);
                $released++;
                $order->refresh();
            }

            // Live offer outstanding — wait for the driver.
            if ($order->driver_id) {
                continue;
            }

            $driver = $this->nearestEligible($order, $radiusKm, $staleMinutes);

            if ($driver) {
                $order->update([
                    'driver_id' => $driver->id,
                    'dispatch_expires_at' => now()->addSeconds($acceptSeconds),
                ]);
                $notifier->jobOffered($order->fresh(), $driver->id, $acceptSeconds);
                $offered++;
                continue;
            }

            // 2. Nobody left to offer and the kitchen has waited long enough.
            if ($order->updated_at->lt(now()->subMinutes($cancelMinutes))) {
                OrderStatus::transition($order, Order::CANCELLED, null, 'Auto-cancelled: no driver available');
                $cancelled++;
            }
        }

        $this->line("Dispatch: {$offered} offered, {$released} released, {$cancelled} cancelled.");

        return self::SUCCESS;
    }

    /** Nearest eligible driver: active, online, delivery kind, fresh position + token. */
    protected function nearestEligible(Order $order, float $radiusKm, int $staleMinutes): ?Driver
    {
        $store = $order->vendor_id ? Store::find($order->vendor_id) : null;
        $rejected = $order->rejectedDriverIds();

        $candidates = Driver::where('status', 'active')
            ->where('is_online', true)
            ->where('kind', 'delivery')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('location_updated_at', '>=', now()->subMinutes($staleMinutes))
            ->when($rejected !== [], fn ($q) => $q->whereNotIn('id', $rejected))
            ->whereHas('pushTokens')
            ->get();

        $ranked = [];

        foreach ($candidates as $driver) {
            if ($store?->latitude === null || $store?->longitude === null) {
                return $driver; // No store position — first eligible wins.
            }

            $distance = $this->haversineKm(
                (float) $store->latitude, (float) $store->longitude,
                (float) $driver->latitude, (float) $driver->longitude,
            );

            if ($distance <= $radiusKm) {
                $ranked[] = [$distance, $driver];
            }
        }

        if ($ranked === []) {
            return null;
        }

        usort($ranked, fn ($a, $b) => $a[0] <=> $b[0]);

        return $ranked[0][1];
    }

    protected function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * 6371 * asin(sqrt($a));
    }
}
