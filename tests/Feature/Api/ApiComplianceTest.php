<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\PushToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/*
 * DDE-Mart API — store-compliance tests (original): account deletion and
 * the mobile launch gate (app-config).
 */
class ApiComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_config_is_public_with_sane_defaults(): void
    {
        $config = $this->getJson('/api/v1/app-config')->assertOk()->json('data');

        $this->assertFalse($config['maintenance']);
        $this->assertEquals('1.0.0', $config['min_versions']['customer']);
        $this->assertEquals('1.0.0', $config['min_versions']['driver']);
        $this->assertEquals('1.0.0', $config['min_versions']['vendor']);

        \App\Models\Setting::set('apps_maintenance', '1');
        \App\Models\Setting::set('min_app_customer', '2.3.0');

        $updated = $this->getJson('/api/v1/app-config')->assertOk()->json('data');
        $this->assertTrue($updated['maintenance']);
        $this->assertEquals('2.3.0', $updated['min_versions']['customer']);
    }

    public function test_password_account_deletes_with_confirmation(): void
    {
        $customer = Customer::create(['name' => 'Sara', 'phone' => '03001234567', 'password' => 'secret123']);
        $token = $customer->createToken('app', ['customer'])->plainTextToken;
        $auth = ['Authorization' => 'Bearer '.$token];

        PushToken::create([
            'tokenable_type' => Customer::class, 'tokenable_id' => $customer->id,
            'token' => 'fcm-1', 'platform' => 'android',
        ]);

        // Wrong password refuses; nothing changes.
        $this->deleteJson('/api/v1/me', ['password' => 'nope'], $auth)->assertStatus(422);
        $this->assertEquals('03001234567', $customer->fresh()->phone);

        $this->deleteJson('/api/v1/me', ['password' => 'secret123'], $auth)
            ->assertOk()->assertJsonPath('data.deleted', true);

        $fresh = $customer->fresh();
        $this->assertFalse((bool) $fresh->is_active);
        $this->assertStringStartsWith('deleted:', $fresh->phone);
        $this->assertNull($fresh->email);
        $this->assertEquals(0, PushToken::count());

        // The old token is revoked (guard reset: sanctum memoizes per test).
        Auth::forgetGuards();
        $this->getJson('/api/v1/me', $auth)->assertStatus(401);
    }

    public function test_passwordless_account_deletes_freely(): void
    {
        Auth::forgetGuards();

        $customer = Customer::create(['name' => 'OTP', 'phone' => '03007654321']);
        $token = $customer->createToken('app', ['customer'])->plainTextToken;

        $this->deleteJson('/api/v1/me', [], ['Authorization' => 'Bearer '.$token])
            ->assertOk()->assertJsonPath('data.deleted', true);

        $this->assertFalse((bool) $customer->fresh()->is_active);

        Auth::forgetGuards();
        $this->deleteJson('/api/v1/me')->assertStatus(401);
    }
}
