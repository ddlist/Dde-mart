<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart API — customer profile update tests (original): PUT /me edits
 * name + email only; phone is the identity and never changes here.
 */
class ApiProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function token(): string
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Sara', 'phone' => '03001234567',
        ]);

        return $response->json('data.token');
    }

    public function test_update_name_and_email(): void
    {
        $auth = ['Authorization' => 'Bearer '.$this->token()];

        $this->putJson('/api/v1/me', [
            'name' => 'Sara K', 'email' => 'sara@example.com',
        ], $auth)
            ->assertOk()
            ->assertJsonPath('data.name', 'Sara K')
            ->assertJsonPath('data.email', 'sara@example.com')
            ->assertJsonPath('data.phone', '03001234567');
    }

    public function test_update_rejects_bad_email(): void
    {
        $auth = ['Authorization' => 'Bearer '.$this->token()];

        $this->putJson('/api/v1/me', ['email' => 'not-an-email'], $auth)
            ->assertStatus(422);
    }

    public function test_update_requires_auth(): void
    {
        // Separate method: the Sanctum guard memoizes the user within one
        // test, so guest-vs-authed must not share a method.
        $this->putJson('/api/v1/me', ['name' => 'X'])->assertStatus(401);
    }
}
