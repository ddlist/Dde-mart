<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PayoutRequest;
use App\Models\Product;
use App\Models\Store;
use App\Models\TableBooking;
use App\Support\Images;
use Illuminate\Http\Request;

/*
 * DDE-Mart API — vendor/owner surfaces (original). Abilities: vendor, owner.
 * Stores owned by the account, their orders/products, payout requests.
 */
class VendorApiController extends Controller
{
    protected function storesOf(Request $request)
    {
        return Store::where('owner_id', $request->user()->id)->pluck('id');
    }

    public function stores(Request $request)
    {
        $stores = Store::where('owner_id', $request->user()->id)->withCount('products')->get();

        return response()->json(['data' => $stores->map(fn ($s) => [
            'id' => $s->id, 'name' => $s->name, 'slug' => $s->slug,
            'status' => $s->status, 'is_open' => (bool) $s->is_open,
            'image' => Images::url($s->image_path),
            'products_count' => $s->products_count,
        ])]);
    }

    public function orders(Request $request)
    {
        $storeIds = $this->storesOf($request);

        $orders = Order::whereIn('vendor_id', $storeIds)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $orders->map(fn ($o) => [
                'id' => $o->id, 'number' => $o->number,
                'customer' => $o->customer_name, 'total' => (float) $o->total,
                'status' => $o->status,
                'created_at' => $o->created_at?->toIso8601String(),
            ]),
            'meta' => ['current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage(), 'total' => $orders->total()],
        ]);
    }

    public function products(Request $request)
    {
        $storeIds = $this->storesOf($request);

        $products = Product::whereIn('vendor_id', $storeIds)
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->input('q').'%'))
            ->orderBy('name')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $products->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name,
                'price' => (float) $p->price, 'quantity' => $p->quantity,
                'is_active' => (bool) $p->is_active,
                'image' => Images::url($p->image_path),
            ]),
            'meta' => ['current_page' => $products->currentPage(), 'last_page' => $products->lastPage(), 'total' => $products->total()],
        ]);
    }

    public function toggleProduct(Request $request, Product $product)
    {
        abort_unless(in_array($product->vendor_id, $this->storesOf($request)->all()), 404);

        $product->update(['is_active' => ! $product->is_active]);

        return response()->json(['data' => ['is_active' => (bool) $product->is_active]]);
    }

    public function toggleStore(Request $request, Store $store)
    {
        abort_unless($store->owner_id === $request->user()->id, 404);

        $validated = $request->validate(['is_open' => ['required', 'boolean']]);
        $store->update(['is_open' => $validated['is_open']]);

        return response()->json(['data' => ['is_open' => (bool) $store->is_open]]);
    }

    public function payouts(Request $request)
    {
        $rows = PayoutRequest::where('requester_type', 'vendor')
            ->whereIn('requester_id', $this->ownerRequesterIds($request))
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $rows->map(fn ($p) => [
                'id' => $p->id, 'amount' => (float) $p->amount,
                'method' => $p->method, 'status' => $p->status,
            ]),
            'meta' => ['current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'total' => $rows->total()],
        ]);
    }

    public function payoutRequest(Request $request)
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'string', 'in:bank,paypal,stripe,razorpay,flutterwave,cash'],
            'method_details' => ['nullable', 'array'],
        ]);

        $store = Store::find($validated['store_id']);
        abort_unless($store && $store->owner_id === $request->user()->id, 404);

        $payout = PayoutRequest::create([
            'requester_type' => 'vendor',
            'requester_id' => $store->id,
            'requester_name' => $store->name,
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'method_details' => $validated['method_details'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json(['data' => ['id' => $payout->id, 'status' => 'pending']], 201);
    }

    /** Accept or cancel an order of an owned store. */
    public function orderTransition(Request $request, Order $order)
    {
        $validated = $request->validate(['to' => ['required', 'string', 'in:accepted,cancelled']]);

        abort_unless(
            $order->vendor_id && $this->storesOf($request)->contains($order->vendor_id),
            404
        );
        abort_unless($order->canTransitionTo($validated['to']), 422, 'Illegal transition.');

        $from = $order->status;
        $order->update(['status' => $validated['to']]);
        $order->history()->create(['from_status' => $from, 'to_status' => $validated['to']]);

        return response()->json(['data' => ['status' => $validated['to']]]);
    }

    /** Table bookings for owned stores (dine-in inbox). */
    public function dinein(Request $request)
    {
        $storeIds = $this->storesOf($request);

        $bookings = TableBooking::whereIn('store_id', $storeIds)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $bookings->map(fn ($b) => [
                'id' => $b->id, 'store_id' => $b->store_id,
                'guest' => $b->guest_name, 'guests' => $b->guests,
                'booked_for' => $b->booked_for?->toIso8601String(),
                'status' => $b->status,
            ]),
            'meta' => ['current_page' => $bookings->currentPage(), 'last_page' => $bookings->lastPage(), 'total' => $bookings->total()],
        ]);
    }

    /** Confirm/seat/complete/cancel a table booking of an owned store. */
    public function dineinTransition(Request $request, TableBooking $booking)
    {
        $validated = $request->validate([
            'to' => ['required', 'string', 'in:confirmed,seated,completed,cancelled'],
        ]);

        abort_unless(
            $booking->store_id && $this->storesOf($request)->contains($booking->store_id),
            404
        );
        abort_unless($booking->canTransitionTo($validated['to']), 422, 'Illegal transition.');

        $booking->update(['status' => $validated['to']]);

        return response()->json(['data' => ['status' => $validated['to']]]);
    }

    /** Owner id + owned store ids (payouts may reference either). */
    protected function ownerRequesterIds(Request $request): array
    {
        return array_merge(
            [$request->user()->id],
            $this->storesOf($request)->all(),
        );
    }
}
