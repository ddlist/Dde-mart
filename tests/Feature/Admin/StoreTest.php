<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — stores tests (original).
 */
class StoreTest extends TestCase
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
        $this->get(route('admin.stores.index'))->assertRedirect(route('login'));

        $role = Role::create(['name' => 'Limited', 'slug' => 'limited']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('admin.stores.index'))->assertForbidden();
    }

    public function test_store_crud_with_auto_slug(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.stores.store'), [
            'name' => 'Fresh Foods',
            'owner_name' => 'Ali',
            'phone' => '03001234567',
            'commission_type' => 'percentage',
            'commission_value' => 10,
        ])->assertRedirect(route('admin.stores.index'));

        $store = Store::where('name', 'Fresh Foods')->firstOrFail();
        $this->assertEquals('fresh-foods', $store->slug);
        $this->assertEquals('pending', $store->status);
    }

    public function test_status_machine(): void
    {
        $admin = $this->superAdmin();
        $store = Store::create(['name' => 'S', 'slug' => 's']);

        // pending → suspended is illegal.
        $this->actingAs($admin)->post(route('admin.stores.transition', $store), ['to' => 'suspended'])
            ->assertSessionHas('error');
        $this->assertEquals('pending', $store->fresh()->status);

        $this->actingAs($admin)->post(route('admin.stores.transition', $store), ['to' => 'active'])
            ->assertSessionHas('success');
        $this->assertEquals('active', $store->fresh()->status);
    }

    public function test_delete_blocked_while_products_assigned(): void
    {
        $admin = $this->superAdmin();
        $store = Store::create(['name' => 'S', 'slug' => 's']);
        Product::create(['name' => 'P', 'slug' => 'p', 'price' => 5, 'vendor_id' => $store->id]);

        $response = $this->actingAs($admin)->delete(route('admin.stores.destroy', $store));

        $response->assertSessionHas('error');
        $this->assertNotNull($store->fresh());
    }

    public function test_importer_imports_zones_stores_and_backfills_products(): void
    {
        $this->artisan('ddemart:import', [
            '--file' => base_path('tests/Fixtures/legacy-sample.json'),
        ])->assertSuccessful();

        $zone = Zone::where('legacy_id', 'zn001')->firstOrFail();
        $this->assertEquals('Downtown', $zone->name);

        $store = Store::where('legacy_id', 'vd001')->firstOrFail();
        $this->assertEquals('Fresh Foods', $store->name);
        $this->assertEquals('active', $store->status); // known-good imports go live
        $this->assertEquals($zone->id, $store->zone_id);
        $this->assertEquals(10.0, (float) $store->commission_value);

        $product = Product::where('legacy_id', 'pr001')->firstOrFail();
        $this->assertEquals($store->id, $product->vendor_id);

        // Re-run stays idempotent.
        $this->artisan('ddemart:import', [
            '--file' => base_path('tests/Fixtures/legacy-sample.json'),
        ])->assertSuccessful();

        $this->assertEquals(1, Store::count());
    }
}
