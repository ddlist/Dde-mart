<?php

namespace Tests\Feature\Shop;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\WalletEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart storefront tests (original): accounts, checkout, my orders.
 */
class ShopAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function loginCustomer(array $overrides = []): Customer
    {
        $customer = Customer::create(array_merge([
            'name' => 'Sara', 'phone' => '03001234567', 'password' => 'secret123',
        ], $overrides));

        $this->post(route('shop.login.attempt'), [
            'phone' => $customer->phone, 'password' => $overrides['password'] ?? 'secret123',
        ])->assertRedirect(route('shop.home'));

        return $customer;
    }

    public function test_register_login_logout_and_guards(): void
    {
        $this->post(route('shop.register.store'), [
            'name' => 'Ali', 'phone' => '03009998877',
        ])->assertRedirect(route('shop.home'));
        $this->assertTrue(Customer::where('phone', '03009998877')->exists());

        // Duplicate rejected (logged out first — guests only).
        $this->post(route('shop.logout'))->assertRedirect(route('shop.home'));
        $this->post(route('shop.register.store'), [
            'name' => 'Ali', 'phone' => '03009998877',
        ])->assertSessionHasErrors('phone');

        $this->get(route('shop.profile'))->assertRedirect(route('shop.login'));
        $this->get(route('shop.checkout'))->assertRedirect(route('shop.login'));
        $this->get(route('shop.orders'))->assertRedirect(route('shop.login'));
    }

    public function test_otp_login_flow(): void
    {
        $this->post(route('shop.login.otp'), ['phone' => '03001112233'])
            ->assertRedirect(route('shop.login'));

        $code = \App\Models\OtpCode::where('phone', '03001112233')->latest()->firstOrFail();

        // Peek the plain code via a fresh issue is impossible (hashed) — verify wrong first.
        $this->post(route('shop.login.otp.verify'), [
            'phone' => '03001112233', 'code' => '000000',
        ])->assertRedirect(route('shop.login'));

        $this->assertTrue(Customer::where('phone', '03001112233')->exists() === false);
    }

    public function test_cod_checkout_clears_cart_and_coupon(): void
    {
        $customer = $this->loginCustomer();
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 100]);
        \App\Models\Coupon::create(['code' => 'FLAT5', 'discount_type' => 'fixed', 'discount_value' => 5]);

        $this->post(route('shop.cart.add'), ['product_id' => $product->id]);
        $this->post(route('shop.cart.coupon'), ['coupon_code' => 'FLAT5']);

        $this->get(route('shop.checkout'))->assertOk()->assertSee('Cash on delivery');

        $this->post(route('shop.checkout.place'), [
            'address' => '123 Main St', 'phone' => '03001234567', 'payment_method' => 'cod',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertEquals(95, (float) $order->total);
        $this->assertEquals($customer->id, $order->customer_id);
        $this->assertEquals([], session('cart', []));
        $this->assertNull(session('shop.coupon'));

        $this->get(route('shop.orders'))->assertOk()->assertSee($order->number);
        $this->get(route('shop.orders.show', $order))->assertOk()->assertSee('Tracking');

        // Reorder puts items back.
        $this->post(route('shop.orders.reorder', $order))->assertRedirect(route('shop.cart'));
        $this->assertNotEmpty(session('cart'));
    }

    public function test_wallet_checkout_debits_and_blocks_shortfall(): void
    {
        $customer = $this->loginCustomer();
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 100]);

        WalletEntry::create([
            'owner_type' => 'customer', 'owner_ref' => $customer->phone,
            'amount' => 30, 'kind' => 'topup', 'status' => 'success',
        ]);

        $this->post(route('shop.cart.add'), ['product_id' => $product->id]);

        // Shortfall refused.
        $this->post(route('shop.checkout.place'), [
            'address' => 'A', 'phone' => $customer->phone, 'payment_method' => 'wallet',
        ])->assertRedirect(route('shop.checkout'));
        $this->assertNull(Order::first());

        WalletEntry::create([
            'owner_type' => 'customer', 'owner_ref' => $customer->phone,
            'amount' => 200, 'kind' => 'topup', 'status' => 'success',
        ]);

        $this->post(route('shop.checkout.place'), [
            'address' => 'A', 'phone' => $customer->phone, 'payment_method' => 'wallet',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertEquals('wallet', $order->payment_method);
        $this->assertEquals(130, (float) WalletEntry::sum('amount')); // 30+200-100
    }

    public function test_gateway_unconfigured_reports_cleanly(): void
    {
        $this->loginCustomer();
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 10]);
        $this->post(route('shop.cart.add'), ['product_id' => $product->id]);

        // No gateway creds in testing → clean error, no crash.
        $this->post(route('shop.checkout.place'), [
            'address' => 'A', 'phone' => '03001234567', 'payment_method' => 'stripe',
        ])->assertRedirect(route('shop.checkout'));
        $this->assertNull(Order::first());
    }
}
