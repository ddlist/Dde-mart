<?php

namespace Tests\Feature\Admin;

use App\Models\Driver;
use App\Models\Owner;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — driver bank + fleet tests (original): bank fields save,
 * fleet owner linkage, scope filters on the list.
 */
class DriverPeopleTest extends TestCase
{
    use RefreshDatabase;

    protected function staff(): User
    {
        $role = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_super' => true],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_bank_and_fleet_save(): void
    {
        $admin = $this->staff();
        $owner = Owner::create(['name' => 'Fleet', 'phone' => '03077']);

        $this->actingAs($admin)->post(route('admin.drivers.store'), [
            'kind' => 'ride',
            'name' => 'Rider',
            'phone' => '03078',
            'bank_name' => 'Bank',
            'bank_account' => '999',
            'owner_id' => $owner->id,
        ])->assertRedirect();

        $driver = Driver::where('phone', '03078')->firstOrFail();
        $this->assertEquals('Bank', $driver->bank_name);
        $this->assertEquals($owner->id, $driver->owner_id);

        $this->actingAs($admin)
            ->get(route('admin.drivers.index', ['scope' => 'fleet']))
            ->assertOk()
            ->assertSee('Rider');
    }
}
