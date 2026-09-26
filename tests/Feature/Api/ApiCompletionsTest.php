<?php

namespace Tests\Feature\Api;

use App\Models\ChatThread;
use App\Models\Provider;
use App\Models\ProviderBooking;
use App\Models\WalletEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/*
 * DDE-Mart API — completions tests (original): chat, top-up, provider bookings.
 */
class ApiCompletionsTest extends TestCase
{
    use RefreshDatabase;

    protected function customer(array $overrides = []): array
    {
        $response = $this->postJson('/api/v1/auth/register', array_merge([
            'name' => 'Sara', 'phone' => '03001234567',
        ], $overrides));

        return ['token' => $response->json('data.token')];
    }

    public function test_chat_send_and_list(): void
    {
        $auth = ['Authorization' => 'Bearer '.$this->customer()['token']];

        $send = $this->postJson('/api/v1/chat/send', [
            'subject' => 'Late order', 'message' => 'Where is it?',
        ], $auth)->assertCreated();

        $threadId = $send->json('data.thread_id');

        $this->getJson('/api/v1/chat/threads', $auth)->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/chat/threads/{$threadId}", $auth)
            ->assertOk()->assertJsonPath('data.messages.0.body', 'Where is it?');

        // Reply appends to the same thread.
        $this->postJson('/api/v1/chat/send', [
            'thread_id' => $threadId, 'message' => 'Hello?',
        ], $auth)->assertCreated();
        $this->assertEquals(2, ChatThread::find($threadId)->messages()->count());

        Auth::forgetGuards();
        $this->getJson('/api/v1/chat/threads')->assertStatus(401);
    }

    public function test_wallet_topup_unconfigured_reports_cleanly(): void
    {
        $auth = ['Authorization' => 'Bearer '.$this->customer()['token']];

        // No gateway creds in testing → honest 422, nothing created.
        $this->postJson('/api/v1/wallet/topup', [
            'amount' => 500, 'method' => 'paypal',
        ], $auth)->assertStatus(422);
        $this->assertEquals(0, WalletEntry::count());

        $this->postJson('/api/v1/wallet/topup', [
            'amount' => 500, 'method' => 'bank',
        ], $auth)->assertStatus(422);
    }

    public function test_provider_bookings_scoped(): void
    {
        $provider = Provider::create(['name' => 'Fix', 'phone' => '03003', 'status' => 'active']);
        $other = Provider::create(['name' => 'Other', 'phone' => '03004', 'status' => 'active']);

        ProviderBooking::create(['customer_name' => 'C', 'total' => 100, 'provider_id' => $provider->id]);
        ProviderBooking::create(['customer_name' => 'D', 'total' => 200, 'provider_id' => $other->id]);

        $req = $this->postJson('/api/v1/work/auth/otp/request', ['phone' => '03003', 'role' => 'provider']);
        $token = $this->postJson('/api/v1/work/auth/otp/verify', [
            'phone' => '03003', 'role' => 'provider', 'code' => $req->json('data.debug_code'),
        ])->json('data.token');
        $auth = ['Authorization' => 'Bearer '.$token];

        $list = $this->getJson('/api/v1/provider/bookings', $auth)->assertOk();
        $list->assertJsonCount(1, 'data');

        $bookingId = ProviderBooking::where('provider_id', $provider->id)->firstOrFail()->id;
        $this->getJson("/api/v1/provider/bookings/{$bookingId}", $auth)
            ->assertOk()->assertJsonPath('data.total', 100);

        $foreignId = ProviderBooking::where('provider_id', $other->id)->firstOrFail()->id;
        $this->getJson("/api/v1/provider/bookings/{$foreignId}", $auth)->assertStatus(404);
    }
}
