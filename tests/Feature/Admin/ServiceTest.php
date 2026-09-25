<?php

namespace Tests\Feature\Admin;

use App\Models\CabType;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\Destination;
use App\Models\Provider;
use App\Models\ProviderBooking;
use App\Models\ProviderCategory;
use App\Models\ProviderService;
use App\Models\ProviderWorker;
use App\Models\Ride;
use App\Models\Role;
use App\Models\TableBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — rides, on-demand, dine-in tests (original).
 */
class ServiceTest extends TestCase
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

        $this->actingAs($user)->get(route('admin.rides.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.fleet.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.providers.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.provider-bookings.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.dinein.index'))->assertForbidden();
    }

    public function test_fleet_masters_crud(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.fleet.makes.store'), ['name' => 'Audi'])
            ->assertRedirect(route('admin.fleet.index'));
        $make = CarMake::firstOrFail();

        $this->actingAs($admin)->post(route('admin.fleet.models.store'), [
            'name' => 'A4', 'car_make_id' => $make->id,
        ])->assertRedirect(route('admin.fleet.index'));
        $this->assertEquals('A4', CarModel::firstOrFail()->name);

        // Make with models is protected.
        $this->actingAs($admin)->delete(route('admin.fleet.makes.destroy', $make))
            ->assertSessionHas('error');

        $this->actingAs($admin)->post(route('admin.fleet.types.store'), [
            'name' => 'Hatchback', 'capacity' => 6, 'base_fare' => 50,
        ])->assertRedirect(route('admin.fleet.index'));
        $this->assertEquals('hatchback', CabType::firstOrFail()->slug);

        $this->actingAs($admin)->post(route('admin.fleet.destinations.store'), [
            'title' => 'Vadodara', 'latitude' => 22.3072, 'longitude' => 73.1812,
        ])->assertRedirect(route('admin.fleet.index'));
        $this->assertNotNull(Destination::first());
    }

    public function test_ride_flow_with_driver_assignment(): void
    {
        $admin = $this->superAdmin();
        $driver = \App\Models\Driver::create(['name' => 'D', 'kind' => 'ride', 'status' => 'active']);
        $ride = Ride::create(['customer_name' => 'Ali', 'total' => 150]);

        $this->assertMatchesRegularExpression('/^CAB-DDE-\d{6}-\d{5}$/', $ride->fresh()->number);

        $this->actingAs($admin)->post(route('admin.rides.assign', $ride), ['driver_id' => $driver->id])
            ->assertSessionHas('success');
        $this->assertEquals($driver->id, $ride->fresh()->driver_id);

        $this->actingAs($admin)->post(route('admin.rides.transition', $ride), ['to' => 'accepted'])
            ->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.rides.transition', $ride->fresh()), ['to' => 'ongoing'])
            ->assertSessionHas('success');
        $this->assertNotNull($ride->fresh()->started_at);
    }

    public function test_provider_nesting_and_booking_flow(): void
    {
        $admin = $this->superAdmin();
        $parent = ProviderCategory::create(['title' => 'Appliance']);
        $child = ProviderCategory::create(['title' => 'AC Repair', 'parent_id' => $parent->id, 'level' => 1]);

        // Parent with children is protected.
        $this->actingAs($admin)->delete(route('admin.provider-categories.destroy', $parent))
            ->assertSessionHas('error');

        $booking = ProviderBooking::create(['customer_name' => 'Sara', 'total' => 500]);

        $this->actingAs($admin)->post(route('admin.provider-bookings.transition', $booking), ['to' => 'completed'])
            ->assertSessionHas('error'); // must go through accepted

        $this->actingAs($admin)->post(route('admin.provider-bookings.transition', $booking), ['to' => 'accepted'])
            ->assertSessionHas('success');
        $this->assertEquals([$child->id], [$child->fresh()->id]); // sanity
    }

    public function test_dinein_booking_flow(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.dinein.store'), [
            'guest_name' => 'Sara K', 'guests' => 4, 'booked_for' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('admin.dinein.index'));

        $booking = TableBooking::firstOrFail();
        $this->assertEquals('pending', $booking->status);

        $this->actingAs($admin)->post(route('admin.dinein.transition', $booking), ['to' => 'confirmed'])
            ->assertSessionHas('success');
        $this->assertEquals('confirmed', $booking->fresh()->status);
    }

    public function test_importer_imports_services_batch(): void
    {
        $this->artisan('ddemart:import', [
            '--file' => base_path('tests/Fixtures/legacy-sample.json'),
        ])->assertSuccessful();

        $this->assertEquals('Audi', CarMake::where('legacy_id', 'mk001')->firstOrFail()->name);
        $this->assertEquals('A4', CarModel::where('legacy_id', 'md001')->firstOrFail()->name);

        $cab = CabType::where('legacy_id', 'ct001')->firstOrFail();
        $this->assertEquals(6, $cab->capacity);

        $this->assertNotNull(Destination::where('legacy_id', 'pd001')->first());

        $ride = Ride::where('legacy_id', 'rd001')->firstOrFail();
        $this->assertEquals('completed', $ride->status);
        $this->assertCount(1, $ride->history);

        $provider = Provider::where('legacy_id', 'pv001')->firstOrFail();
        $this->assertEquals('Fix It', $provider->name);

        $child = \App\Models\ProviderCategory::where('legacy_id', 'pcat002')->firstOrFail();
        $this->assertEquals(ProviderCategory::where('legacy_id', 'pcat001')->firstOrFail()->id, $child->parent_id);

        $service = ProviderService::where('legacy_id', 'ps001')->firstOrFail();
        $this->assertEquals($provider->id, $service->provider_id);
        $this->assertEquals($child->id, $service->category_id);

        $worker = ProviderWorker::where('legacy_id', 'pw001x')->firstOrFail();
        $this->assertEquals($provider->id, $worker->provider_id);

        $booking = ProviderBooking::where('legacy_id', 'pb001')->firstOrFail();
        $this->assertEquals('placed', $booking->status);

        $table = TableBooking::where('legacy_id', 'bt001')->firstOrFail();
        $this->assertEquals('confirmed', $table->status);
        $this->assertEquals(4, $table->guests);
    }
}
