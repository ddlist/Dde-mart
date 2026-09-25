<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OrderResource;
use App\Models\Coupon;
use App\Models\Order;
use App\Services\CartQuote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/*
 * DDE-Mart API — cart quote, coupon validate, checkout (original).
 * Checkout persists snapshot rows; totals always recomputed server-side.
 */
class CheckoutController extends Controller
{
    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'items.*.addons' => ['nullable', 'array'],
            'items.*.addons.*' => ['integer'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $quote = CartQuote::build($validated['items'], $validated['coupon_code'] ?? null);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $quote]);
    }

    public function validateCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $coupon = Coupon::where('code', strtoupper($validated['code']))->first();

        if (! $coupon) {
            return response()->json(['message' => 'Unknown coupon.'], 404);
        }

        $discount = $coupon->calculateDiscount((float) $validated['subtotal']);

        if ($discount <= 0) {
            return response()->json(['message' => 'Coupon not usable for this cart.'], 422);
        }

        return response()->json(['data' => [
            'code' => $coupon->code,
            'discount' => $discount,
        ]]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'items.*.addons' => ['nullable', 'array'],
            'items.*.addons.*' => ['integer'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['required', 'string', 'in:cod,wallet,card,online'],
            'address' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);

        try {
            $quote = CartQuote::build($validated['items'], $validated['coupon_code'] ?? null);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $customer = $request->user();

        // Single-store carts link the order to that store (vendor inbox).
        $vendorIds = \App\Models\Product::whereIn('id', collect($quote['lines'])->pluck('product_id'))
            ->whereNotNull('vendor_id')->distinct()->pluck('vendor_id');

        $order = Order::create([
            'type' => 'food',
            'customer_id' => $customer->id,
            'vendor_id' => $vendorIds->count() === 1 ? $vendorIds->first() : null,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'address' => $validated['address'] ?? null,
            'payment_method' => $validated['payment_method'],
            'subtotal' => $quote['subtotal'],
            'discount' => $quote['discount'],
            'delivery_charge' => $quote['delivery'],
            'tax' => $quote['tax'],
            'total' => $quote['total'],
            'coupon_code' => $quote['coupon']['code'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
        ]);

        foreach ($quote['lines'] as $line) {
            $order->items()->create([
                'product_id' => $line['product_id'],
                'name' => $line['name'],
                'price' => $line['price'],
                'quantity' => $line['quantity'],
                'extras' => $line['extras'],
                'subtotal' => $line['subtotal'],
            ]);
        }

        $order->history()->create(['from_status' => null, 'to_status' => 'placed']);

        if (! empty($quote['coupon']['id'])) {
            Coupon::where('id', $quote['coupon']['id'])->increment('used_count');
        }

        $order->load(['items', 'history']);

        return response()->json(['data' => new OrderResource($order)], 201);
    }
}
