<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ParcelCategory;
use App\Models\ParcelOrder;
use App\Models\ParcelWeight;
use App\Models\RentalOrder;
use App\Models\RentalPackage;
use App\Models\RentalVehicleType;
use App\Models\Ride;
use App\Models\Setting;
use Illuminate\Http\Request;

/*
 * DDE-Mart API — customer transport bookings (original).
 * Parcel/rental/rides: book, list, track. Server-priced, phone-scoped.
 */
class TransportApiController extends Controller
{
    // -- Parcel ----------------------------------------------------------

    public function parcelMeta()
    {
        return response()->json(['data' => [
            'categories' => ParcelCategory::where('is_active', true)->orderBy('sort_order')
                ->get(['id', 'name']),
            'weights' => ParcelWeight::where('is_active', true)->orderBy('sort_order')
                ->get(['id', 'title', 'delivery_charge']),
            'per_km' => (float) Setting::get('parcel_per_km', 2),
        ]]);
    }

    public function parcelQuote(Request $request)
    {
        $validated = $request->validate([
            'weight_id' => ['required', 'integer', 'exists:parcel_weights,id'],
            'distance_km' => ['required', 'numeric', 'min:0'],
        ]);

        $weight = ParcelWeight::findOrFail($validated['weight_id']);
        $charge = round($weight->delivery_charge + $validated['distance_km'] * (float) Setting::get('parcel_per_km', 2), 2);

        return response()->json(['data' => ['charge' => $charge]]);
    }

    public function parcelBook(Request $request)
    {
        $validated = $request->validate([
            'sender_name' => ['required', 'string', 'max:200'],
            'sender_address' => ['required', 'string', 'max:500'],
            'receiver_name' => ['required', 'string', 'max:200'],
            'receiver_phone' => ['required', 'string', 'max:50'],
            'receiver_address' => ['required', 'string', 'max:500'],
            'category_id' => ['nullable', 'integer', 'exists:parcel_categories,id'],
            'weight_id' => ['required', 'integer', 'exists:parcel_weights,id'],
            'distance_km' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer = $request->user();
        $weight = ParcelWeight::findOrFail($validated['weight_id']);
        $total = round($weight->delivery_charge + $validated['distance_km'] * (float) Setting::get('parcel_per_km', 2), 2);

        $order = ParcelOrder::create([
            'sender_name' => $validated['sender_name'],
            'sender_phone' => $customer->phone,
            'sender_address' => ['address' => $validated['sender_address']],
            'receiver_name' => $validated['receiver_name'],
            'receiver_phone' => $validated['receiver_phone'],
            'receiver_address' => ['address' => $validated['receiver_address']],
            'category_id' => $validated['category_id'] ?? null,
            'weight_id' => $weight->id,
            'distance_km' => $validated['distance_km'],
            'subtotal' => $total,
            'total' => $total,
            'payment_method' => 'cod',
            'notes' => $validated['notes'] ?? null,
            'status' => 'placed',
        ]);

        $order->history()->create(['from_status' => null, 'to_status' => 'placed']);

        app(\App\Services\WorkforceNotifier::class)->parcelPlaced($order);

        return response()->json(['data' => ['id' => $order->id, 'number' => $order->number, 'total' => $total]], 201);
    }

    public function parcelOrders(Request $request)
    {
        $orders = ParcelOrder::where('sender_phone', $request->user()->phone)
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $orders->map(fn ($o) => [
                'id' => $o->id, 'number' => $o->number, 'status' => $o->status,
                'total' => (float) $o->total,
            ]),
            'meta' => ['current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage(), 'total' => $orders->total()],
        ]);
    }

    public function parcelTrack(Request $request, ParcelOrder $order)
    {
        abort_unless($order->sender_phone === $request->user()->phone, 404);
        $order->load(['history', 'driver']);

        return response()->json(['data' => [
            'id' => $order->id, 'number' => $order->number, 'status' => $order->status,
            'total' => (float) $order->total,
            'driver' => $this->driverPosition($order->driver),
            'timeline' => $order->history->map(fn ($h) => [
                'from' => $h->from_status, 'to' => $h->to_status,
                'at' => $h->created_at?->toIso8601String(),
            ]),
        ]]);
    }

    /** Customer-safe driver card with live position (null until assigned). */
    protected function driverPosition($driver): ?array
    {
        if (! $driver) {
            return null;
        }

        return [
            'id' => $driver->id,
            'name' => $driver->name,
            'phone' => $driver->phone,
            'latitude' => $driver->latitude !== null ? (float) $driver->latitude : null,
            'longitude' => $driver->longitude !== null ? (float) $driver->longitude : null,
            'position_at' => $driver->location_updated_at?->toIso8601String(),
        ];
    }

    public function parcelCancel(Request $request, ParcelOrder $order)
    {
        abort_unless($order->sender_phone === $request->user()->phone, 404);
        abort_unless(in_array($order->status, ['placed', 'accepted'], true), 422, 'Too late to cancel.');

        $from = $order->status;
        $order->update(['status' => 'cancelled']);
        $order->history()->create(['from_status' => $from, 'to_status' => 'cancelled']);

        return response()->json(['data' => ['status' => 'cancelled']]);
    }

    // -- Rental ----------------------------------------------------------

    public function rentalMeta()
    {
        return response()->json(['data' => [
            'types' => RentalVehicleType::where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'capacity']),
            'packages' => RentalPackage::with(['vehicleType:id,name'])->where('is_active', true)
                ->orderBy('sort_order')->get(['id', 'vehicle_type_id', 'name', 'base_fare', 'included_hours', 'included_km']),
        ]]);
    }

    public function rentalBook(Request $request)
    {
        $validated = $request->validate([
            'package_id' => ['required', 'integer', 'exists:rental_packages,id'],
            'source' => ['required', 'string', 'max:500'],
            'destination' => ['nullable', 'string', 'max:500'],
            'booking_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $package = RentalPackage::findOrFail($validated['package_id']);
        $customer = $request->user();

        $order = RentalOrder::create([
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'package_id' => $package->id,
            'vehicle_type_id' => $package->vehicle_type_id,
            'source' => $validated['source'],
            'destination' => $validated['destination'] ?? null,
            'subtotal' => $package->base_fare,
            'total' => $package->base_fare,
            'payment_method' => 'cod',
            'booking_at' => $validated['booking_at'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'placed',
        ]);

        $order->history()->create(['from_status' => null, 'to_status' => 'placed']);

        app(\App\Services\WorkforceNotifier::class)->rentalPlaced($order);

        return response()->json(['data' => ['id' => $order->id, 'number' => $order->number]], 201);
    }

    public function rentalOrders(Request $request)
    {
        $orders = RentalOrder::where('customer_phone', $request->user()->phone)
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $orders->map(fn ($o) => [
                'id' => $o->id, 'number' => $o->number, 'status' => $o->status,
                'total' => (float) $o->total,
            ]),
            'meta' => ['current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage(), 'total' => $orders->total()],
        ]);
    }

    public function rentalTrack(Request $request, RentalOrder $order)
    {
        abort_unless($order->customer_phone === $request->user()->phone, 404);
        $order->load(['history', 'package', 'driver']);

        return response()->json(['data' => [
            'id' => $order->id, 'number' => $order->number, 'status' => $order->status,
            'total' => (float) $order->total,
            'driver' => $this->driverPosition($order->driver),
            'timeline' => $order->history->map(fn ($h) => [
                'from' => $h->from_status, 'to' => $h->to_status,
                'at' => $h->created_at?->toIso8601String(),
            ]),
        ]]);
    }

    public function rentalCancel(Request $request, RentalOrder $order)
    {
        abort_unless($order->customer_phone === $request->user()->phone, 404);
        abort_unless(in_array($order->status, ['placed', 'accepted'], true), 422, 'Too late to cancel.');

        $from = $order->status;
        $order->update(['status' => 'cancelled']);
        $order->history()->create(['from_status' => $from, 'to_status' => 'cancelled']);

        return response()->json(['data' => ['status' => 'cancelled']]);
    }

    // -- Rides -----------------------------------------------------------

    public function rideRequest(Request $request)
    {
        $validated = $request->validate([
            'source' => ['required', 'string', 'max:500'],
            'destination' => ['required', 'string', 'max:500'],
            'cab_type_id' => ['nullable', 'integer', 'exists:cab_types,id'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
        ]);

        $customer = $request->user();
        $total = 0;

        if (! empty($validated['cab_type_id']) && ! empty($validated['distance_km'])) {
            $type = \App\Models\CabType::find($validated['cab_type_id']);
            $total = round(max($type->min_fare, $type->base_fare + $type->per_km_fare * $validated['distance_km']), 2);
        }

        $ride = Ride::create([
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'cab_type_id' => $validated['cab_type_id'] ?? null,
            'source' => $validated['source'],
            'destination' => $validated['destination'],
            'distance_km' => $validated['distance_km'] ?? null,
            'subtotal' => $total,
            'total' => $total,
            'payment_method' => 'cod',
            'booking_at' => now(),
            'status' => 'placed',
        ]);

        $ride->history()->create(['from_status' => null, 'to_status' => 'placed']);

        app(\App\Services\WorkforceNotifier::class)->rideRequested($ride);

        return response()->json(['data' => ['id' => $ride->id, 'number' => $ride->number]], 201);
    }

    public function rides(Request $request)
    {
        $rides = Ride::where('customer_phone', $request->user()->phone)
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $rides->map(fn ($r) => [
                'id' => $r->id, 'number' => $r->number, 'status' => $r->status,
                'total' => (float) $r->total,
            ]),
            'meta' => ['current_page' => $rides->currentPage(), 'last_page' => $rides->lastPage(), 'total' => $rides->total()],
        ]);
    }

    public function rideTrack(Request $request, Ride $ride)
    {
        abort_unless($ride->customer_phone === $request->user()->phone, 404);
        $ride->load(['history', 'driver']);

        return response()->json(['data' => [
            'id' => $ride->id, 'number' => $ride->number, 'status' => $ride->status,
            'total' => (float) $ride->total,
            'driver' => $this->driverPosition($ride->driver),
            'timeline' => $ride->history->map(fn ($h) => [
                'from' => $h->from_status, 'to' => $h->to_status,
                'at' => $h->created_at?->toIso8601String(),
            ]),
        ]]);
    }

    public function rideCancel(Request $request, Ride $ride)
    {
        abort_unless($ride->customer_phone === $request->user()->phone, 404);
        abort_unless(in_array($ride->status, ['placed', 'accepted'], true), 422, 'Too late to cancel.');

        $from = $ride->status;
        $ride->update(['status' => 'cancelled']);
        $ride->history()->create(['from_status' => $from, 'to_status' => 'cancelled']);

        return response()->json(['data' => ['status' => 'cancelled']]);
    }
}
