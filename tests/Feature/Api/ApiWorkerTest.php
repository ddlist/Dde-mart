<?php

namespace Tests\Feature\Api;

use App\Models\Provider;
use App\Models\ProviderBooking;
use App\Models\ProviderService;
use App\Models\ProviderWorker;
use App\Models\SosAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/*
 * DDE-Mart API — handyman (provider worker) tests (original): OTP auth with
 * active gate, assigned jobs, machine transitions, payouts, SOS, tokens.
 */
class ApiWorkerTest extends TestCase
{
    use RefreshDatabase;

    protected function provider(): Provider
    {
        return Provider::create(['name' => 'Fix', 'phone' => '03003', 'status' => 'active']);
    }

    protected function worker(Provider $provider, array $overrides = []): ProviderWorker
    {
        return $provider->workers()->create(array_merge([
            'name' => 'Ali', 'phone' => '0300777',
        ], $overrides));
    }

    protected function workerToken(string $phone): string
    {
        $req = $this->postJson('/api/v1/work/auth/otp/request', ['phone' => $phone, 'role' => 'worker']);

        return $this->postJson('/api/v1/work/auth/otp/verify', [
            'phone' => $phone, 'role' => 'worker', 'code' => $req->json('data.debug_code'),
        ])->json('data.token');
    }

    public function test_worker_auth_and_active_gate(): void
    {
        $provider = $this->provider();
        $this->worker($provider);

        $token = $this->workerToken('0300777');
        $me = $this->getJson('/api/v1/worker/me', ['Authorization' => 'Bearer '.$token])->assertOk();
        $me->assertJsonPath('data.name', 'Ali');

        // Deactivated worker cannot sign in.
        Auth::forgetGuards();
        $inactive = $this->worker($provider, ['phone' => '0300788', 'is_active' => false]);
        $req = $this->postJson('/api/v1/work/auth/otp/request', ['phone' => '0300788', 'role' => 'worker']);
        $this->postJson('/api/v1/work/auth/otp/verify', [
            'phone' => '0300788', 'role' => 'worker', 'code' => $req->json('data.debug_code'),
        ])->assertStatus(403);
        $this->assertNotNull($inactive->fresh());

        // Unknown number has no account.
        Auth::forgetGuards();
        $req = $this->postJson('/api/v1/work/auth/otp/request', ['phone' => '0300999', 'role' => 'worker']);
        $this->postJson('/api/v1/work/auth/otp/verify', [
            'phone' => '0300999', 'role' => 'worker', 'code' => $req->json('data.debug_code'),
        ])->assertStatus(404);
    }

    public function test_jobs_scoped_and_transition_flow(): void
    {
        $provider = $this->provider();
        $service = $provider->services()->create(['title' => 'Plumbing', 'price' => 300, 'is_active' => true]);
        $worker = $this->worker($provider);
        $other = $this->worker($provider, ['phone' => '0300788']);
        $auth = ['Authorization' => 'Bearer '.$this->workerToken('0300777')];

        $mine = ProviderBooking::create([
            'customer_name' => 'C', 'total' => 300, 'provider_id' => $provider->id,
            'service_id' => $service->id, 'worker_id' => $worker->id,
        ]);
        ProviderBooking::create([
            'customer_name' => 'D', 'total' => 200, 'provider_id' => $provider->id,
            'service_id' => $service->id, 'worker_id' => $other->id,
        ]);
        ProviderBooking::create([
            'customer_name' => 'E', 'total' => 100, 'provider_id' => $provider->id,
            'service_id' => $service->id,
        ]);

        $list = $this->getJson('/api/v1/worker/jobs', $auth)->assertOk();
        $list->assertJsonCount(1, 'data');
        $list->assertJsonPath('data.0.service.title', 'Plumbing');
        $list->assertJsonPath('data.0.provider.name', 'Fix');

        $this->getJson("/api/v1/worker/jobs/{$mine->id}", $auth)->assertOk()
            ->assertJsonPath('data.status', 'placed');

        // Illegal jump rejected; legal flow advances with history.
        $this->postJson("/api/v1/worker/jobs/{$mine->id}/transition", ['to' => 'completed'], $auth)
            ->assertStatus(422);

        foreach (['accepted', 'ongoing', 'completed'] as $to) {
            $this->postJson("/api/v1/worker/jobs/{$mine->id}/transition", ['to' => $to], $auth)
                ->assertOk()->assertJsonPath('data.status', $to);
        }
        $this->assertEquals(3, $mine->fresh()->history()->count());

        // Foreign booking invisible.
        $foreign = ProviderBooking::where('customer_name', 'D')->firstOrFail();
        $this->getJson("/api/v1/worker/jobs/{$foreign->id}", $auth)->assertStatus(404);
        $this->postJson("/api/v1/worker/jobs/{$foreign->id}/transition", ['to' => 'accepted'], $auth)
            ->assertStatus(404);

        Auth::forgetGuards();
        $this->getJson('/api/v1/worker/jobs')->assertStatus(401);
    }

    public function test_payouts_sos_and_tokens(): void
    {
        $provider = $this->provider();
        $this->worker($provider);
        $auth = ['Authorization' => 'Bearer '.$this->workerToken('0300777')];

        $req = $this->postJson('/api/v1/worker/payouts', [
            'amount' => 1500, 'method' => 'bank',
        ], $auth)->assertCreated()->assertJsonPath('data.status', 'pending');

        $list = $this->getJson('/api/v1/worker/payouts', $auth)->assertOk();
        $list->assertJsonCount(1, 'data');
        $this->assertEquals($req->json('data.id'), $list->json('data.0.id'));

        $this->postJson('/api/v1/worker/sos', [
            'latitude' => 24.86, 'longitude' => 67.0,
        ], $auth)->assertCreated();
        $this->assertEquals('worker', SosAlert::firstOrFail()->reporter_type);

        $this->postJson('/api/v1/worker/push-tokens', ['token' => 'tok-w'], $auth)
            ->assertOk()->assertJsonPath('data.registered', true);
        $this->deleteJson('/api/v1/worker/push-tokens', ['token' => 'tok-w'], $auth)->assertOk();

        Auth::forgetGuards();
        $this->postJson('/api/v1/worker/push-tokens', ['token' => 'x'])->assertStatus(401);
    }
}
