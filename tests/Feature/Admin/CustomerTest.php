<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\WalletEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — customer management tests (original): list filter,
 * create/edit, detail with wallet top-up, guarded delete.
 */
class CustomerTest extends TestCase
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

    public function test_list_filters_and_create(): void
    {
        $admin = $this->staff();
        Customer::create(['name' => 'A', 'phone' => '03001', 'is_active' => true]);
        Customer::create(['name' => 'B', 'phone' => '03002', 'is_active' => false]);

        $this->actingAs($admin)->get(route('admin.customers.index'))
            ->assertOk();
        $this->actingAs($admin)->get(route('admin.customers.index', ['search' => '03001']))
            ->assertOk();
        $this->actingAs($admin)->get(route('admin.customers.index', ['status' => 'active']))
            ->assertOk();

        $this->actingAs($admin)->post(route('admin.customers.store'), [
            'name' => 'C', 'phone' => '03003', 'password' => 'secret123',
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', ['phone' => '03003']);

        // Duplicate phone rejected.
        $this->actingAs($admin)->post(route('admin.customers.store'), [
            'name' => 'Clone', 'phone' => '03003', 'password' => 'secret123',
        ])->assertSessionHasErrors('phone');
    }

    public function test_detail_topup_and_guarded_delete(): void
    {
        $admin = $this->staff();
        $customer = Customer::create(['name' => 'A', 'phone' => '03001']);

        $this->actingAs($admin)->get(route('admin.customers.show', $customer))
            ->assertOk();

        $this->actingAs($admin)->post(route('admin.customers.topup', $customer), [
            'amount' => 250, 'note' => 'Goodwill',
        ])->assertRedirect(route('admin.customers.show', $customer));

        $this->assertEquals(250, WalletEntry::where('owner_ref', '03001')->sum('amount'));

        // Orders on record block deletion.
        Order::create([
            'type' => 'food', 'customer_id' => $customer->id,
            'customer_name' => 'A', 'total' => 100, 'status' => 'placed',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.customers.destroy', $customer))
            ->assertRedirect(route('admin.customers.index'));

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }
}
