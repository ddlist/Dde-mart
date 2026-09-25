<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OrderResource;
use App\Models\Order;
use App\Models\WalletEntry;
use Illuminate\Http\Request;

/*
 * DDE-Mart API — customer self-service (original): my orders + wallet.
 * Orders scope to customer_id (new) with phone fallback (imported rows).
 */
class AccountController extends Controller
{
    public function orders(Request $request)
    {
        $customer = $request->user();

        $orders = Order::where(function ($q) use ($customer) {
            $q->where('customer_id', $customer->id)
                ->orWhere('customer_phone', $customer->phone);
        })
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return OrderResource::collection($orders);
    }

    public function order(Request $request, Order $order)
    {
        $customer = $request->user();

        abort_unless(
            $order->customer_id === $customer->id || $order->customer_phone === $customer->phone,
            404
        );

        $order->load(['items', 'history']);

        return new OrderResource($order);
    }

    public function wallet(Request $request)
    {
        $customer = $request->user();

        $entries = WalletEntry::where(function ($q) use ($customer) {
            $q->where('owner_ref', $customer->phone);

            if ($customer->legacy_id) {
                $q->orWhere('owner_ref', $customer->legacy_id);
            }
        })
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        $balance = WalletEntry::where(function ($q) use ($customer) {
            $q->where('owner_ref', $customer->phone);

            if ($customer->legacy_id) {
                $q->orWhere('owner_ref', $customer->legacy_id);
            }
        })->sum('amount');

        return response()->json([
            'data' => $entries->items(),
            'meta' => [
                'balance' => (float) $balance,
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'total' => $entries->total(),
            ],
        ]);
    }
}
