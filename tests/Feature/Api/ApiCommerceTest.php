<?php

namespace Tests\Feature\Api;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\Tax;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart API — commerce tests (original): quote, coupons, checkout, my-orders, wallet.
 */
class ApiCommerceTest extends TestCase
{
    use RefreshDatabase;

    protected function customer(array $overrides = []): array
    {
        $response = $this->postJson('/api/v1/auth/register', array_merge([
            'name' => 'Sara', 'phone' => '03001234567',
        ], $overrides));

        return ['token' => $response->json('data.token'), 'id' => $response->json('data.id')];
    }

    protected function auth(array $customer): array
    {
        return ['Authorization' => 'Bearer '.$customer['token']];
    }

    public function test_quote_math_with_addons_coupon_tax(): void
    {
        $store = Store::create(['name' => 'S', 'slug' => 's', 'status' => 'active', 'delivery_fee' => 20]);
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 100, 'vendor_id' => $store->id]);
        $addon = $product->addons()->create(['name' => 'X', 'price' => 10]);
        Tax::create(['country' => 'Pakistan', 'title' => 'GST', 'type' => 'percentage', 'value' => 10]);
        Coupon::create(['code' => 'BIG20', 'discount_type' => 'percentage', 'discount_value' => 20, 'max_discount' => 50]);

        $response = $this->postJson('/api/v1/cart/quote', [
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'addons' => [$addon->id]]],
            'coupon_code' => 'big20',
        ], $this->auth($this->customer()))->assertOk();

        // (100+10)*2=220 subtotal; 20% = 44 (under the 50 cap); delivery 20; tax 10% of 176 = 17.6.
        $this->assertEquals(220, $response->json('data.subtotal'));
        $this->assertEquals(44, $response->json('data.discount'));
        $this->assertEquals(20, $response->json('data.delivery'));
        $this->assertEquals(17.6, $response->json('data.tax'));
        $this->assertEquals(213.6, $response->json('data.total'));
    }

    public function test_vendor_scoped_coupon_applies_only_to_own_store(): void
    {
        $mine = Store::create(['name' => 'Mine', 'slug' => 'mine', 'status' => 'active']);
        $theirs = Store::create(['name' => 'Theirs', 'slug' => 'theirs', 'status' => 'active']);
        $product = Product::create(['name' => 'P', 'slug' => 'p9', 'price' => 100, 'vendor_id' => $mine->id]);
        Coupon::create([
            'code' => 'MINE10', 'discount_type' => 'percentage', 'discount_value' => 10,
            'vendor_id' => $theirs->id,
        ]);

        $auth = $this->auth($this->customer());

        // Other store's coupon: no discount.
        $this->postJson('/api/v1/cart/quote', [
            'items' => [['product_id' => $product->id]],
            'coupon_code' => 'mine10',
        ], $auth)->assertOk()->assertJsonPath('data.discount', 0);

        // Unscoped coupon: applies.
        Coupon::create(['code' => 'ANY10', 'discount_type' => 'percentage', 'discount_value' => 10]);
        $this->postJson('/api/v1/cart/quote', [
            'items' => [['product_id' => $product->id]],
            'coupon_code' => 'any10',
        ], $auth)->assertOk()->assertJsonPath('data.discount', 10);
    }

    public function test_quote_rejects_bad_items(): void
    {
        $auth = $this->auth($this->customer());

        $this->postJson('/api/v1/cart/quote', ['items' => []], $auth)->assertStatus(422);
        $this->postJson('/api/v1/cart/quote', [
            'items' => [['product_id' => 999, 'quantity' => 1]],
        ], $auth)->assertStatus(422);
    }

    public function test_coupon_validate_endpoint(): void
    {
        $auth = $this->auth($this->customer());
        Coupon::create(['code' => 'FLAT5', 'discount_type' => 'fixed', 'discount_value' => 5]);

        $this->postJson('/api/v1/coupons/validate', ['code' => 'FLAT5', 'subtotal' => 100], $auth)
            ->assertOk()->assertJsonPath('data.discount', 5);

        $this->postJson('/api/v1/coupons/validate', ['code' => 'NOPE', 'subtotal' => 100], $auth)
            ->assertStatus(404);
    }

    public function test_checkout_creates_snapshot_order(): void
    {
        $auth = $this->auth($this->customer());
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 100]);
        $coupon = Coupon::create(['code' => 'FLAT5', 'discount_type' => 'fixed', 'discount_value' => 5]);

        $response = $this->postJson('/api/v1/checkout', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'coupon_code' => 'FLAT5',
            'payment_method' => 'cod',
            'address' => ['address' => '123 Main', 'locality' => 'Town'],
            'total' => 1, // ignored: server recomputes
        ], $auth)->assertCreated();

        $order = Order::firstOrFail();
        $this->assertEquals(95, (float) $order->total);
        $this->assertEquals('FLAT5', $order->coupon_code);
        $this->assertEquals('placed', $order->status);
        $this->assertCount(1, $order->items);
        $this->assertEquals('P', $order->items->first()->name); // snapshot
        $this->assertEquals(1, $coupon->fresh()->used_count);
        $this->assertMatchesRegularExpression('/^DDE-\d{6}-\d{5}$/', $response->json('data.number'));
    }

    public function test_my_orders_scoped_to_customer(): void
    {
        $mine = $this->auth($this->customer(['phone' => '03001']));
        $theirs = $this->auth($this->customer(['phone' => '03002', 'name' => 'Ali']));

        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 10]);

        $this->postJson('/api/v1/checkout', [
            'items' => [['product_id' => $product->id]], 'payment_method' => 'cod',
        ], $mine)->assertCreated();

        $list = $this->getJson('/api/v1/orders', $mine)->assertOk();
        $this->assertCount(1, $list->json('data'));

        // NOTE: the auth guard caches the resolved user per test process —
        // forget guards when switching identities in one test.
        \Illuminate\Support\Facades\Auth::forgetGuards();

        $this->getJson('/api/v1/orders', $theirs)->assertOk()->assertJsonCount(0, 'data');

        // Other customer's order detail is 404, not 403 (no id oracle).
        $orderId = Order::firstOrFail()->id;
        $this->getJson("/api/v1/orders/{$orderId}", $theirs)->assertStatus(404);

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->getJson("/api/v1/orders/{$orderId}", $mine)->assertOk();
    }

    public function test_wallet_read(): void
    {
        $auth = $this->auth($this->customer(['phone' => '03001']));
        \App\Models\WalletEntry::create([
            'owner_type' => 'customer', 'owner_ref' => '03001',
            'amount' => 25, 'kind' => 'topup', 'status' => 'success',
        ]);

        $response = $this->getJson('/api/v1/wallet', $auth)->assertOk();
        $this->assertEquals(25, $response->json('meta.balance'));
    }

    public function test_commerce_requires_auth(): void
    {
        $this->postJson('/api/v1/cart/quote', ['items' => []])->assertStatus(401);
        $this->postJson('/api/v1/checkout', ['items' => []])->assertStatus(401);
        $this->getJson('/api/v1/orders')->assertStatus(401);
        $this->getJson('/api/v1/wallet')->assertStatus(401);
    }
}
