<?php

namespace Tests\Feature\Api;

use App\Models\Coupon;
use App\Models\Owner;
use App\Models\Store;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/*
 * DDE-Mart API — vendor extras tests (original): coupon CRUD scoped to own
 * stores, subscription view, vendor chat inbox + reply.
 */
class ApiVendorExtrasTest extends TestCase
{
    use RefreshDatabase;

    protected function vendorToken(string $phone = '03005'): array
    {
        $owner = Owner::create(['name' => 'V', 'phone' => $phone, 'status' => 'active']);
        $req = $this->postJson('/api/v1/work/auth/otp/request', ['phone' => $phone, 'role' => 'vendor']);
        $token = $this->postJson('/api/v1/work/auth/otp/verify', [
            'phone' => $phone, 'role' => 'vendor', 'code' => $req->json('data.debug_code'),
        ])->json('data.token');

        return [$owner, ['Authorization' => 'Bearer '.$token]];
    }

    public function test_coupon_crud_scoped(): void
    {
        [$owner, $auth] = $this->vendorToken();
        $store = Store::create(['name' => 'Mine', 'slug' => 'mine', 'status' => 'active', 'owner_id' => $owner->id]);
        $rival = Owner::create(['name' => 'R', 'phone' => '03006', 'status' => 'active']);
        $foreign = Store::create(['name' => 'Theirs', 'slug' => 'theirs', 'status' => 'active', 'owner_id' => $rival->id]);

        // Foreign store rejected; code uppercased + bound to own store.
        $this->postJson('/api/v1/vendor/coupons', [
            'store_id' => $foreign->id, 'code' => 'hijack',
            'discount_type' => 'fixed', 'discount_value' => 10,
        ], $auth)->assertStatus(404);

        $create = $this->postJson('/api/v1/vendor/coupons', [
            'store_id' => $store->id, 'code' => 'flat5',
            'discount_type' => 'fixed', 'discount_value' => 5,
            'min_order' => 100, 'usage_limit' => 50,
        ], $auth)->assertCreated()->assertJsonPath('data.code', 'FLAT5');

        $coupon = Coupon::findOrFail($create->json('data.id'));
        $this->assertEquals($store->id, $coupon->vendor_id);
        $this->assertEquals('food', $coupon->scope);

        // Duplicate code (any scope) rejected.
        $this->postJson('/api/v1/vendor/coupons', [
            'store_id' => $store->id, 'code' => 'FLAT5',
            'discount_type' => 'fixed', 'discount_value' => 5,
        ], $auth)->assertStatus(422);

        $list = $this->getJson('/api/v1/vendor/coupons', $auth)->assertOk();
        $list->assertJsonCount(1, 'data');

        $this->putJson("/api/v1/vendor/coupons/{$coupon->id}", [
            'discount_value' => 8, 'is_active' => false,
        ], $auth)->assertOk();
        $coupon->refresh();
        $this->assertEquals(8.0, (float) $coupon->discount_value);
        $this->assertFalse((bool) $coupon->is_active);

        // Rival vendor sees nothing and cannot touch it.
        Auth::forgetGuards();
        [, $rivalAuth] = $this->vendorToken('03006');
        $this->getJson('/api/v1/vendor/coupons', $rivalAuth)->assertOk()->assertJsonCount(0, 'data');
        $this->putJson("/api/v1/vendor/coupons/{$coupon->id}", ['discount_value' => 1], $rivalAuth)
            ->assertStatus(404);
    }

    public function test_subscription_view(): void
    {
        [$owner, $auth] = $this->vendorToken();
        $plan = SubscriptionPlan::create([
            'name' => 'Pro', 'price' => 999, 'validity_days' => 30, 'is_active' => true,
        ]);

        $view = $this->getJson('/api/v1/vendor/subscription', $auth)->assertOk();
        $view->assertJsonCount(1, 'data.plans');
        $view->assertJsonPath('data.mine', null);

        \App\Models\PlanSubscription::create([
            'subscriber_type' => 'owner', 'subscriber_id' => $owner->id,
            'plan_id' => $plan->id, 'amount' => 999, 'status' => 'active',
            'starts_at' => now(), 'ends_at' => now()->addDays(30),
        ]);

        $this->getJson('/api/v1/vendor/subscription', $auth)->assertOk()
            ->assertJsonPath('data.mine.plan.name', 'Pro')
            ->assertJsonPath('data.mine.expired', false);
    }
}
