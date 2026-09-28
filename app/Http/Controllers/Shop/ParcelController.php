<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ParcelCategory;
use App\Models\ParcelOrder;
use App\Models\ParcelWeight;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/*
 * DDE-Mart storefront — parcel booking (original).
 * Price = weight slab charge + distance × parcel_per_km setting. COD only at
 * launch (online methods reuse checkout once parcel checkout lands).
 */
class ParcelController extends Controller
{
    public function index(): View
    {
        $categories = ParcelCategory::where('is_active', true)->orderBy('sort_order')->get();
        $weights = ParcelWeight::where('is_active', true)->orderBy('sort_order')->get();

        return view('shop.parcel', compact('categories', 'weights'));
    }

    public function quote(Request $request)
    {
        $validated = $request->validate([
            'weight_id' => ['required', 'integer', 'exists:parcel_weights,id'],
            'distance_km' => ['required', 'numeric', 'min:0'],
        ]);

        $weight = ParcelWeight::findOrFail($validated['weight_id']);

        return response()->json(['data' => [
            'charge' => \App\Support\ParcelPricing::quote($weight, (float) $validated['distance_km']),
        ]]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sender_name' => ['required', 'string', 'max:200'],
            'sender_phone' => ['required', 'string', 'max:50'],
            'sender_address' => ['required', 'string', 'max:500'],
            'receiver_name' => ['required', 'string', 'max:200'],
            'receiver_phone' => ['required', 'string', 'max:50'],
            'receiver_address' => ['required', 'string', 'max:500'],
            'category_id' => ['nullable', 'integer', 'exists:parcel_categories,id'],
            'weight_id' => ['required', 'integer', 'exists:parcel_weights,id'],
            'distance_km' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $weight = ParcelWeight::findOrFail($validated['weight_id']);
        $total = \App\Support\ParcelPricing::quote($weight, (float) $validated['distance_km']);

        $customer = Auth::guard('customer')->user();

        $order = ParcelOrder::create([
            'sender_name' => $validated['sender_name'],
            'sender_phone' => $validated['sender_phone'],
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

        return redirect()->route('shop.parcel.track', $order)
            ->with('success', "Parcel {$order->number} booked. Pay {$total} on delivery.");
    }

    public function track(ParcelOrder $parcelOrder): View
    {
        $customer = Auth::guard('customer')->user();

        abort_unless($parcelOrder->sender_phone === $customer->phone, 404);

        $parcelOrder->load(['history']);

        return view('shop.parcel-track', ['order' => $parcelOrder]);
    }

    public function mine(): View
    {
        $customer = Auth::guard('customer')->user();

        $orders = ParcelOrder::where('sender_phone', $customer->phone)
            ->orderByDesc('id')->paginate(10);

        return view('shop.parcels', compact('orders'));
    }
}
