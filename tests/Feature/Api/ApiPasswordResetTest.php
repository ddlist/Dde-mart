<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/*
 * DDE-Mart API — password reset tests (original). OTP-verified reset for
 * customers; unknown numbers stay hidden behind 404.
 */
class ApiPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_flow_signs_in_with_new_password(): void
    {
        Customer::create(['name' => 'Sara', 'phone' => '03001234567', 'password' => 'oldsecret123']);

        $this->postJson('/api/v1/auth/password/request', ['phone' => '03009999999'])
            ->assertStatus(404);

        $req = $this->postJson('/api/v1/auth/password/request', ['phone' => '03001234567'])
            ->assertOk();
        $code = $req->json('data.debug_code');
        $this->assertNotNull($code);

        // Wrong code fails; right code resets and returns a token.
        $this->postJson('/api/v1/auth/password/reset', [
            'phone' => '03001234567', 'code' => '000000',
            'password' => 'newsecret123', 'password_confirmation' => 'newsecret123',
        ])->assertStatus(401);

        $reset = $this->postJson('/api/v1/auth/password/reset', [
            'phone' => '03001234567', 'code' => $code,
            'password' => 'newsecret123', 'password_confirmation' => 'newsecret123',
        ])->assertOk();
        $this->assertNotEmpty($reset->json('data.token'));
        $this->assertTrue(Hash::check('newsecret123', Customer::firstOrFail()->password));

        // Consumed codes cannot be reused.
        $this->postJson('/api/v1/auth/password/reset', [
            'phone' => '03001234567', 'code' => $code,
            'password' => 'another123', 'password_confirmation' => 'another123',
        ])->assertStatus(401);

        // New password works at login.
        $this->postJson('/api/v1/auth/login', [
            'phone' => '03001234567', 'password' => 'newsecret123',
        ])->assertOk();
    }
}
