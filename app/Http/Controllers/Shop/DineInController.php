<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\TableBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/*
 * DDE-Mart storefront — dine-in reservations (original).
 */
class DineInController extends Controller
{
    public function index(): View
    {
        $stores = Store::where('status', 'active')->orderBy('name')->limit(12)->get();
        $customer = Auth::guard('customer')->user();

        $mine = TableBooking::where('guest_phone', $customer->phone)
            ->orderByDesc('booked_for')->limit(5)->get();

        return view('shop.dinein', compact('stores', 'mine'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'guests' => ['required', 'integer', 'min:1', 'max:30'],
            'booked_for' => ['required', 'date', 'after:now'],
            'occasion' => ['nullable', 'string', 'max:150'],
            'special_request' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer = Auth::guard('customer')->user();

        $booking = TableBooking::create([
            'store_id' => $validated['store_id'] ?? null,
            'guest_name' => $customer->name,
            'guest_phone' => $customer->phone,
            'guest_email' => $customer->email,
            'guests' => $validated['guests'],
            'booked_for' => $validated['booked_for'],
            'occasion' => $validated['occasion'] ?? null,
            'special_request' => $validated['special_request'] ?? null,
            'status' => 'pending',
        ]);

        app(\App\Services\WorkforceNotifier::class)->dineInPlaced($booking);

        return redirect()->route('shop.dinein')->with('success', "Table requested for {$booking->guests}.");
    }
}
