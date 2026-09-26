<?php

namespace Tests\Feature\Api;

use App\Models\Driver;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\Owner;
use App\Models\ParcelWeight;
use App\Models\Provider;
use App\Models\Ride;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/*
 * DDE-Mart API — workforce push tests (original): token registration per
 * role plus fan-out logs on customer bookings and admin ride assignment.
 * FCM is unconfigured in testing, so pushes log as failed — the row itself
 * proves the right audience was notified at the right moment.
 */
class ApiWorkforcePushTest extends TestCase
{
    use RefreshDatabase;

    protected function workToken(string $role, string $phone): string
    {
        $req = $this->postJson('/api/v1/work/auth/otp/request', ['phone' => $phone, 'role' => $role]);

        return $this->postJson('/api/v1/work/auth/otp/verify', [
            'phone' => $phone, 'role' => $role, 'code' => $req->json('data.debug_code'),
        ])->json('data.token');
    }

    protected function customerToken(): string
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Sara', 'phone' => '03001234567',
        ]);

        return $response->json('data.token');
    }

    public function test_each_role_registers_its_own_tokens(): void
    {
        Driver::create(['name' => 'Rider', 'phone' => '03001', 'status' => 'active']);
        Owner::create(['name' => 'Vendor', 'phone' => '03002', 'status' => 'active']);
        Provider::create(['name' => 'Fixer', 'phone' => '03003', 'status' => 'active']);

        $cases = [
            'driver' => ['03001', '/api/v1/driver/push-tokens'],
            'vendor' => ['03002', '/api/v1/vendor/push-tokens'],
            'provider' => ['03003', '/api/v1/provider/push-tokens'],
        ];

        foreach ($cases as $role => [$phone, $url]) {
            $auth = ['Authorization' => 'Bearer '.$this->workToken($role, $phone)];

            $this->postJson($url, ['token' => "tok-{$role}", 'platform' => 'android'], $auth)
                ->assertOk()->assertJsonPath('data.registered', true);

            // Duplicate token is idempotent, not a second row.
            $this->postJson($url, ['token' => "tok-{$role}"], $auth)->assertOk();

            $this->deleteJson($url, ['token' => "tok-{$role}"], $auth)
                ->assertOk()->assertJsonPath('data.unregistered', true);

            Auth::forgetGuards();
        }

        $this->assertDatabaseCount('push_tokens', 0);
        $this->postJson('/api/v1/driver/push-tokens', ['token' => 'x'])->assertStatus(401);
    }

    public function test_parcel_booking_notifies_drivers_with_template(): void
    {
        NotificationTemplate::create([
            'key' => 'workforce.parcel_placed',
            'audience' => 'drivers',
            'subject' => 'Parcel :order_number',
            'body' => 'Pickup for :customer worth :total',
            'is_active' => true,
        ]);

        $auth = ['Authorization' => 'Bearer '.$this->customerToken()];
        ParcelWeight::create(['title' => '5 KG', 'delivery_charge' => 50]);

        $this->postJson('/api/v1/parcel/book', [
            'sender_name' => 'Sara', 'sender_address' => 'A',
            'receiver_name' => 'Ali', 'receiver_phone' => '03002',
            'receiver_address' => 'B', 'weight_id' => 1, 'distance_km' => 4,
        ], $auth)->assertCreated();

        $log = Notification::where('audience', 'drivers')->firstOrFail();
        $this->assertStringStartsWith('Parcel P-DDE-', $log->subject);
        $this->assertStringContainsString('Sara', $log->message);
        $this->assertEquals('failed', $log->status); // no FCM creds in testing
    }

    public function test_service_booking_notifies_providers_and_ride_assign_notifies_drivers(): void
    {
        $provider = Provider::create(['name' => 'Fixer', 'phone' => '03003', 'status' => 'active']);
        $service = $provider->services()->create(['title' => 'Plumbing', 'price' => 300, 'is_active' => true]);

        $auth = ['Authorization' => 'Bearer '.$this->customerToken()];

        $this->postJson('/api/v1/services/book', [
            'service_id' => $service->id,
            'address' => 'House 1',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
        ], $auth)->assertCreated();

        $this->assertTrue(
            Notification::where('audience', 'providers')->where('subject', 'New service booking')->exists()
        );

        // Admin assigns a ride → drivers topic.
        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin', 'is_super' => true]);
        $admin = User::factory()->create(['role_id' => $role->id]);
        $driver = Driver::create(['name' => 'D', 'kind' => 'ride', 'status' => 'active']);
        $ride = Ride::create(['customer_name' => 'Ali', 'total' => 150]);

        $this->actingAs($admin)
            ->post(route('admin.rides.assign', $ride), ['driver_id' => $driver->id])
            ->assertSessionHas('success');

        $log = Notification::where('audience', 'drivers')
            ->where('subject', 'Ride assigned to you')->firstOrFail();
        $this->assertStringContainsString($ride->fresh()->number, $log->message);
    }
}
