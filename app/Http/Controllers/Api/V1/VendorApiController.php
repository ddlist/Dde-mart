<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatThread;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\PlanSubscription;
use App\Models\SubscriptionPlan;
use App\Models\PayoutRequest;
use App\Models\Product;
use App\Models\Store;
use App\Models\TableBooking;
use App\Support\ImageUploads;
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

    /** Create a product in an owned store (image via multipart). */
    public function productStore(Request $request)
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'veg' => ['nullable', 'boolean'],
            'is_takeaway' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        abort_unless($this->storesOf($request)->contains($validated['store_id']), 404);

        $product = Product::create(collect($validated)->except('image')->all() + [
            'vendor_id' => $validated['store_id'],
            'image_path' => ImageUploads::store($request->file('image'), 'products'),
        ]);

        return response()->json(['data' => ['id' => $product->id, 'name' => $product->name]], 201);
    }

    /** Update a product of an owned store (image replace/remove supported). */
    public function productUpdate(Request $request, Product $product)
    {
        abort_unless($product->vendor_id && $this->storesOf($request)->contains($product->vendor_id), 404);

        $validated = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['sometimes', 'numeric', 'min:0', 'max:1000000'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'veg' => ['nullable', 'boolean'],
            'is_takeaway' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        if (array_key_exists('discount_price', $validated) && $validated['discount_price'] !== null) {
            $price = $validated['price'] ?? $product->price;
            abort_unless($validated['discount_price'] < $price, 422, 'Discount must be below price.');
        }

        $data = collect($validated)->except(['image', 'remove_image'])->all();

        if ($request->boolean('remove_image')) {
            ImageUploads::delete($product->image_path);
            $data['image_path'] = null;
        } elseif ($request->hasFile('image')) {
            $data['image_path'] = ImageUploads::replace($request->file('image'), $product->image_path, 'products');
        }

        $product->update($data);

        return response()->json(['data' => ['id' => $product->id, 'name' => $product->fresh()->name]]);
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

    /** Coupons funded by this vendor (own stores, food scope). */
    public function coupons(Request $request)
    {
        $coupons = Coupon::whereIn('vendor_id', $this->storesOf($request))
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $coupons->map(fn ($c) => [
                'id' => $c->id, 'code' => $c->code,
                'discount_type' => $c->discount_type,
                'discount_value' => (float) $c->discount_value,
                'min_order' => (float) $c->min_order,
                'usage_limit' => $c->usage_limit, 'used_count' => $c->used_count,
                'is_active' => (bool) $c->is_active,
                'expires_at' => $c->expires_at?->toIso8601String(),
            ]),
            'meta' => ['current_page' => $coupons->currentPage(), 'last_page' => $coupons->lastPage(), 'total' => $coupons->total()],
        ]);
    }

    public function couponStore(Request $request)
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'code' => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'discount_type' => ['required', 'string', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        abort_unless($this->storesOf($request)->contains($validated['store_id']), 404);
        $validated['code'] = strtoupper($validated['code']);
        abort_if(
            Coupon::where('code', $validated['code'])->exists(),
            422, 'Code already taken.'
        );

        $coupon = Coupon::create($validated + [
            'vendor_id' => $validated['store_id'],
            'scope' => 'food',
            'is_public' => true,
            'is_active' => true,
        ]);

        return response()->json(['data' => ['id' => $coupon->id, 'code' => $coupon->code]], 201);
    }

    public function couponUpdate(Request $request, Coupon $coupon)
    {
        abort_unless(
            $coupon->vendor_id && $this->storesOf($request)->contains($coupon->vendor_id),
            404
        );

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
            'discount_value' => ['sometimes', 'numeric', 'min:0'],
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $coupon->update($validated);

        return response()->json(['data' => ['id' => $coupon->id, 'is_active' => (bool) $coupon->fresh()->is_active]]);
    }

    /** Support threads linked to owned stores (customer opened, staff/vendor answer). */
    public function chatThreads(Request $request)
    {
        $threads = ChatThread::whereIn('vendor_id', $this->storesOf($request))
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $threads->map(fn ($t) => [
                'id' => $t->id, 'subject' => $t->subject, 'status' => $t->status,
                'order_ref' => $t->order_ref, 'last_message' => $t->last_message,
            ]),
            'meta' => ['current_page' => $threads->currentPage(), 'last_page' => $threads->lastPage(), 'total' => $threads->total()],
        ]);
    }

    public function chatShow(Request $request, ChatThread $thread)
    {
        abort_unless(
            $thread->vendor_id && $this->storesOf($request)->contains($thread->vendor_id),
            404
        );

        $thread->load(['messages']);

        return response()->json(['data' => [
            'id' => $thread->id, 'subject' => $thread->subject, 'status' => $thread->status,
            'order_ref' => $thread->order_ref,
            'messages' => $thread->messages->map(fn ($m) => [
                'id' => $m->id,
                'from_me' => str_starts_with($m->sender_ref ?? '', 'vendor:'),
                'body' => $m->body,
                'at' => $m->sent_at?->toIso8601String(),
            ]),
        ]]);
    }

    public function chatReply(Request $request, ChatThread $thread)
    {
        abort_unless(
            $thread->vendor_id && $this->storesOf($request)->contains($thread->vendor_id),
            404
        );
        abort_unless($thread->status === 'open', 422, 'Thread is closed.');

        $validated = $request->validate(['message' => ['required', 'string', 'max:2000']]);

        $message = $thread->messages()->create([
            'sender_ref' => 'vendor:'.$request->user()->id,
            'body' => $validated['message'],
            'sent_at' => now(),
        ]);
        $thread->update(['last_message' => substr($validated['message'], 0, 500)]);

        return response()->json(['data' => ['message_id' => $message->id]], 201);
    }

    /** Subscription plans + my current subscription (purchase stays panel-side). */
    public function subscription(Request $request)
    {
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('price')->get();

        $mine = PlanSubscription::where('subscriber_type', 'owner')
            ->whereIn('subscriber_id', $this->ownerRequesterIds($request))
            ->where('status', 'active')
            ->orderByDesc('ends_at')
            ->first();

        return response()->json(['data' => [
            'plans' => $plans->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name, 'price' => (float) $p->price,
                'validity_days' => $p->validity_days, 'features' => $p->features ?? [],
            ]),
            'mine' => $mine ? [
                'plan' => $mine->plan?->only(['id', 'name']),
                'status' => $mine->status,
                'ends_at' => $mine->ends_at?->toIso8601String(),
                'expired' => $mine->ends_at !== null && $mine->ends_at->isPast(),
            ] : null,
        ]]);
    }
}
