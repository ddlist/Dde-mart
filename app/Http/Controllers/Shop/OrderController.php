<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/*
 * DDE-Mart storefront — my orders (original). Scoped to the signed-in shopper
 * (customer_id, phone fallback). Includes reorder-into-cart.
 */
class OrderController extends Controller
{
    protected function mine()
    {
        $customer = Auth::guard('customer')->user();

        return Order::where(function ($q) use ($customer) {
            $q->where('customer_id', $customer->id)
                ->orWhere('customer_phone', $customer->phone);
        });
    }

    public function index(): View
    {
        $orders = $this->mine()->withCount('items')->orderByDesc('id')->paginate(10);

        return view('shop.orders', compact('orders'));
    }

    public function show(Order $order): View
    {
        $customer = Auth::guard('customer')->user();

        abort_unless(
            $order->customer_id === $customer->id || $order->customer_phone === $customer->phone,
            404
        );

        $order->load(['items', 'history']);

        return view('shop.order', compact('order'));
    }

    /** Cancel a still-placed order (before the vendor accepts it). */
    public function cancel(Order $order): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        abort_unless(
            $order->customer_id === $customer->id || $order->customer_phone === $customer->phone,
            404
        );
        abort_unless($order->status === Order::PLACED, 422, 'Only placed orders can be cancelled.');

        $order->update(['status' => Order::CANCELLED]);
        $order->history()->create(['from_status' => Order::PLACED, 'to_status' => Order::CANCELLED]);

        return redirect()->route('shop.orders.show', $order)->with('success', 'Order cancelled.');
    }

    public function reorder(Order $order): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        abort_unless(
            $order->customer_id === $customer->id || $order->customer_phone === $customer->phone,
            404
        );

        $cart = session('cart', []);

        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            $product = Product::find($item->product_id);

            if (! $product || ! $product->is_active) {
                continue;
            }

            $addonIds = collect($item->extras ?? [])->pluck('name')
                ->map(fn ($name) => $product->addons->firstWhere('name', $name)?->id)
                ->filter()->values()->all();

            $key = $product->id.':'.implode(',', $addonIds);

            if (isset($cart[$key])) {
                $cart[$key]['quantity'] = min(99, $cart[$key]['quantity'] + $item->quantity);
            } else {
                $cart[$key] = ['product_id' => $product->id, 'quantity' => $item->quantity, 'addons' => $addonIds];
            }
        }

        session(['cart' => $cart]);

        return redirect()->route('shop.cart')->with('success', 'Order items added back to cart.');
    }
}
