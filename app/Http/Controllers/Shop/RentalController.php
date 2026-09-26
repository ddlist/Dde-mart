<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\RentalOrder;
use App\Models\RentalPackage;
use App\Models\RentalVehicleType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/*
 * DDE-Mart storefront — rental booking (original).
 * Package base fare at booking; odometer-verified extras settle later.
 */
class RentalController extends Controller
{
    public function index(): View
    {
        $types = RentalVehicleType::where('is_active', true)->orderBy('name')->get();
        $packages = RentalPackage::with(['vehicleType'])->where('is_active', true)
            ->orderBy('sort_order')->get();

        return view('shop.rental', compact('types', 'packages'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'package_id' => ['required', 'integer', 'exists:rental_packages,id'],
            'source' => ['required', 'string', 'max:500'],
            'destination' => ['nullable', 'string', 'max:500'],
            'booking_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $package = RentalPackage::findOrFail($validated['package_id']);
        $customer = Auth::guard('customer')->user();

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

        return redirect()->route('shop.rental.track', $order)
            ->with('success', "Rental {$order->number} booked.");
    }

    public function track(RentalOrder $rentalOrder): View
    {
        $customer = Auth::guard('customer')->user();

        abort_unless($rentalOrder->customer_phone === $customer->phone, 404);

        $rentalOrder->load(['history', 'package', 'driver']);

        return view('shop.rental-track', ['order' => $rentalOrder]);
    }

    public function mine(): View
    {
        $customer = Auth::guard('customer')->user();

        $orders = RentalOrder::where('customer_phone', $customer->phone)
            ->orderByDesc('id')->paginate(10);

        return view('shop.rentals', compact('orders'));
    }
}
