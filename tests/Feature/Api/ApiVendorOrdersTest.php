<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Owner;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/*
 * DDE-Mart API — vendor order detail + profile tests (original): scoped
 * order show (items + timeline) and owner profile update.
 */
class ApiVendorOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function workLogin(string $role, string $phone): string
    {
        $req = $this->postJson('/api/v1/work/auth/otp/request', [
            'phone' => $phone, 'role' => $role,
        ])->assertOk();

        $verify = $this->postJson('/api/v1/work/auth/otp/verify', [
            'phone' => $phone, 'role' => $role, 'code' => $req->json('data.debug_code'),
        ])->assertOk();

        return $verify->json('data.token');
    }

    protected function seedOrder(): array
    {
        $owner = Owner::create(['name' => 'Ali', 'phone' => '03002', 'status' => 'active']);
        $store = Store::create(['name' => 'Shop', 'slug' => 'shop', 'status' => 'active', 'owner_id' => $owner->id]);
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 10, 'vendor_id' => $store->id]);

        $auth = ['Authorization' => 'Bearer '.$this->workLogin('vendor', '03002')];

        $cust = $this->postJson('/api/v1/auth/register', ['name' => 'C', 'phone' => '03111'])
            ->json('data.token');
        $custAuth = ['Authorization' => 'Bearer '.$cust];
        Auth::forgetGuards();

        $this->postJson('/api/v1/checkout', [
            'items' => [['product_id' => $product->id]], 'payment_method' => 'cod',
        ], $custAuth)->assertCreated();
        Auth::forgetGuards();

        return [$auth, Order::firstOrFail(), $owner];
    }

    public function test_order_show_scoped_with_items_and_timeline(): void
    {
        [$auth, $order] = $this->seedOrder();

        $this->getJson("/api/v1/vendor/orders/{$order->id}", $auth)
            ->assertOk()
            ->assertJsonPath('data.number', $order->number)
            ->assertJsonPath('data.items.0.name', 'P')
            ->assertJsonPath('data.timeline.0.to', 'placed');
    }

    public function test_order_show_foreign_is_404(): void
    {
        [$auth, $order] = $this->seedOrder();

        $other = Owner::create(['name' => 'Zed', 'phone' => '03009', 'status' => 'active']);
        $otherAuth = ['Authorization' => 'Bearer '.$this->workLogin('vendor', '03009')];
        Auth::forgetGuards();

        $this->getJson("/api/v1/vendor/orders/{$order->id}", $otherAuth)->assertStatus(404);
    }

    public function test_profile_update_name(): void
    {
        [$auth, , $owner] = $this->seedOrder();

        $this->putJson('/api/v1/vendor/profile', ['name' => 'Ali K'], $auth)
            ->assertOk()
            ->assertJsonPath('data.name', 'Ali K');

        $this->assertEquals('Ali K', $owner->fresh()->name);
    }
}
