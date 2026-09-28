<?php

namespace Tests\Feature\Shop;

use App\Models\Customer;
use App\Models\GiftCard;
use App\Models\GiftOrder;
use App\Models\ParcelCategory;
use App\Models\ParcelWeight;
use App\Models\Product;
use App\Models\ProviderCategory;
use App\Models\ProviderService;
use App\Models\RentalPackage;
use App\Models\RentalVehicleType;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart storefront verticals tests (original): parcel, rental, services,
 * dine-in, gifts, favorites.
 */
class ShopVerticalsTest extends TestCase
{
    use RefreshDatabase;

    protected function loginCustomer(array $overrides = []): Customer
    {
        $customer = Customer::create(array_merge([
            'name' => 'Sara', 'phone' => '03001234567', 'password' => 'secret123',
        ], $overrides));

        $this->post(route('shop.login.attempt'), [
            'phone' => $customer->phone, 'password' => 'secret123',
        ])->assertRedirect(route('shop.home'));

        return $customer;
    }

    public function test_parcel_quote_applies_delivery_minimum(): void
    {
        \App\Models\Setting::set('delivery_min', '500');
        $weight = ParcelWeight::create(['title' => 'Doc', 'delivery_charge' => 50]);

        $token = $this->postJson('/api/v1/auth/register', [
            'name' => 'Sara', 'phone' => '03001234567',
        ])->json('data.token');

        $this->postJson('/api/v1/parcel/quote', [
            'weight_id' => $weight->id, 'distance_km' => 1,
        ], ['Authorization' => 'Bearer '.$token])
            ->assertOk()->assertJsonPath('data.charge', 500);
    }

    public function test_parcel_book_and_track(): void
    {
        $this->loginCustomer();
        ParcelCategory::create(['name' => 'Docs', 'slug' => 'docs']);
        $weight = ParcelWeight::create(['title' => '5 KG', 'delivery_charge' => 50]);

        $this->get(route('shop.parcel'))->assertOk();

        $this->post(route('shop.parcel.book'), [
            'sender_name' => 'Ali', 'sender_phone' => '03001234567',
            'sender_address' => 'A street',
            'receiver_name' => 'Sara', 'receiver_phone' => '03009998877',
            'receiver_address' => 'B street',
            'weight_id' => $weight->id, 'distance_km' => 3.5,
        ])->assertRedirect();

        $order = \App\Models\ParcelOrder::firstOrFail();
        $this->assertEquals(50 + 3.5 * 2, (float) $order->total); // slab + per-km default
        $this->assertEquals('placed', $order->status);

        $this->get(route('shop.parcel.track', $order))->assertOk()->assertSee($order->number);
        $this->get(route('shop.parcel.orders'))->assertOk()->assertSee($order->number);
    }

    public function test_rental_book_and_track(): void
    {
        $this->loginCustomer();
        $type = RentalVehicleType::create(['name' => 'Sedan', 'slug' => 'sedan']);
        $package = RentalPackage::create(['name' => '2h', 'vehicle_type_id' => $type->id, 'base_fare' => 500]);

        $this->get(route('shop.rental'))->assertOk()->assertSee('2h');

        $this->post(route('shop.rental.book'), [
            'package_id' => $package->id, 'source' => 'Home',
            'booking_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $order = \App\Models\RentalOrder::firstOrFail();
        $this->assertEquals(500, (float) $order->total);
        $this->assertEquals($package->id, $order->package_id);

        $this->get(route('shop.rental.track', $order))->assertOk();
    }

    public function test_service_book_and_track(): void
    {
        $this->loginCustomer();
        $parent = ProviderCategory::create(['title' => 'Home']);
        $service = ProviderService::create([
            'title' => 'Cleaning', 'category_id' => $parent->id, 'price' => 1500,
        ]);

        $this->get(route('shop.services'))->assertOk()->assertSee('Home');
        $this->get(route('shop.services.category', $parent))->assertOk()->assertSee('Cleaning');

        $this->post(route('shop.services.book'), [
            'service_id' => $service->id, 'address' => 'Flat 1',
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $booking = \App\Models\ProviderBooking::firstOrFail();
        $this->assertEquals(1500, (float) $booking->total);
        $this->get(route('shop.bookings.track', $booking))->assertOk();
    }

    public function test_dinein_book(): void
    {
        $this->loginCustomer();
        $store = Store::create(['name' => 'S', 'slug' => 's', 'status' => 'active']);

        $this->get(route('shop.dinein'))->assertOk();

        $this->post(route('shop.dinein.book'), [
            'store_id' => $store->id, 'guests' => 4,
            'booked_for' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('shop.dinein'));

        $booking = \App\Models\TableBooking::firstOrFail();
        $this->assertEquals(4, $booking->guests);
        $this->assertEquals($store->id, $booking->store_id);
    }

    public function test_gift_buy_and_redeem_credits_wallet(): void
    {
        $customer = $this->loginCustomer();
        $card = GiftCard::create(['title' => 'Bday', 'amount' => 100]);

        $this->get(route('shop.gifts'))->assertOk()->assertSee('Bday');

        $this->post(route('shop.gifts.buy'), [
            'gift_id' => $card->id, 'payment_method' => 'cod',
        ])->assertRedirect(route('shop.gifts'));

        $order = GiftOrder::firstOrFail();
        $this->assertEquals('active', $order->status);

        $this->post(route('shop.gifts.redeem'), ['code' => $order->code])
            ->assertRedirect(route('shop.gifts'));
        $this->assertEquals('redeemed', $order->fresh()->status);
        $this->assertEquals(100, (float) \App\Models\WalletEntry::sum('amount'));

        // Double redeem refused.
        $this->post(route('shop.gifts.redeem'), ['code' => $order->code])
            ->assertRedirect(route('shop.gifts'));
    }

    public function test_favorites_toggle_and_list(): void
    {
        $this->loginCustomer();
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 10]);
        $store = Store::create(['name' => 'S', 'slug' => 's', 'status' => 'active']);

        $this->post(route('shop.favorites.toggle'), ['type' => 'product', 'id' => $product->id])
            ->assertRedirect();
        $this->post(route('shop.favorites.toggle'), ['type' => 'store', 'id' => $store->id])
            ->assertRedirect();

        $this->get(route('shop.favorites'))->assertOk()->assertSee('P')->assertSee('S');

        // Toggle off.
        $this->post(route('shop.favorites.toggle'), ['type' => 'product', 'id' => $product->id])
            ->assertRedirect();
        $this->assertEquals(1, \App\Models\Favorite::count());
    }

    public function test_verticals_require_login(): void
    {
        $this->get(route('shop.parcel'))->assertRedirect(route('shop.login'));
        $this->get(route('shop.rental'))->assertRedirect(route('shop.login'));
        $this->get(route('shop.services'))->assertRedirect(route('shop.login'));
        $this->get(route('shop.dinein'))->assertRedirect(route('shop.login'));
        $this->get(route('shop.gifts'))->assertRedirect(route('shop.login'));
        $this->get(route('shop.favorites'))->assertRedirect(route('shop.login'));
    }
}
