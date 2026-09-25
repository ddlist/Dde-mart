<?php

namespace Tests\Feature\Admin;

use App\Models\ParcelCategory;
use App\Models\ParcelOrder;
use App\Models\ParcelWeight;
use App\Models\RentalOrder;
use App\Models\RentalPackage;
use App\Models\RentalVehicleType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — parcel + rental tests (original).
 */
class TransportTest extends TestCase
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

    public function test_gates_deny_unprivileged(): void
    {
        $role = Role::create(['name' => 'Limited', 'slug' => 'limited']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('admin.parcel-orders.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.rental-orders.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.parcel-categories.index'))->assertForbidden();
    }

    public function test_parcel_masters_crud(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.parcel-categories.store'), [
            'name' => 'Documents',
        ])->assertRedirect(route('admin.parcel-categories.index'));
        $this->assertEquals('documents', ParcelCategory::firstOrFail()->slug);

        $this->actingAs($admin)->post(route('admin.parcel-weights.store'), [
            'title' => '5 KG', 'delivery_charge' => 50,
        ])->assertRedirect(route('admin.parcel-weights.index'));
        $this->assertEquals(50.0, (float) ParcelWeight::firstOrFail()->delivery_charge);
    }

    public function test_parcel_transition_machine(): void
    {
        $admin = $this->superAdmin();
        $order = ParcelOrder::create(['sender_name' => 'A', 'receiver_name' => 'B', 'total' => 100]);

        $this->assertMatchesRegularExpression('/^P-DDE-\d{6}-\d{5}$/', $order->fresh()->number);

        // placed → completed is illegal.
        $this->actingAs($admin)->post(route('admin.parcel-orders.transition', $order), ['to' => 'completed'])
            ->assertSessionHas('error');

        $this->actingAs($admin)->post(route('admin.parcel-orders.transition', $order), ['to' => 'accepted'])
            ->assertSessionHas('success');
        $this->assertEquals('accepted', $order->fresh()->status);
        $this->assertCount(1, $order->fresh()->history);
    }

    public function test_rental_package_crud_and_order_flow(): void
    {
        $admin = $this->superAdmin();
        $type = RentalVehicleType::create(['name' => 'Sedan', 'slug' => 'sedan']);

        $this->actingAs($admin)->post(route('admin.rental-packages.store'), [
            'name' => '2 Hour | 40 KM', 'vehicle_type_id' => $type->id, 'base_fare' => 10,
        ])->assertRedirect(route('admin.rental-packages.index'));

        $order = RentalOrder::create(['customer_name' => 'Ali', 'total' => 20]);
        $this->assertMatchesRegularExpression('/^R-DDE-\d{6}-\d{5}$/', $order->fresh()->number);

        $this->actingAs($admin)->post(route('admin.rental-orders.transition', $order), ['to' => 'accepted'])
            ->assertSessionHas('success');

        // accepted → ongoing stamps start time.
        $this->actingAs($admin)->post(route('admin.rental-orders.transition', $order->fresh()), ['to' => 'ongoing'])
            ->assertSessionHas('success');
        $this->assertNotNull($order->fresh()->started_at);
    }

    public function test_importer_imports_transport(): void
    {
        $this->artisan('ddemart:import', [
            '--file' => base_path('tests/Fixtures/legacy-sample.json'),
        ])->assertSuccessful();

        $this->assertEquals('Documents', ParcelCategory::where('legacy_id', 'pc001')->firstOrFail()->name);

        $weight = ParcelWeight::where('legacy_id', 'pw001')->firstOrFail();
        $this->assertEquals(5.0, (float) $weight->max_kg); // parsed from "5 KG"

        $parcel = \App\Models\ParcelOrder::where('legacy_id', 'po001')->firstOrFail();
        $this->assertEquals('accepted', $parcel->status);
        $this->assertNotNull($parcel->driver_id); // du001 driver linked
        $this->assertCount(1, $parcel->history);

        $package = RentalPackage::where('legacy_id', 'rp001')->firstOrFail();
        $this->assertEquals(10.0, (float) $package->base_fare);

        $rental = RentalOrder::where('legacy_id', 'ro001')->firstOrFail();
        $this->assertEquals('completed', $rental->status);
        $this->assertEquals($package->id, $rental->package_id);
    }
}
