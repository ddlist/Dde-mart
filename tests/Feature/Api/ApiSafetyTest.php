<?php

namespace Tests\Feature\Api;

use App\Models\Complaint;
use App\Models\Driver;
use App\Models\SosAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/*
 * DDE-Mart API — safety inbox tests (original): customer complaints and
 * customer/driver SOS filing.
 */
class ApiSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function customerToken(): string
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Sara', 'phone' => '03001234567',
        ]);

        return $response->json('data.token');
    }

    protected function driverToken(string $phone = '03009998877'): string
    {
        Driver::create(['name' => 'Rider', 'phone' => $phone, 'status' => 'active']);
        $req = $this->postJson('/api/v1/work/auth/otp/request', ['phone' => $phone, 'role' => 'driver']);

        return $this->postJson('/api/v1/work/auth/otp/verify', [
            'phone' => $phone, 'role' => 'driver', 'code' => $req->json('data.debug_code'),
        ])->json('data.token');
    }

    public function test_customer_complaint_file_and_list(): void
    {
        $auth = ['Authorization' => 'Bearer '.$this->customerToken()];

        $this->postJson('/api/v1/complaints', [
            'title' => 'Cold food', 'description' => 'Arrived cold after an hour.',
        ], $auth)->assertCreated()->assertJsonPath('data.status', 'open');

        $complaint = Complaint::firstOrFail();
        $this->assertEquals('Sara', $complaint->customer_name);

        $list = $this->getJson('/api/v1/complaints', $auth)->assertOk();
        $list->assertJsonCount(1, 'data');
        $list->assertJsonPath('data.0.title', 'Cold food');

        Auth::forgetGuards();
        $this->postJson('/api/v1/complaints', ['title' => 'X', 'description' => 'Y'])
            ->assertStatus(401);
    }

    public function test_sos_from_customer_and_driver(): void
    {
        $customerAuth = ['Authorization' => 'Bearer '.$this->customerToken()];
        $driverAuth = ['Authorization' => 'Bearer '.$this->driverToken()];

        $this->postJson('/api/v1/sos', [
            'latitude' => 24.86, 'longitude' => 67.0, 'order_ref' => 'DDE-1',
        ], $customerAuth)->assertCreated();

        Auth::forgetGuards(); // sanctum memoizes the user per test — reset on switch

        $this->postJson('/api/v1/driver/sos', [
            'latitude' => 24.87, 'longitude' => 67.01,
        ], $driverAuth)->assertCreated();

        $this->assertEquals(2, SosAlert::count());
        $this->assertEquals(
            ['customer', 'driver'],
            SosAlert::orderBy('id')->pluck('reporter_type')->all(),
        );
        $this->assertEquals('03009998877', SosAlert::where('reporter_type', 'driver')->firstOrFail()->reporter_ref);

        // Drivers cannot file customer complaints; guests cannot raise SOS.
        Auth::forgetGuards();

        $this->postJson('/api/v1/complaints', ['title' => 'X', 'description' => 'Y'], $driverAuth)
            ->assertStatus(403);
        Auth::forgetGuards();
        $this->postJson('/api/v1/sos', ['latitude' => 1, 'longitude' => 1])->assertStatus(401);
    }
}
