<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ProviderBooking;
use App\Models\ProviderCategory;
use App\Models\ProviderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/*
 * DDE-Mart storefront — on-demand services (original).
 * Browse nested categories, book a service, track like other verticals.
 */
class ServiceController extends Controller
{
    public function index(): View
    {
        $categories = ProviderCategory::with(['children'])
            ->where('is_active', true)->whereNull('parent_id')
            ->orderBy('title')->get();

        return view('shop.services', compact('categories'));
    }

    public function category(ProviderCategory $category): View
    {
        $ids = [$category->id, ...$category->children()->pluck('id')->all()];

        $services = ProviderService::with(['provider'])
            ->where('is_active', true)
            ->whereIn('category_id', $ids)
            ->orderBy('title')->paginate(24);

        return view('shop.service-category', compact('category', 'services'));
    }

    public function book(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:provider_services,id'],
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:50'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $service = ProviderService::findOrFail($validated['service_id']);
        abort_unless($service->is_active, 422, 'Service unavailable.');

        $customer = Auth::guard('customer')->user();

        $booking = ProviderBooking::create([
            'customer_name' => $customer->name,
            'customer_phone' => $validated['phone'],
            'provider_id' => $service->provider_id,
            'service_id' => $service->id,
            'address' => $validated['address'],
            'scheduled_at' => $validated['scheduled_at'],
            'subtotal' => $service->price,
            'total' => $service->price,
            'payment_method' => 'cod',
            'notes' => $validated['notes'] ?? null,
            'status' => 'placed',
        ]);

        $booking->history()->create(['from_status' => null, 'to_status' => 'placed']);

        return redirect()->route('shop.bookings.track', $booking)
            ->with('success', "Booking {$booking->number} placed.");
    }

    public function mine(): View
    {
        $customer = Auth::guard('customer')->user();

        $bookings = ProviderBooking::where('customer_phone', $customer->phone)
            ->orderByDesc('id')->paginate(10);

        return view('shop.bookings', compact('bookings'));
    }

    public function track(ProviderBooking $booking): View
    {
        $customer = Auth::guard('customer')->user();

        abort_unless($booking->customer_phone === $customer->phone, 404);

        $booking->load(['provider', 'service', 'worker', 'history']);

        return view('shop.booking-track', compact('booking'));
    }
}
