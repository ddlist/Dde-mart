<?php

namespace Tests\Feature\Admin;

use App\Models\Driver;
use App\Models\ParcelOrder;
use App\Models\RentalOrder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — parcel/rental manual assignment tests (original).
 */
class TransportAssignTest extends TestCase
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

    public function test_parcel_assign_notifies_driver(): void
    {
        $admin = $this->staff();
        $driver = Driver::create(['name' => 'R', 'phone' => '03091']);
        $order = ParcelOrder::create([
            'sender_name' => 'S', 'sender_address' => 'A',
            'receiver_name' => 'R', 'receiver_address' => 'B',
            'total' => 120, 'status' => 'accepted',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.parcel-orders.assign', $order), ['driver_id' => $driver->id])
            ->assertRedirect(route('admin.parcel-orders.show', $order));

        $this->assertEquals($driver->id, $order->fresh()->driver_id);
    }

    public function test_rental_assign_notifies_driver(): void
    {
        $admin = $this->staff();
        $driver = Driver::create(['name' => 'R', 'phone' => '03092']);
        $order = RentalOrder::create([
            'customer_name' => 'C', 'source' => 'A',
            'total' => 2000, 'status' => 'accepted',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.rental-orders.assign', $order), ['driver_id' => $driver->id])
            ->assertRedirect(route('admin.rental-orders.show', $order));

        $this->assertEquals($driver->id, $order->fresh()->driver_id);
    }
}
