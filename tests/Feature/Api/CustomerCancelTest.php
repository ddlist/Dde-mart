<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/*
 * DDE-Mart API — customer order cancel tests (original). Placed food orders
 * can be cancelled before the vendor accepts; anything later is locked.
 */
class CustomerCancelTest extends TestCase
{
    use RefreshDatabase;

    protected function customer(string $phone = '03001234567'): array
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Sara', 'phone' => $phone,
        ]);

        return ['token' => $response->json('data.token')];
    }

    public function test_placed_order_cancels(): void
    {
        $auth = ['Authorization' => 'Bearer '.$this->customer()['token']];
        $order = Order::create([
            'customer_name' => 'Sara', 'customer_phone' => '03001234567',
            'total' => 100, 'status' => 'placed',
        ]);

        $this->postJson("/api/v1/orders/{$order->id}/cancel", [], $auth)
            ->assertOk()->assertJsonPath('data.status', 'cancelled');

        $this->assertEquals('cancelled', $order->fresh()->status);
        $this->assertEquals(1, $order->fresh()->history()->count());
    }

    public function test_accepted_order_locked_and_foreign_invisible(): void
    {
        $auth = ['Authorization' => 'Bearer '.$this->customer()['token']];

        $accepted = Order::create([
            'customer_name' => 'Sara', 'customer_phone' => '03001234567',
            'total' => 100, 'status' => 'accepted',
        ]);
        $foreign = Order::create([
            'customer_name' => 'Ali', 'customer_phone' => '03009999999',
            'total' => 50, 'status' => 'placed',
        ]);

        $this->postJson("/api/v1/orders/{$accepted->id}/cancel", [], $auth)->assertStatus(422);
        $this->assertEquals('accepted', $accepted->fresh()->status);

        $this->postJson("/api/v1/orders/{$foreign->id}/cancel", [], $auth)->assertStatus(404);

        Auth::forgetGuards();
        $this->postJson("/api/v1/orders/{$accepted->id}/cancel", [])->assertStatus(401);
    }
}
