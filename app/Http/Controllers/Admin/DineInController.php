<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\TableBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — dine-in table bookings (original controller).
 */
class DineInController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = TableBooking::with(['store'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('booked_for')
            ->paginate(15)->withQueryString();

        return view('admin.dinein.index', [
            'bookings' => $bookings,
            'statuses' => TableBooking::STATUSES,
            'stores' => Store::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'guest_name' => ['required', 'string', 'max:200'],
            'guest_phone' => ['nullable', 'string', 'max:50'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guests' => ['required', 'integer', 'min:1'],
            'booked_for' => ['required', 'date'],
            'occasion' => ['nullable', 'string', 'max:150'],
            'special_request' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking = TableBooking::create($validated);

        return redirect()->route('admin.dinein.index')->with('success', "Table booked for '{$booking->guest_name}'.");
    }

    public function transition(Request $request, TableBooking $booking): RedirectResponse
    {
        $to = $request->validate(['to' => ['required', 'string']])['to'];

        if (! in_array($to, TableBooking::STATUSES, true) || ! $booking->canTransitionTo($to)) {
            return redirect()->route('admin.dinein.index')->with('error', "Cannot move booking to [{$to}].");
        }

        $booking->update(['status' => $to]);

        return redirect()->route('admin.dinein.index')->with('success', "Booking {$to}.");
    }

    public function destroy(TableBooking $booking): RedirectResponse
    {
        $booking->delete();

        return redirect()->route('admin.dinein.index')->with('success', 'Booking deleted.');
    }
}
