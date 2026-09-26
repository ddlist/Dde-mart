<?php

namespace Tests\Feature\Api;

use App\Models\Driver;
use App\Models\GiftCard;
use App\Models\Order;
use App\Models\Owner;
use App\Models\ParcelOrder;
use App\Models\ParcelWeight;
use App\Models\Product;
use App\Models\ProviderCategory;
use App\Models\ProviderService;
use App\Models\RentalOrder;
use App\Models\RentalPackage;
use App\Models\RentalVehicleType;
use App\Models\Ride;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/*
 * DDE-Mart API — vertical bookings + job actions tests (original).
 */
class ApiVerticalsTest extends TestCase
{
    use RefreshDatabase;

    protected function customer(array $overrides = []): array
    {
        $response = $this->postJson('/api/v1/auth/register', array_merge([
            'name' => 'Sara', 'phone' => '03001234567',
        ], $overrides));

        return ['token' => $response->json('data.token')];
    }

    protected function driver(string $phone = '03009998877'): array
    {
        Driver::create(['name' => 'Rider', 'phone' => $phone, 'status' => 'active']);
        $req = $this->postJson('/api/v1/work/auth/otp/request', ['phone' => $phone, 'role' => 'driver']);
        $verify = $this->postJson('/api/v1/work/auth/otp/verify', [
            'phone' => $phone, 'role' => 'driver', 'code' => $req->json('data.debug_code'),
        ]);

        return ['token' => $verify->json('data.token')];
    }

    public function test_parcel_book_track_cancel(): void
    {
        $auth = ['Authorization' => 'Bearer '.$this->customer()['token']];
        ParcelWeight::create(['title' => '5 KG', 'delivery_charge' => 50]);

        $meta = $this->getJson('/api/v1/parcel/meta', $auth)->assertOk();
        $this->assertEquals(2.0, $meta->json('data.per_km'));

        $quote = $this->postJson('/api/v1/parcel/quote', [
            'weight_id' => 1, 'distance_km' => 4,
        ], $auth)->assertOk();
        $this->assertEquals(58, $quote->json('data.charge'));

        $book = $this->postJson('/api/v1/parcel/book', [
            'sender_name' => 'Sara', 'sender_address' => 'A',
            'receiver_name' => 'Ali', 'receiver_phone' => '03002',
            'receiver_address' => 'B', 'weight_id' => 1, 'distance_km' => 4,
        ], $auth)->assertCreated();

        $order = ParcelOrder::firstOrFail();
        $this->assertEquals(58.0, (float) $order->total);

        $this->getJson("/api/v1/parcel/orders/{$order->id}", $auth)->assertOk();
        $this->postJson("/api/v1/parcel/orders/{$order->id}/cancel", [], $auth)->assertOk();
        $this->assertEquals('cancelled', $order->fresh()->status);
        $this->assertCount(2, $order->fresh()->history); // placed + cancelled
    }

    public function test_rental_and_ride_flows(): void
    {
        $auth = ['Authorization' => 'Bearer '.$this->customer()['token']];
        $type = RentalVehicleType::create(['name' => 'Sedan', 'slug' => 'sedan']);
        $package = RentalPackage::create(['name' => '2h', 'vehicle_type_id' => $type->id, 'base_fare' => 500]);

        $this->getJson('/api/v1/rental/meta', $auth)->assertOk();

        $this->postJson('/api/v1/rental/book', [
            'package_id' => $package->id, 'source' => 'Home',
            'booking_at' => now()->addDay()->toIso8601String(),
        ], $auth)->assertCreated();

        $rental = RentalOrder::firstOrFail();
        $this->assertEquals($package->id, $rental->package_id);
        $this->getJson("/api/v1/rental/orders/{$rental->id}", $auth)->assertOk();

        $ride = $this->postJson('/api/v1/rides/request', [
            'source' => 'A', 'destination' => 'B',
        ], $auth)->assertCreated();
        $this->assertMatchesRegularExpression('/^CAB-DDE-/', $ride->json('data.number'));

        $rideId = $ride->json('data.id');
        $this->getJson("/api/v1/rides/{$rideId}", $auth)->assertOk();
        $this->postJson("/api/v1/rides/{$rideId}/cancel", [], $auth)->assertOk();
        $this->assertEquals('cancelled', Ride::find($rideId)->status);
    }

    public function test_service_dinein_gifts_favorites(): void
    {
        $auth = ['Authorization' => 'Bearer '.$this->customer()['token']];
        $cat = ProviderCategory::create(['title' => 'Home']);
        $service = ProviderService::create(['title' => 'Clean', 'category_id' => $cat->id, 'price' => 1500]);

        $this->getJson('/api/v1/service-categories', $auth)->assertOk();
        $this->getJson('/api/v1/services', $auth)->assertOk()->assertJsonCount(1, 'data');

        $book = $this->postJson('/api/v1/services/book', [
            'service_id' => $service->id, 'address' => 'Flat 1',
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ], $auth)->assertCreated();
        $this->getJson('/api/v1/service-bookings/'.$book->json('data.id'), $auth)->assertOk();

        $store = Store::create(['name' => 'S', 'slug' => 's', 'status' => 'active']);
        $this->postJson('/api/v1/dinein/book', [
            'store_id' => $store->id, 'guests' => 2,
            'booked_for' => now()->addDay()->toIso8601String(),
        ], $auth)->assertCreated();
        $this->getJson('/api/v1/dinein/bookings', $auth)->assertOk()->assertJsonCount(1, 'data');

        $card = GiftCard::create(['title' => 'Bday', 'amount' => 100]);
        $this->getJson('/api/v1/gift-cards', $auth)->assertOk();
        $buy = $this->postJson('/api/v1/gifts/buy', [
            'gift_id' => $card->id, 'payment_method' => 'cod',
        ], $auth)->assertCreated();
        $this->postJson('/api/v1/gifts/redeem', ['code' => $buy->json('data.code')], $auth)
            ->assertOk()->assertJsonPath('data.credited', 100);

        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 10]);
        $this->postJson('/api/v1/favorites/toggle', ['type' => 'product', 'id' => $product->id], $auth)
            ->assertOk()->assertJsonPath('data.saved', true);
        $this->getJson('/api/v1/favorites', $auth)->assertOk();
    }

    public function test_driver_accept_and_transition(): void
    {
        $driverAuth = ['Authorization' => 'Bearer '.$this->driver()['token']];
        $order = ParcelOrder::create(['sender_name' => 'A', 'receiver_name' => 'B', 'total' => 10]);

        // Pool shows the unassigned job.
        $jobs = $this->getJson('/api/v1/driver/jobs', $driverAuth)->assertOk();
        $this->assertCount(1, $jobs->json('data.pool'));

        $this->postJson('/api/v1/driver/jobs/accept', [
            'type' => 'parcel', 'id' => $order->id,
        ], $driverAuth)->assertOk();
        $this->assertEquals('accepted', $order->fresh()->status);

        // Double accept refused.
        Auth::forgetGuards();
        $other = Driver::create(['name' => 'D2', 'phone' => '03111', 'status' => 'active']);
        $otherAuth = ['Authorization' => 'Bearer '.$this->driver('03111')['token']];
        $this->postJson('/api/v1/driver/jobs/accept', [
            'type' => 'parcel', 'id' => $order->id,
        ], $otherAuth)->assertStatus(422);

        Auth::forgetGuards();
        $this->postJson('/api/v1/driver/jobs/transition', [
            'type' => 'parcel', 'id' => $order->id, 'to' => 'shipped',
        ], $driverAuth)->assertOk();
        $this->assertEquals('shipped', $order->fresh()->status);
    }

    public function test_vendor_order_transition_scoped(): void
    {
        $owner = Owner::create(['name' => 'Ali', 'phone' => '03002', 'status' => 'active']);
        $store = Store::create(['name' => 'Shop', 'slug' => 'shop', 'status' => 'active', 'owner_id' => $owner->id]);
        $mine = Order::create([
            'customer_name' => 'C', 'payment_method' => 'cod',
            'subtotal' => 10, 'total' => 10, 'vendor_id' => $store->id,
        ]);
        $foreign = Order::create([
            'customer_name' => 'C', 'payment_method' => 'cod', 'subtotal' => 5, 'total' => 5,
        ]);

        $req = $this->postJson('/api/v1/work/auth/otp/request', ['phone' => '03002', 'role' => 'vendor']);
        $token = $this->postJson('/api/v1/work/auth/otp/verify', [
            'phone' => '03002', 'role' => 'vendor', 'code' => $req->json('data.debug_code'),
        ])->json('data.token');
        $auth = ['Authorization' => 'Bearer '.$token];

        $this->postJson("/api/v1/vendor/orders/{$mine->id}/transition", ['to' => 'accepted'], $auth)
            ->assertOk();
        $this->assertEquals('accepted', $mine->fresh()->status);

        // Foreign store's order → 404.
        $this->postJson("/api/v1/vendor/orders/{$foreign->id}/transition", ['to' => 'accepted'], $auth)
            ->assertStatus(404);

        // Illegal jump refused.
        $this->postJson("/api/v1/vendor/orders/{$mine->id}/transition", ['to' => 'completed'], $auth)
            ->assertStatus(422);
    }
}
