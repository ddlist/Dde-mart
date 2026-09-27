<?php

namespace Tests\Feature\Api;

use App\Models\Driver;
use App\Models\Order;
use App\Models\ParcelOrder;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/*
 * DDE-Mart API — driver job detail tests (original): scoped per-type
 * detail for assigned + pool jobs, 404 for foreign jobs and bad types.
 */
class ApiDriverJobsTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_job_show_food_assigned(): void
    {
        $store = Store::create(['name' => 'S', 'slug' => 's', 'status' => 'active']);
        $driver = Driver::create([
            'name' => 'R', 'phone' => '0300111001',
            'kind' => 'delivery', 'status' => 'active',
        ]);
        $order = Order::create([
            'type' => 'food', 'customer_name' => 'C', 'customer_phone' => '0300111',
            'address' => '12 Main St', 'total' => 500, 'status' => 'accepted',
            'vendor_id' => $store->id, 'driver_id' => $driver->id,
        ]);

        $auth = ['Authorization' => 'Bearer '.$this->driverToken($driver)];

        $this->getJson("/api/v1/driver/jobs/food/{$order->id}", $auth)
            ->assertOk()
            ->assertJsonPath('data.number', $order->number)
            ->assertJsonPath('data.customer_name', 'C')
            ->assertJsonPath('data.address', '12 Main St');
    }

    public function test_job_show_pool_parcel_visible(): void
    {
        $driver = Driver::create([
            'name' => 'R', 'phone' => '0300111002',
            'kind' => 'delivery', 'status' => 'active',
        ]);
        $parcel = ParcelOrder::create([
            'number' => 'P-1', 'sender_name' => 'S', 'sender_address' => 'A',
            'receiver_name' => 'R', 'receiver_address' => 'B',
            'total' => 120, 'status' => 'placed',
        ]);

        $auth = ['Authorization' => 'Bearer '.$this->driverToken($driver)];

        $this->getJson("/api/v1/driver/jobs/parcel/{$parcel->id}", $auth)
            ->assertOk()
            ->assertJsonPath('data.sender_name', 'S')
            ->assertJsonPath('data.receiver_address', 'B');
    }

    public function test_job_show_foreign_and_bad_type_404(): void
    {
        $mine = Driver::create([
            'name' => 'R', 'phone' => '0300111003',
            'kind' => 'delivery', 'status' => 'active',
        ]);
        $other = Driver::create([
            'name' => 'O', 'phone' => '0300111004',
            'kind' => 'delivery', 'status' => 'active',
        ]);
        $order = Order::create([
            'type' => 'food', 'customer_name' => 'C', 'total' => 100,
            'status' => 'accepted', 'driver_id' => $other->id,
        ]);

        $auth = ['Authorization' => 'Bearer '.$this->driverToken($mine)];
        Auth::forgetGuards();

        $this->getJson("/api/v1/driver/jobs/food/{$order->id}", $auth)->assertStatus(404);
        $this->getJson("/api/v1/driver/jobs/spaceship/{$order->id}", $auth)->assertStatus(404);
    }
}
