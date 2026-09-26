<?php

namespace Tests\Feature\Api;

use App\Models\Driver;
use App\Models\Notification;
use App\Models\Order;
use App\Models\PushToken;
use App\Models\Setting;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/*
 * DDE-Mart dispatch engine tests (original): offer → accept/timeout →
 * re-offer → auto-cancel, plus driver location pings and food job surfaces.
 */
class DispatchOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('dispatch_auto', '1');
    }

    protected function store(): Store
    {
        return Store::create([
            'name' => 'S', 'slug' => 's', 'status' => 'active',
            'latitude' => 24.8600, 'longitude' => 67.0000,
        ]);
    }

    protected function driver(array $overrides = []): Driver
    {
        return Driver::create(array_merge([
            'name' => 'R', 'phone' => '0300'.random_int(1000000, 9999999),
            'kind' => 'delivery', 'status' => 'active', 'is_online' => true,
            'latitude' => 24.8610, 'longitude' => 67.0010,
            'location_updated_at' => now(),
        ], $overrides));
    }

    protected function tokened(Driver $driver): Driver
    {
        PushToken::create([
            'tokenable_type' => Driver::class, 'tokenable_id' => $driver->id,
            'token' => 'tok-'.$driver->id, 'platform' => 'android',
        ]);

        return $driver;
    }

    protected function order(Store $store): Order
    {
        return Order::create([
            'type' => 'food', 'customer_name' => 'C', 'customer_phone' => '0300111',
            'total' => 500, 'status' => 'accepted', 'vendor_id' => $store->id,
        ]);
    }

    protected function driverToken(Driver $driver): string
    {
        $req = $this->postJson('/api/v1/work/auth/otp/request', [
            'phone' => $driver->phone, 'role' => 'driver',
        ]);

        return $this->postJson('/api/v1/work/auth/otp/verify', [
            'phone' => $driver->phone, 'role' => 'driver',
            'code' => $req->json('data.debug_code'),
        ])->json('data.token');
    }

    public function test_offers_nearest_eligible_driver(): void
    {
        $store = $this->store();
        $order = $this->order($store);

        $near = $this->tokened($this->driver());
        $far = $this->tokened($this->driver(['latitude' => 25.5000, 'longitude' => 67.5000]));
        $this->tokened($this->driver(['is_online' => false])); // offline, skipped
        $this->tokened($this->driver(['latitude' => null, 'longitude' => null])); // no fix, skipped
        $this->driver(); // no push token, skipped

        $this->artisan('dispatch:orders')->assertSuccessful();

        $order->refresh();
        $this->assertEquals($near->id, $order->driver_id);
        $this->assertNotNull($order->dispatch_expires_at);
        $this->assertTrue(Notification::where('audience', 'drivers')->exists());
    }

    public function test_timeout_releases_and_reoffers_next(): void
    {
        $store = $this->store();
        $order = $this->order($store);

        $first = $this->tokened($this->driver());
        $second = $this->tokened($this->driver(['latitude' => 24.8620, 'longitude' => 67.0020]));

        $this->artisan('dispatch:orders')->assertSuccessful();
        $this->assertEquals($first->id, $order->fresh()->driver_id);

        // Nobody accepts: expire the offer, next run re-offers the runner-up.
        $order->update(['dispatch_expires_at' => now()->subMinute()]);
        $this->artisan('dispatch:orders')->assertSuccessful();

        $order->refresh();
        $this->assertEquals($second->id, $order->driver_id);
        $this->assertEquals([$first->id], $order->rejectedDriverIds());
    }

    public function test_offered_driver_claims_and_expired_offer_rejected(): void
    {
        $store = $this->store();
        $order = $this->order($store);
        $driver = $this->tokened($this->driver());
        $auth = ['Authorization' => 'Bearer '.$this->driverToken($driver)];

        $this->artisan('dispatch:orders')->assertSuccessful();

        $this->postJson('/api/v1/driver/jobs/accept', [
            'type' => 'food', 'id' => $order->id,
        ], $auth)->assertOk()->assertJsonPath('data.status', 'accepted');

        $order->refresh();
        $this->assertNull($order->dispatch_expires_at);
        $this->assertStringContainsString(
            'Dispatch offer accepted',
            $order->history()->latest()->first()->note ?? ''
        );

        // Driver advances the job through the food machine.
        $this->postJson('/api/v1/driver/jobs/transition', [
            'type' => 'food', 'id' => $order->id, 'to' => 'shipped',
        ], $auth)->assertOk();
        $this->assertEquals('shipped', $order->fresh()->status);

        // Expired offer cannot be claimed.
        $order2 = $this->order($store);
        $order2->update(['driver_id' => $driver->id, 'dispatch_expires_at' => now()->subMinute()]);

        Auth::forgetGuards();
        $auth2 = ['Authorization' => 'Bearer '.$this->driverToken($driver)];
        $this->postJson('/api/v1/driver/jobs/accept', [
            'type' => 'food', 'id' => $order2->id,
        ], $auth2)->assertStatus(422);
    }

    public function test_pool_claim_works_when_dispatch_off(): void
    {
        Setting::set('dispatch_auto', '0');
        $store = $this->store();
        $order = $this->order($store);
        $driver = $this->driver();
        $auth = ['Authorization' => 'Bearer '.$this->driverToken($driver)];

        // Off mode: command ignores the order, driver claims from the pool.
        $this->artisan('dispatch:orders')->assertSuccessful();
        $this->assertNull($order->fresh()->driver_id);

        $jobs = $this->getJson('/api/v1/driver/jobs', $auth)->assertOk();
        $jobs->assertJsonFragment(['type' => 'food', 'id' => $order->id]);

        $this->postJson('/api/v1/driver/jobs/accept', [
            'type' => 'food', 'id' => $order->id,
        ], $auth)->assertOk();
        $this->assertEquals($driver->id, $order->fresh()->driver_id);
    }

    public function test_hopeless_order_auto_cancels(): void
    {
        $store = $this->store();
        $order = $this->order($store);
        // Backdate past the cancel window (query builder: Eloquent would touch it).
        Order::where('id', $order->id)->update(['updated_at' => now()->subHours(2)]);

        $this->artisan('dispatch:orders')->assertSuccessful();

        $this->assertEquals('cancelled', $order->fresh()->status);
        $this->assertStringContainsString(
            'Auto-cancelled',
            $order->history()->latest()->first()->note ?? ''
        );
    }

    public function test_location_ping_records_position(): void
    {
        $driver = $this->driver(['latitude' => null, 'longitude' => null]);
        $auth = ['Authorization' => 'Bearer '.$this->driverToken($driver)];

        $this->postJson('/api/v1/driver/location', [
            'latitude' => 24.8700, 'longitude' => 67.0100,
        ], $auth)->assertOk()->assertJsonPath('data.recorded', true);

        $driver->refresh();
        $this->assertEquals(24.8700, (float) $driver->latitude);
        $this->assertNotNull($driver->location_updated_at);

        $this->postJson('/api/v1/driver/location', [
            'latitude' => 200, 'longitude' => 67.0100,
        ], $auth)->assertStatus(422);

        Auth::forgetGuards();
        $this->postJson('/api/v1/driver/location', [
            'latitude' => 24.87, 'longitude' => 67.01,
        ])->assertStatus(401);
    }
}
