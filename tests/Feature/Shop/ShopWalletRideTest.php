<?php

namespace Tests\Feature\Shop;

use App\Models\CabType;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Ride;
use App\Models\WalletEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart storefront tests (original): wallet page + top-up, ride booking
 * flow, food-order cancel.
 */
class ShopWalletRideTest extends TestCase
{
    use RefreshDatabase;

    protected function loginCustomer(): Customer
    {
        $customer = Customer::create(['name' => 'Sara', 'phone' => '03001234567', 'password' => 'secret123']);

        $this->post(route('shop.login.attempt'), [
            'phone' => '03001234567', 'password' => 'secret123',
        ]);

        return $customer;
    }

    public function test_wallet_page_renders_and_topup_reports_cleanly(): void
    {
        $this->loginCustomer();

        $this->get(route('shop.wallet'))->assertOk()->assertSee('Balance');

        WalletEntry::create([
            'owner_type' => 'customer', 'owner_ref' => '03001234567',
            'amount' => 250, 'kind' => 'topup', 'method' => 'cod',
            'status' => 'success', 'occurred_at' => now(),
        ]);

        $this->get(route('shop.wallet'))->assertOk()->assertSee('250.00');

        // No gateway creds in testing → honest redirect-back, nothing created.
        $this->post(route('shop.wallet.topup'), ['amount' => 500, 'method' => 'paypal'])
            ->assertRedirect(route('shop.wallet'))
            ->assertSessionHas('error');
        $this->assertEquals(1, WalletEntry::count());
    }

    public function test_ride_book_track_cancel(): void
    {
        $this->loginCustomer();
        CabType::create(['name' => 'Sedan', 'slug' => 'sedan', 'base_fare' => 100, 'per_km_fare' => 20, 'min_fare' => 120]);

        $this->get(route('shop.ride'))->assertOk()->assertSee('Book a ride');

        $this->post(route('shop.ride.book'), [
            'source' => 'A', 'destination' => 'B', 'cab_type_id' => 1, 'distance_km' => 5,
        ])->assertRedirect();

        $ride = Ride::firstOrFail();
        $this->assertEquals(200.0, (float) $ride->total); // 100 + 5×20

        $this->get(route('shop.ride.orders'))->assertOk()->assertSee($ride->number);
        $this->get(route('shop.ride.track', $ride))->assertOk()->assertSee('Cancel this ride');

        $this->post(route('shop.ride.cancel', $ride))->assertRedirect();
        $this->assertEquals('cancelled', $ride->fresh()->status);
    }

    public function test_food_order_cancel_flow(): void
    {
        $this->loginCustomer();
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 100]);

        $this->post(route('shop.cart.add'), ['product_id' => $product->id]);
        $this->post(route('shop.checkout.place'), [
            'address' => '123 Main St', 'phone' => '03001234567', 'payment_method' => 'cod',
        ])->assertRedirect();

        $order = Order::firstOrFail();

        // Cancel button shows while placed, order cancels cleanly.
        $this->get(route('shop.orders.show', $order))->assertOk()->assertSee('Cancel this order');
        $this->post(route('shop.orders.cancel', $order))->assertRedirect();
        $this->assertEquals('cancelled', $order->fresh()->status);
        $this->get(route('shop.orders.show', $order))->assertOk()->assertDontSee('Cancel this order');
    }
}
