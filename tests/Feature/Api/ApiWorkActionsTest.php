<?php

namespace Tests\Feature\Api;

use App\Models\Owner;
use App\Models\Provider;
use App\Models\ProviderBooking;
use App\Models\Store;
use App\Models\TableBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/*
 * DDE-Mart API — workforce actions tests (original): provider booking
 * transitions and vendor dine-in inbox, both owner-scoped with machines.
 */
class ApiWorkActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function workToken(string $role, string $phone): string
    {
        $req = $this->postJson('/api/v1/work/auth/otp/request', ['phone' => $phone, 'role' => $role]);

        return $this->postJson('/api/v1/work/auth/otp/verify', [
            'phone' => $phone, 'role' => $role, 'code' => $req->json('data.debug_code'),
        ])->json('data.token');
    }

    public function test_provider_booking_transition_flow(): void
    {
        $provider = Provider::create(['name' => 'Fix', 'phone' => '03003', 'status' => 'active']);
        $other = Provider::create(['name' => 'Other', 'phone' => '03004', 'status' => 'active']);
        $auth = ['Authorization' => 'Bearer '.$this->workToken('provider', '03003')];

        $booking = ProviderBooking::create([
            'customer_name' => 'C', 'total' => 100, 'provider_id' => $provider->id,
        ]);
        $foreign = ProviderBooking::create([
            'customer_name' => 'D', 'total' => 200, 'provider_id' => $other->id,
        ]);

        // Illegal jump is rejected before any write.
        $this->postJson("/api/v1/provider/bookings/{$booking->id}/transition", ['to' => 'completed'], $auth)
            ->assertStatus(422);
        $this->assertEquals('placed', $booking->fresh()->status);

        foreach (['accepted', 'ongoing', 'completed'] as $to) {
            $this->postJson("/api/v1/provider/bookings/{$booking->id}/transition", ['to' => $to], $auth)
                ->assertOk()->assertJsonPath('data.status', $to);
        }

        $this->assertEquals('completed', $booking->fresh()->status);
        $this->assertEquals(3, $booking->fresh()->history()->count());

        // Terminal state is locked; foreign bookings are invisible.
        $this->postJson("/api/v1/provider/bookings/{$booking->id}/transition", ['to' => 'cancelled'], $auth)
            ->assertStatus(422);
        $this->postJson("/api/v1/provider/bookings/{$foreign->id}/transition", ['to' => 'accepted'], $auth)
            ->assertStatus(404);

        Auth::forgetGuards();
        $this->postJson("/api/v1/provider/bookings/{$booking->id}/transition", ['to' => 'accepted'])
            ->assertStatus(401);
    }

    public function test_vendor_dinein_inbox_scoped(): void
    {
        $owner = Owner::create(['name' => 'V', 'phone' => '03005', 'status' => 'active']);
        $rival = Owner::create(['name' => 'R', 'phone' => '03006', 'status' => 'active']);
        $store = Store::create(['name' => 'Mine', 'slug' => 'mine', 'status' => 'active', 'owner_id' => $owner->id]);
        $foreign = Store::create(['name' => 'Theirs', 'slug' => 'theirs', 'status' => 'active', 'owner_id' => $rival->id]);
        $auth = ['Authorization' => 'Bearer '.$this->workToken('vendor', '03005')];

        $mine = TableBooking::create([
            'store_id' => $store->id, 'guest_name' => 'G',
            'guests' => 2, 'booked_for' => now()->addDay(),
        ]);
        TableBooking::create([
            'store_id' => $foreign->id, 'guest_name' => 'H',
            'guests' => 4, 'booked_for' => now()->addDay(),
        ]);

        $list = $this->getJson('/api/v1/vendor/dinein', $auth)->assertOk();
        $list->assertJsonCount(1, 'data');
        $list->assertJsonPath('data.0.guest', 'G');

        $this->postJson("/api/v1/vendor/dinein/{$mine->id}/transition", ['to' => 'confirmed'], $auth)
            ->assertOk()->assertJsonPath('data.status', 'confirmed');
        $this->assertEquals('confirmed', $mine->fresh()->status);

        // Illegal jump + foreign booking.
        $this->postJson("/api/v1/vendor/dinein/{$mine->id}/transition", ['to' => 'completed'], $auth)
            ->assertStatus(422);

        $foreignBooking = TableBooking::where('store_id', $foreign->id)->firstOrFail();
        $this->postJson("/api/v1/vendor/dinein/{$foreignBooking->id}/transition", ['to' => 'confirmed'], $auth)
            ->assertStatus(404);

        Auth::forgetGuards();
        $this->getJson('/api/v1/vendor/dinein')->assertStatus(401);
    }
}
