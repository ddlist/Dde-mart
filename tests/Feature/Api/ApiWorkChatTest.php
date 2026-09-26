<?php

namespace Tests\Feature\Api;

use App\Models\ChatThread;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Owner;
use App\Models\ParcelOrder;
use App\Models\ParcelWeight;
use App\Models\Story;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/*
 * DDE-Mart API — workforce chat + tracking extras tests (original):
 * order_ref linking on customer send, vendor/driver inboxes + replies with
 * scoping, stories feed, driver position on tracking payloads.
 */
class ApiWorkChatTest extends TestCase
{
    use RefreshDatabase;

    protected function customerToken(): string
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Sara', 'phone' => '03001234567',
        ]);

        return $response->json('data.token');
    }

    protected function workToken(string $role, string $phone): string
    {
        $req = $this->postJson('/api/v1/work/auth/otp/request', ['phone' => $phone, 'role' => $role]);

        return $this->postJson('/api/v1/work/auth/otp/verify', [
            'phone' => $phone, 'role' => $role, 'code' => $req->json('data.debug_code'),
        ])->json('data.token');
    }

    public function test_order_ref_links_thread_to_vendor_and_driver(): void
    {
        $owner = Owner::create(['name' => 'V', 'phone' => '03005', 'status' => 'active']);
        $store = Store::create(['name' => 'S', 'slug' => 's', 'status' => 'active', 'owner_id' => $owner->id]);
        $driver = Driver::create(['name' => 'D', 'phone' => '03001', 'status' => 'active']);

        $order = Order::create([
            'customer_name' => 'Sara', 'customer_phone' => '03001234567',
            'total' => 100, 'status' => 'accepted',
            'vendor_id' => $store->id, 'driver_id' => $driver->id,
        ]);

        $customer = ['Authorization' => 'Bearer '.$this->customerToken()];
        $send = $this->postJson('/api/v1/chat/send', [
            'subject' => 'Where is my food?', 'message' => 'Hello?',
            'order_ref' => $order->number,
        ], $customer)->assertCreated();
        $threadId = $send->json('data.thread_id');

        $thread = ChatThread::findOrFail($threadId);
        $this->assertEquals($store->id, $thread->vendor_id);
        $this->assertEquals($driver->id, $thread->driver_id);

        // Vendor inbox sees it and can reply; customer sees the reply.
        Auth::forgetGuards();
        $vendor = ['Authorization' => 'Bearer '.$this->workToken('vendor', '03005')];
        $this->getJson('/api/v1/vendor/chat/threads', $vendor)->assertOk()->assertJsonCount(1, 'data');
        $this->postJson("/api/v1/vendor/chat/threads/{$threadId}/reply", [
            'message' => 'On its way!',
        ], $vendor)->assertCreated();

        Auth::forgetGuards(); // sanctum memoizes the user per test — reset on switch
        $show = $this->getJson("/api/v1/chat/threads/{$threadId}", $customer)->assertOk();
        $show->assertJsonPath('data.messages.1.body', 'On its way!');
        $show->assertJsonPath('data.messages.1.from_me', false);

        // Driver inbox sees the same thread and answers.
        Auth::forgetGuards();
        $drv = ['Authorization' => 'Bearer '.$this->workToken('driver', '03001')];
        $this->getJson('/api/v1/driver/chat/threads', $drv)->assertOk()->assertJsonCount(1, 'data');
        $this->postJson("/api/v1/driver/chat/threads/{$threadId}/reply", [
            'message' => 'Two minutes away.',
        ], $drv)->assertCreated();

        // Foreign driver sees nothing.
        Auth::forgetGuards();
        Driver::create(['name' => 'X', 'phone' => '03002', 'status' => 'active']);
        $other = ['Authorization' => 'Bearer '.$this->workToken('driver', '03002')];
        $this->getJson('/api/v1/driver/chat/threads', $other)->assertOk()->assertJsonCount(0, 'data');
        $this->postJson("/api/v1/driver/chat/threads/{$threadId}/reply", ['message' => 'Hi'], $other)
            ->assertStatus(404);
    }

    public function test_stories_feed_and_tracking_driver_card(): void
    {
        $store = Store::create(['name' => 'S', 'slug' => 's', 'status' => 'active']);
        Story::create(['store_id' => $store->id, 'video_url' => 'https://cdn.test/v.mp4', 'status' => 'active']);
        Story::create(['store_id' => $store->id, 'video_url' => 'https://cdn.test/old.mp4', 'status' => 'removed']);

        $feed = $this->getJson('/api/v1/stories')->assertOk();
        $feed->assertJsonCount(1, 'data');
        $feed->assertJsonPath('data.0.store.name', 'S');

        // Parcel tracking carries the driver card with live position.
        ParcelWeight::create(['title' => '5 KG', 'delivery_charge' => 50]);
        $driver = Driver::create([
            'name' => 'D', 'phone' => '03001', 'status' => 'active',
            'latitude' => 24.8700, 'longitude' => 67.0100, 'location_updated_at' => now(),
        ]);

        $customer = ['Authorization' => 'Bearer '.$this->customerToken()];
        $book = $this->postJson('/api/v1/parcel/book', [
            'sender_name' => 'Sara', 'sender_address' => 'A',
            'receiver_name' => 'Ali', 'receiver_phone' => '03002',
            'receiver_address' => 'B', 'weight_id' => 1, 'distance_km' => 4,
        ], $customer)->assertCreated();

        $parcel = ParcelOrder::firstOrFail();
        $this->assertNull($book->json('data.driver'));

        $parcel->update(['driver_id' => $driver->id]);
        $track = $this->getJson("/api/v1/parcel/orders/{$parcel->id}", $customer)->assertOk();
        $track->assertJsonPath('data.driver.name', 'D');
        $track->assertJsonPath('data.driver.latitude', 24.87);
        $track->assertJsonPath('data.driver.position_at', fn ($v) => $v !== null);
    }
}
