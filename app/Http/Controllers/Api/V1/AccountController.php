<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OrderResource;
use App\Models\Order;
use App\Models\WalletEntry;
use App\Services\ShopPayments;
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

        $order->load(['items', 'history', 'driver']);

        return new OrderResource($order);
    }

    /** Cancel a still-placed order (before the vendor accepts it). */
    public function cancel(Request $request, Order $order)
    {
        $customer = $request->user();

        abort_unless(
            $order->customer_id === $customer->id || $order->customer_phone === $customer->phone,
            404
        );
        abort_unless($order->status === Order::PLACED, 422, 'Only placed orders can be cancelled.');

        $order->update(['status' => Order::CANCELLED]);
        $order->history()->create(['from_status' => Order::PLACED, 'to_status' => Order::CANCELLED]);

        return new OrderResource($order->load(['items', 'history', 'driver']));
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

    /**
     * Start a wallet top-up via gateway redirect (verified callback credits).
     */
    public function topupStart(Request $request, ShopPayments $payments)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:'.max(1, (int) \App\Models\Setting::get('min_deposit', 1)), 'max:100000'],
            'method' => ['required', 'string', 'in:stripe,razorpay,paypal'],
        ]);

        $quote = [
            'lines' => [[
                'name' => 'Wallet top-up',
                'subtotal' => round((float) $validated['amount'], 2),
            ]],
            'subtotal' => round((float) $validated['amount'], 2),
            'total' => round((float) $validated['amount'], 2),
        ];

        try {
            [$url, $reference] = $payments->start(
                $validated['method'],
                $quote,
                route('api.v1.wallet.topup.callback', ['method' => $validated['method']]),
                route('api.v1.wallet.topup.callback', ['method' => $validated['method'], 'cancel' => 1]),
            );
        } catch (\App\Payments\DriverNotConfigured $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        session(['api.topup' => [
            'method' => $validated['method'],
            'reference' => $reference,
            'amount' => round((float) $validated['amount'], 2),
            'customer_id' => $request->user()->id,
        ]]);

        return response()->json(['data' => ['redirect_url' => $url, 'reference' => $reference]]);
    }

    /** Gateway return: verify, then credit the wallet once (idempotent-ish). */
    public function topupCallback(Request $request, string $method, ShopPayments $payments)
    {
        $pending = session('api.topup');

        if (! $pending || ($pending['method'] ?? null) !== $method || $request->boolean('cancel')) {
            return response()->json(['message' => 'Top-up session expired or cancelled.'], 422);
        }

        $reference = (string) ($request->input('session_id') ?? $request->input('razorpay_payment_link_id') ?? $request->input('token') ?? '');

        $paid = match ($method) {
            'stripe' => $payments->verifyStripe($reference),
            'razorpay' => $payments->verifyRazorpay($reference),
            'paypal' => $payments->verifyPaypal($reference),
            default => false,
        };

        if (! $paid) {
            return response()->json(['message' => 'Payment not confirmed.'], 422);
        }

        $customer = \App\Models\Customer::find($pending['customer_id']);
        abort_unless($customer, 404);

        $entry = WalletEntry::firstOrCreate(
            ['note' => "Top-up {$pending['reference']}"],
            [
                'owner_type' => 'customer',
                'owner_ref' => $customer->phone,
                'amount' => $pending['amount'],
                'kind' => 'topup',
                'method' => $method,
                'status' => 'success',
                'occurred_at' => now(),
            ],
        );

        session()->forget('api.topup');

        return response()->json(['data' => ['credited' => (float) $entry->amount]]);
    }
}
