<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ItemReview;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/*
 * DDE-Mart API — customer review submit (original).
 * Reviews land pending for staff moderation. One review per order+product pair.
 */
class ReviewApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer = $request->user();

        $order = Order::find($validated['order_id']);

        abort_unless(
            $order->customer_id === $customer->id || $order->customer_phone === $customer->phone,
            404
        );

        abort_unless($order->status === 'completed', 422, 'Only completed orders can be reviewed.');

        // Canonical rule: one review per customer per product.
        if (ItemReview::where('product_id', $validated['product_id'])
            ->where('author_name', $customer->name)->exists()) {
            return response()->json(['message' => 'Already reviewed.'], 422);
        }

        $product = \App\Models\Product::find($validated['product_id']);

        $review = ItemReview::create([
            'product_id' => $validated['product_id'],
            'store_id' => $product?->vendor_id,
            'author_name' => $customer->name,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json(['data' => ['id' => $review->id, 'status' => $review->status]], 201);
    }

    public function mine(Request $request)
    {
        $reviews = ItemReview::where('author_name', $request->user()->name)
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $reviews->map(fn ($r) => [
                'id' => $r->id, 'product_id' => $r->product_id,
                'rating' => $r->rating, 'comment' => $r->comment,
                'status' => $r->status,
            ]),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }
}
