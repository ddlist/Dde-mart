<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Tax;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/*
 * DDE-Mart API — cart pricing (original service).
 * All money is recomputed server-side; clients never send totals.
 * Delivery = sum of distinct stores' fees; tax = all active percentage/fixed taxes.
 */
class CartQuote
{
    public static function build(array $items, ?string $couponCode = null): array
    {
        if (empty($items)) {
            throw new InvalidArgumentException('Cart is empty.');
        }

        $lines = [];
        $subtotal = 0;
        $storeIds = [];

        foreach ($items as $i => $item) {
            $product = Product::with(['addons'])->find($item['product_id'] ?? null);

            if (! $product || ! $product->is_active) {
                throw new InvalidArgumentException("Item #{$i} is unavailable.");
            }

            $quantity = max(1, min(99, (int) ($item['quantity'] ?? 1)));
            $price = $product->sellingPrice();

            $extras = [];
            $extrasTotal = 0;

            foreach ((array) ($item['addons'] ?? []) as $addonId) {
                $addon = $product->addons->firstWhere('id', (int) $addonId);

                if ($addon) {
                    $extras[] = ['name' => $addon->name, 'price' => (float) $addon->price];
                    $extrasTotal += (float) $addon->price;
                }
            }

            $lineTotal = round(($price + $extrasTotal) * $quantity, 2);
            $subtotal = round($subtotal + $lineTotal, 2);

            if ($product->vendor_id) {
                $storeIds[$product->vendor_id] = true;
            }

            $lines[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $price,
                'quantity' => $quantity,
                'extras' => $extras,
                'subtotal' => $lineTotal,
            ];
        }

        $discount = 0;
        $coupon = null;

        if ($couponCode) {
            $coupon = Coupon::where('code', strtoupper($couponCode))->first();

            if ($coupon) {
                $discount = $coupon->calculateDiscount($subtotal);
            }
        }

        $delivery = self::deliveryFor(array_keys($storeIds));
        $tax = self::taxFor($subtotal - $discount);

        $total = round(max(0, $subtotal - $discount + $delivery + $tax), 2);

        return compact('lines', 'subtotal', 'discount', 'delivery', 'tax', 'total') + [
            'coupon' => $coupon?->only(['id', 'code']),
        ];
    }

    protected static function deliveryFor(array $storeIds): float
    {
        if (empty($storeIds)) {
            return 0;
        }

        return (float) \App\Models\Store::whereIn('id', $storeIds)->sum('delivery_fee');
    }

    protected static function taxFor(float $base): float
    {
        $tax = 0;

        foreach (Tax::where('is_active', true)->get() as $rule) {
            $tax += $rule->calculate(max(0, $base));
        }

        return round($tax, 2);
    }
}
