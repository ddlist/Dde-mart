<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\WalletEntry;
use App\Services\CartQuote;
use App\Services\ShopPayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;

/*
 * DDE-Mart storefront — unified checkout (original).
 * ONE flow for all verticals: quote → pay (cod/wallet/gateway redirect) →
 * verify → order. Totals always recomputed server-side.
 */
class CheckoutController extends Controller
{
    public function index(ShopPayments $payments): View|RedirectResponse
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return redirect()->route('shop.cart')->with('error', 'Your cart is empty.');
        }

        try {
            $quote = CartQuote::build($cart, session('shop.coupon'));
        } catch (InvalidArgumentException $e) {
            return redirect()->route('shop.cart')->with('error', $e->getMessage());
        }

        $customer = Auth::guard('customer')->user();
        $balance = $this->balanceFor($customer);

        return view('shop.checkout', [
            'quote' => $quote,
            'methods' => $payments->methods(),
            'balance' => $balance,
            'customer' => $customer,
        ]);
    }

    public function place(Request $request, ShopPayments $payments): RedirectResponse
    {
        $validated = $request->validate([
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:50'],
            'payment_method' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);

        $customer = Auth::guard('customer')->user();

        try {
            $quote = CartQuote::build(session('cart', []), session('shop.coupon'));
        } catch (InvalidArgumentException $e) {
            return redirect()->route('shop.cart')->with('error', $e->getMessage());
        }

        $method = $validated['payment_method'];

        if ($method === 'wallet') {
            return $this->payWithWallet($customer, $quote, $validated);
        }

        if (in_array($method, ['stripe', 'razorpay', 'paypal'], true)) {
            return $this->startOnline($payments, $method, $quote, $validated);
        }

        if ($method !== 'cod') {
            return redirect()->route('shop.checkout')->with('error', 'Unknown payment method.');
        }

        $order = $this->createOrder($customer, $quote, $validated, 'cod', 'placed');
        $this->clearCart();

        return redirect()->route('shop.orders.show', $order)
            ->with('success', "Order {$order->number} placed.");
    }

    /** Gateway return: verify, then create the paid order from session data. */
    public function callback(Request $request, string $gateway, ShopPayments $payments): RedirectResponse
    {
        $pending = session('shop.pending');

        if (! $pending || ($pending['method'] ?? null) !== $gateway) {
            return redirect()->route('shop.cart')->with('error', 'Session expired. Please check out again.');
        }

        $reference = (string) ($request->input('session_id') ?? $request->input('razorpay_payment_link_id') ?? $request->input('token') ?? '');

        $paid = match ($gateway) {
            'stripe' => $payments->verifyStripe($reference),
            'razorpay' => $payments->verifyRazorpay($reference),
            'paypal' => $payments->verifyPaypal($reference),
            default => false,
        };

        if (! $paid) {
            return redirect()->route('shop.checkout')->with('error', 'Payment not confirmed. No charge was applied to your order.');
        }

        try {
            $quote = CartQuote::build($pending['items'], $pending['coupon'] ?? null);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('shop.cart')->with('error', 'Cart changed during payment. Please retry.');
        }

        $customer = Auth::guard('customer')->user();
        $order = $this->createOrder($customer, $quote, $pending['form'], $gateway, 'placed');
        $this->clearCart();
        session()->forget('shop.pending');

        return redirect()->route('shop.orders.show', $order)
            ->with('success', "Payment confirmed. Order {$order->number} placed.");
    }

    public function cancel(): RedirectResponse
    {
        session()->forget('shop.pending');

        return redirect()->route('shop.checkout')->with('error', 'Payment cancelled — nothing was charged.');
    }

    // -- internals ---------------------------------------------------------

    protected function startOnline(ShopPayments $payments, string $method, array $quote, array $form): RedirectResponse
    {
        try {
            [$url, $reference] = $payments->start(
                $method,
                $quote,
                route('shop.checkout.callback', $method),
                route('shop.checkout.cancel'),
            );
        } catch (\App\Payments\DriverNotConfigured $e) {
            return redirect()->route('shop.checkout')->with('error', $e->getMessage());
        }

        session(['shop.pending' => [
            'method' => $method,
            'reference' => $reference,
            'items' => session('cart', []),
            'coupon' => session('shop.coupon'),
            'form' => $form,
        ]]);

        return redirect()->away($url);
    }

    protected function payWithWallet($customer, array $quote, array $form): RedirectResponse
    {
        if ($this->balanceFor($customer) < $quote['total']) {
            return redirect()->route('shop.checkout')->with('error', 'Insufficient wallet balance.');
        }

        $order = $this->createOrder($customer, $quote, $form, 'wallet', 'placed');

        WalletEntry::create([
            'owner_type' => 'customer',
            'owner_ref' => $customer->phone,
            'amount' => -$quote['total'],
            'kind' => 'order',
            'method' => 'wallet',
            'status' => 'success',
            'note' => "Order {$order->number}",
            'order_ref' => $order->number,
            'occurred_at' => now(),
        ]);

        $this->clearCart();

        return redirect()->route('shop.orders.show', $order)
            ->with('success', "Paid from wallet. Order {$order->number} placed.");
    }

    protected function createOrder($customer, array $quote, array $form, string $method, string $status): Order
    {
        $vendorIds = \App\Models\Product::whereIn('id', collect($quote['lines'])->pluck('product_id'))
            ->whereNotNull('vendor_id')->distinct()->pluck('vendor_id');

        $order = Order::create([
            'type' => 'food',
            'customer_id' => $customer->id,
            'vendor_id' => $vendorIds->count() === 1 ? $vendorIds->first() : null,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $form['phone'] ?? $customer->phone,
            'address' => ['address' => $form['address'] ?? null],
            'payment_method' => $method,
            'subtotal' => $quote['subtotal'],
            'discount' => $quote['discount'],
            'delivery_charge' => $quote['delivery'],
            'tax' => $quote['tax'],
            'total' => $quote['total'],
            'coupon_code' => $quote['coupon']['code'] ?? null,
            'notes' => $form['notes'] ?? null,
            'scheduled_at' => $form['scheduled_at'] ?? null,
            'status' => $status,
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

        $order->history()->create(['from_status' => null, 'to_status' => $status]);

        app(\App\Services\WorkforceNotifier::class)->orderPlaced($order);

        if (! empty($quote['coupon']['id'])) {
            Coupon::where('id', $quote['coupon']['id'])->increment('used_count');
        }

        return $order;
    }

    protected function balanceFor($customer): float
    {
        return (float) WalletEntry::where('owner_type', 'customer')
            ->where(function ($q) use ($customer) {
                $q->where('owner_ref', $customer->phone);

                if ($customer->legacy_id) {
                    $q->orWhere('owner_ref', $customer->legacy_id);
                }
            })->sum('amount');
    }

    protected function clearCart(): void
    {
        session()->forget(['cart', 'shop.coupon']);
    }
}
