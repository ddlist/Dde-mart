<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\GiftCard;
use App\Models\GiftOrder;
use App\Models\Product;
use App\Models\ProviderBooking;
use App\Models\ProviderCategory;
use App\Models\ProviderService;
use App\Models\Store;
use App\Models\TableBooking;
use App\Models\WalletEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/*
 * DDE-Mart API — services, dine-in, gifts, favorites (original).
 * Customer-scoped bookings with timelines; gift buy/redeem against wallet.
 */
class LifeApiController extends Controller
{
    // -- Services --------------------------------------------------------

    public function serviceCategories()
    {
        $categories = ProviderCategory::with(['children'])
            ->where('is_active', true)->whereNull('parent_id')
            ->orderBy('title')->get(['id', 'title']);

        return response()->json(['data' => $categories]);
    }

    public function providerServices(Request $request)
    {
        $services = ProviderService::with(['provider:id,name'])
            ->where('is_active', true)
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->input('category_id')))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->input('q').'%'))
            ->orderBy('title')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $services->map(fn ($s) => [
                'id' => $s->id, 'title' => $s->title,
                'description' => $s->description,
                'price' => (float) $s->price,
                'provider' => $s->provider?->only(['id', 'name']),
            ]),
            'meta' => ['current_page' => $services->currentPage(), 'last_page' => $services->lastPage(), 'total' => $services->total()],
        ]);
    }

    public function serviceBook(Request $request)
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:provider_services,id'],
            'address' => ['required', 'string', 'max:500'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $service = ProviderService::findOrFail($validated['service_id']);
        abort_unless($service->is_active, 422, 'Service unavailable.');

        $customer = $request->user();

        $booking = ProviderBooking::create([
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
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

        return response()->json(['data' => ['id' => $booking->id, 'number' => $booking->number]], 201);
    }

    public function serviceBookings(Request $request)
    {
        $bookings = ProviderBooking::where('customer_phone', $request->user()->phone)
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $bookings->map(fn ($b) => [
                'id' => $b->id, 'number' => $b->number, 'status' => $b->status,
                'total' => (float) $b->total,
                'scheduled_at' => $b->scheduled_at?->toIso8601String(),
            ]),
            'meta' => ['current_page' => $bookings->currentPage(), 'last_page' => $bookings->lastPage(), 'total' => $bookings->total()],
        ]);
    }

    public function serviceTrack(Request $request, ProviderBooking $booking)
    {
        abort_unless($booking->customer_phone === $request->user()->phone, 404);
        $booking->load(['history', 'provider', 'service', 'worker']);

        return response()->json(['data' => [
            'id' => $booking->id, 'number' => $booking->number, 'status' => $booking->status,
            'total' => (float) $booking->total,
            'provider' => $booking->provider?->only(['id', 'name']),
            'service' => $booking->service?->only(['id', 'title']),
            'worker' => $booking->worker?->only(['id', 'name']),
            'timeline' => $booking->history->map(fn ($h) => [
                'from' => $h->from_status, 'to' => $h->to_status,
                'at' => $h->created_at?->toIso8601String(),
            ]),
        ]]);
    }

    // -- Dine-in ---------------------------------------------------------

    public function dineinBook(Request $request)
    {
        $validated = $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'guests' => ['required', 'integer', 'min:1', 'max:30'],
            'booked_for' => ['required', 'date', 'after:now'],
            'occasion' => ['nullable', 'string', 'max:150'],
            'special_request' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer = $request->user();

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

        return response()->json(['data' => ['id' => $booking->id, 'status' => 'pending']], 201);
    }

    public function dineinMine(Request $request)
    {
        $bookings = TableBooking::where('guest_phone', $request->user()->phone)
            ->orderByDesc('booked_for')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $bookings->map(fn ($b) => [
                'id' => $b->id, 'guests' => $b->guests,
                'booked_for' => $b->booked_for?->toIso8601String(),
                'status' => $b->status,
            ]),
            'meta' => ['current_page' => $bookings->currentPage(), 'last_page' => $bookings->lastPage(), 'total' => $bookings->total()],
        ]);
    }

    // -- Gifts -----------------------------------------------------------

    public function giftCards()
    {
        return response()->json(['data' => GiftCard::where('is_active', true)
            ->orderBy('amount')->get(['id', 'title', 'amount'])]);
    }

    public function giftBuy(Request $request)
    {
        $validated = $request->validate([
            'gift_id' => ['required', 'integer', 'exists:gift_cards,id'],
            'payment_method' => ['required', 'string', 'in:cod,wallet'],
        ]);

        $card = GiftCard::findOrFail($validated['gift_id']);
        abort_unless($card->is_active, 422, 'Card unavailable.');

        $customer = $request->user();

        if ($validated['payment_method'] === 'wallet') {
            $balance = (float) WalletEntry::where('owner_type', 'customer')
                ->where('owner_ref', $customer->phone)->sum('amount');

            abort_unless($balance >= (float) $card->amount, 422, 'Insufficient wallet balance.');

            WalletEntry::create([
                'owner_type' => 'customer', 'owner_ref' => $customer->phone,
                'amount' => -$card->amount, 'kind' => 'order', 'method' => 'wallet',
                'status' => 'success', 'note' => "Gift card {$card->title}",
                'occurred_at' => now(),
            ]);
        }

        $order = GiftOrder::create([
            'gift_id' => $card->id,
            'code' => 'GIFT-'.strtoupper(Str::random(8)),
            'buyer_ref' => $customer->phone,
            'amount' => $card->amount,
            'status' => 'active',
            'expires_at' => now()->addDays($card->expiry_days),
        ]);

        return response()->json(['data' => ['code' => $order->code, 'amount' => (float) $order->amount]], 201);
    }

    public function giftRedeem(Request $request)
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:50']]);

        $order = GiftOrder::where('code', strtoupper($validated['code']))->first();
        abort_unless($order && $order->status === 'active', 422, 'Invalid or used code.');

        if ($order->expires_at && $order->expires_at->isPast()) {
            $order->update(['status' => 'expired']);

            return response()->json(['message' => 'Code expired.'], 422);
        }

        $customer = $request->user();
        $order->update(['status' => 'redeemed']);

        WalletEntry::create([
            'owner_type' => 'customer', 'owner_ref' => $customer->phone,
            'amount' => $order->amount, 'kind' => 'topup', 'method' => 'gift',
            'status' => 'success', 'note' => "Redeemed {$order->code}",
            'occurred_at' => now(),
        ]);

        return response()->json(['data' => ['credited' => (float) $order->amount]]);
    }

    // -- Favorites -------------------------------------------------------

    public function favorites(Request $request)
    {
        $favorites = Favorite::where('customer_id', $request->user()->id)->get();

        $products = Product::whereIn('id', $favorites->where('favorite_type', 'product')->pluck('favorite_id'))
            ->where('is_active', true)->get(['id', 'name', 'price']);
        $stores = Store::whereIn('id', $favorites->where('favorite_type', 'store')->pluck('favorite_id'))
            ->where('status', 'active')->get(['id', 'name']);

        return response()->json(['data' => ['products' => $products, 'stores' => $stores]]);
    }

    public function favoriteToggle(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:product,store'],
            'id' => ['required', 'integer'],
        ]);

        $exists = $validated['type'] === 'product'
            ? Product::where('id', $validated['id'])->exists()
            : Store::where('id', $validated['id'])->exists();

        abort_unless($exists, 404);

        $favorite = Favorite::where('customer_id', $request->user()->id)
            ->where('favorite_type', $validated['type'])
            ->where('favorite_id', $validated['id'])
            ->first();

        if ($favorite) {
            $favorite->delete();

            return response()->json(['data' => ['saved' => false]]);
        }

        Favorite::create([
            'customer_id' => $request->user()->id,
            'favorite_type' => $validated['type'],
            'favorite_id' => $validated['id'],
        ]);

        return response()->json(['data' => ['saved' => true]]);
    }
}
