<?php

namespace Tests\Feature\Admin;

use App\Models\Owner;
use App\Models\Referral;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\WalletEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — owners, wallet ledger, referrals tests (original).
 */
class OwnerTest extends TestCase
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

    public function test_guest_and_unprivileged_are_blocked(): void
    {
        $this->get(route('admin.owners.index'))->assertRedirect(route('login'));

        $role = Role::create(['name' => 'Limited', 'slug' => 'limited']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('admin.owners.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.wallet.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.referrals.index'))->assertForbidden();
    }

    public function test_owner_crud_transitions_and_store_block(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.owners.store'), [
            'name' => 'Store Owner', 'phone' => '03009998877', 'email' => 'owner@example.com',
        ])->assertRedirect();

        $owner = Owner::where('name', 'Store Owner')->firstOrFail();
        $this->assertEquals('pending', $owner->status);

        $this->actingAs($admin)->post(route('admin.owners.transition', $owner), ['to' => 'active'])
            ->assertSessionHas('success');

        Store::create(['name' => 'S', 'slug' => 's', 'owner_id' => $owner->id]);

        $this->actingAs($admin)->delete(route('admin.owners.destroy', $owner))
            ->assertSessionHas('error');
        $this->assertNotNull($owner->fresh());
    }

    public function test_wallet_and_referral_pages_render(): void
    {
        $admin = $this->superAdmin();
        WalletEntry::create([
            'owner_type' => 'driver', 'owner_ref' => 'du001', 'amount' => 10,
            'kind' => 'order', 'status' => 'success', 'note' => 'Delivery payout',
        ]);
        Referral::create(['code' => 'ABC123', 'referrer_ref' => 'du001']);

        $this->actingAs($admin)->get(route('admin.wallet.index'))
            ->assertOk()->assertSee('Delivery payout');
        $this->actingAs($admin)->get(route('admin.referrals.index'))
            ->assertOk()->assertSee('ABC123');
    }

    public function test_importer_imports_owners_wallets_referrals(): void
    {
        $this->artisan('ddemart:import', [
            '--file' => base_path('tests/Fixtures/legacy-sample.json'),
        ])->assertSuccessful();

        // Only the vendor-role user imports.
        $owner = Owner::where('legacy_id', 'vu001')->firstOrFail();
        $this->assertEquals('Store Owner', $owner->name);
        $this->assertEquals('active', $owner->status);
        $this->assertEquals(1, Owner::count());

        $wallet = WalletEntry::where('legacy_id', 'w001')->firstOrFail();
        $this->assertEquals('driver', $wallet->owner_type);
        $this->assertEquals(10.0, (float) $wallet->amount);

        $this->assertEquals('ABC123', Referral::where('legacy_id', 'r001')->firstOrFail()->code);
    }
}
