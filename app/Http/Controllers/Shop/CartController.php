<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\CartQuote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/*
 * DDE-Mart storefront — session cart (original).
 * Items live in session; every total is recomputed server-side via CartQuote.
 * Coupon code persists in session until checkout consumes it.
 */
class CartController extends Controller
{
    /** Session cart shape: [{product_id, quantity, addons:[ids]}]. */
    protected function cart(): array
    {
        return session('cart', []);
    }

    protected function save(array $cart): void
    {
        // Keep assoc keys (product:addons) — update/remove address rows by key.
        session(['cart' => $cart]);
    }

    public function index(): View
    {
        $quote = null;
        $error = null;

        try {
            $quote = CartQuote::build($this->cart(), session('shop.coupon'));

            // Re-attach session keys (product:addons) so rows address update/remove.
            foreach (array_values(array_keys($this->cart())) as $i => $key) {
                if (isset($quote['lines'][$i])) {
                    $quote['lines'][$i]['_key'] = $key;
                }
            }
        } catch (InvalidArgumentException $e) {
            $error = empty($this->cart()) ? null : $e->getMessage();
        }

        return view('shop.cart', compact('quote', 'error'));
    }

    public function add(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['integer'],
        ]);

        $cart = $this->cart();
        $key = $validated['product_id'].':'.implode(',', $validated['addons'] ?? []);

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] = min(99, $cart[$key]['quantity'] + ($validated['quantity'] ?? 1));
        } else {
            $cart[$key] = [
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'] ?? 1,
                'addons' => array_map('intval', $validated['addons'] ?? []),
            ];
        }

        $this->save($cart);

        return redirect()->route('shop.cart')->with('success', 'Added to cart.');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $cart = $this->cart();

        if (! isset($cart[$validated['key']])) {
            return redirect()->route('shop.cart');
        }

        if ($validated['quantity'] === 0) {
            unset($cart[$validated['key']]);
        } else {
            $cart[$validated['key']]['quantity'] = $validated['quantity'];
        }

        $this->save($cart);

        return redirect()->route('shop.cart');
    }

    public function remove(Request $request): RedirectResponse
    {
        $validated = $request->validate(['key' => ['required', 'string']]);

        $cart = $this->cart();
        unset($cart[$validated['key']]);
        $this->save($cart);

        return redirect()->route('shop.cart');
    }

    public function coupon(Request $request): RedirectResponse
    {
        $validated = $request->validate(['coupon_code' => ['nullable', 'string', 'max:50']]);

        if (empty($validated['coupon_code'])) {
            session()->forget('shop.coupon');

            return redirect()->route('shop.cart')->with('success', 'Coupon removed.');
        }

        session(['shop.coupon' => strtoupper($validated['coupon_code'])]);

        return redirect()->route('shop.cart')->with('success', 'Coupon applied — verify the totals.');
    }
}
