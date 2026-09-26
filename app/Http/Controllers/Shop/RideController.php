<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\CabType;
use App\Models\Ride;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/*
 * DDE-Mart storefront — ride booking (original). Fare = max(min_fare,
 * base_fare + distance × per_km) of the chosen cab type. COD only at launch.
 */
class RideController extends Controller
{
    public function index(): View
    {
        $types = CabType::where('is_active', true)->orderBy('name')->get();

        return view('shop.ride', compact('types'));
    }

    public function quote(Request $request)
    {
        $validated = $request->validate([
            'cab_type_id' => ['required', 'integer', 'exists:cab_types,id'],
            'distance_km' => ['required', 'numeric', 'min:0'],
        ]);

        return response()->json(['data' => [
            'charge' => $this->fare(CabType::findOrFail($validated['cab_type_id']), $validated['distance_km']),
        ]]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'string', 'max:500'],
            'destination' => ['required', 'string', 'max:500'],
            'cab_type_id' => ['nullable', 'integer', 'exists:cab_types,id'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer = Auth::guard('customer')->user();

        $total = 0;

        if (! empty($validated['cab_type_id']) && ! empty($validated['distance_km'])) {
            $total = $this->fare(CabType::findOrFail($validated['cab_type_id']), $validated['distance_km']);
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
            'notes' => $validated['notes'] ?? null,
            'status' => 'placed',
        ]);

        $ride->history()->create(['from_status' => null, 'to_status' => 'placed']);

        app(\App\Services\WorkforceNotifier::class)->rideRequested($ride);

        return redirect()->route('shop.ride.track', $ride)
            ->with('success', "Ride {$ride->number} requested.");
    }

    public function mine(): View
    {
        $customer = Auth::guard('customer')->user();

        $rides = Ride::where('customer_phone', $customer->phone)
            ->orderByDesc('id')->paginate(10);

        return view('shop.rides', compact('rides'));
    }

    public function track(Ride $ride): View
    {
        $customer = Auth::guard('customer')->user();

        abort_unless($ride->customer_phone === $customer->phone, 404);

        $ride->load(['history']);

        return view('shop.ride-track', ['ride' => $ride]);
    }

    public function cancel(Ride $ride): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        abort_unless($ride->customer_phone === $customer->phone, 404);
        abort_unless(in_array($ride->status, ['placed', 'accepted'], true), 422, 'Too late to cancel.');

        $from = $ride->status;
        $ride->update(['status' => 'cancelled']);
        $ride->history()->create(['from_status' => $from, 'to_status' => 'cancelled']);

        return redirect()->route('shop.ride.track', $ride)->with('success', 'Ride cancelled.');
    }

    protected function fare(CabType $type, float $distanceKm): float
    {
        return round(max($type->min_fare, $type->base_fare + $type->per_km_fare * $distanceKm), 2);
    }
}
