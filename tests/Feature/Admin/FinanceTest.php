<?php

namespace Tests\Feature\Admin;

use App\Models\Advertisement;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\PayoutRequest;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — finance + promotions tests (D8, original).
 */
class FinanceTest extends TestCase
{
    use RefreshDatabase;

    protected function superAdmin(): User
    {
        $role = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_super' => true],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_coupon_discount_math(): void
    {
        $percent = Coupon::create([
            'code' => 'BIG20', 'discount_type' => 'percentage', 'discount_value' => 20,
            'max_discount' => 50,
        ]);

        $this->assertEquals(20.0, $percent->calculateDiscount(100)); // 20% of 100
        $this->assertEquals(50.0, $percent->calculateDiscount(1000)); // capped at 50

        $fixed = Coupon::create(['code' => 'FLAT5', 'discount_type' => 'fixed', 'discount_value' => 5]);

        $this->assertEquals(5.0, $fixed->calculateDiscount(100));
        $this->assertEquals(3.0, $fixed->calculateDiscount(3)); // never exceeds subtotal

        $expired = Coupon::create([
            'code' => 'OLD', 'discount_type' => 'fixed', 'discount_value' => 5,
            'expires_at' => now()->subDay(),
        ]);

        $this->assertEquals(0.0, $expired->calculateDiscount(100));
        $this->assertFalse($expired->isUsable(100));
    }

    public function test_coupon_code_is_uppercased_and_unique(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.coupons.store'), [
            'code' => 'big20', 'discount_type' => 'percentage', 'discount_value' => 20, 'scope' => 'all',
        ])->assertRedirect(route('admin.coupons.index'));

        $this->assertEquals('BIG20', Coupon::firstOrFail()->code);

        $this->actingAs($admin)->post(route('admin.coupons.store'), [
            'code' => 'BIG20', 'discount_type' => 'fixed', 'discount_value' => 5, 'scope' => 'all',
        ])->assertSessionHasErrors('code');
    }

    public function test_tax_calculation(): void
    {
        $percent = Tax::create(['country' => 'Pakistan', 'title' => 'GST', 'type' => 'percentage', 'value' => 16]);
        $fixed = Tax::create(['country' => 'Pakistan', 'title' => 'Service', 'type' => 'fixed', 'value' => 50]);

        $this->assertEquals(16.0, $percent->calculate(100));
        $this->assertEquals(50.0, $fixed->calculate(100));
    }

    public function test_exactly_one_default_currency(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.currencies.store'), [
            'code' => 'pkr', 'name' => 'Pakistani Rupee', 'symbol' => '₨',
        ])->assertRedirect(route('admin.currencies.index'));

        // First currency auto-becomes default.
        $this->assertTrue(Currency::where('code', 'PKR')->firstOrFail()->is_default);

        $this->actingAs($admin)->post(route('admin.currencies.store'), [
            'code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'is_default' => '1',
        ]);

        $this->assertTrue(Currency::where('code', 'USD')->firstOrFail()->is_default);
        $this->assertFalse(Currency::where('code', 'PKR')->firstOrFail()->is_default);
        $this->assertEquals(1, Currency::where('is_default', true)->count());

        // Default cannot be deleted.
        $this->actingAs($admin)->delete(route('admin.currencies.destroy', Currency::where('code', 'USD')->firstOrFail()))
            ->assertSessionHas('error');
    }

    public function test_currency_format(): void
    {
        $left = Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$']);
        $right = Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'symbol_at_right' => true]);

        $this->assertEquals('$1,234.50', $left->format(1234.5));
        $this->assertEquals('1,234.50 €', $right->format(1234.5));
    }

    public function test_subscription_plan_with_features(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.plans.store'), [
            'name' => 'Bronze', 'type' => 'free', 'price' => 0, 'validity_days' => 30,
            'item_limit' => -1, 'order_limit' => 10,
            'features' => ['chat', 'reports'],
        ])->assertRedirect(route('admin.plans.index'));

        $plan = SubscriptionPlan::where('name', 'Bronze')->firstOrFail();
        $this->assertNull($plan->item_limit); // -1 → unlimited
        $this->assertEquals(10, $plan->order_limit);
        $this->assertEquals(['chat', 'reports'], $plan->features);
    }

    public function test_ad_status_machine(): void
    {
        $admin = $this->superAdmin();
        $ad = Advertisement::create(['title' => 'Launch Sale']);

        $this->assertEquals('pending', $ad->status);

        $this->actingAs($admin)->post(route('admin.ads.transition', $ad), ['to' => 'active'])
            ->assertSessionHas('error'); // pending → active is illegal (via approved)
        $this->assertEquals('pending', $ad->fresh()->status);

        $this->actingAs($admin)->post(route('admin.ads.transition', $ad), ['to' => 'approved'])
            ->assertSessionHas('success');
        $this->assertEquals('approved', $ad->fresh()->status);
    }

    public function test_payout_workflow_and_money_trail(): void
    {
        $admin = $this->superAdmin();
        $payout = PayoutRequest::create([
            'requester_type' => 'vendor', 'requester_id' => 7,
            'requester_name' => 'Fresh Foods', 'amount' => 1500, 'method' => 'bank',
            'method_details' => ['account' => 'PK00TEST123'],
        ]);

        // pending → paid directly is illegal.
        $this->actingAs($admin)->post(route('admin.payouts.transition', $payout), ['to' => 'paid'])
            ->assertSessionHas('error');

        $this->actingAs($admin)->post(route('admin.payouts.transition', $payout), [
            'to' => 'approved', 'admin_note' => 'Verified sales',
        ])->assertSessionHas('success');

        $payout->refresh();
        $this->assertEquals('approved', $payout->status);
        $this->assertEquals($admin->id, $payout->handled_by);

        $this->actingAs($admin)->post(route('admin.payouts.transition', $payout), ['to' => 'paid'])
            ->assertSessionHas('success');

        $payout->refresh();
        $this->assertEquals('paid', $payout->status);
        $this->assertNotNull($payout->paid_at);

        // Terminal: no further moves; and no delete route exists.
        $this->actingAs($admin)->post(route('admin.payouts.transition', $payout), ['to' => 'pending'])
            ->assertSessionHas('error');
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.payouts.destroy'));
    }

    public function test_finance_gates_deny_unprivileged(): void
    {
        $role = Role::create(['name' => 'Limited', 'slug' => 'limited']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('admin.payouts.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.coupons.index'))->assertForbidden();
    }
}
