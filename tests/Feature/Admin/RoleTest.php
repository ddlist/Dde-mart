<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — role CRUD + permission enforcement tests (D2, original).
 */
class RoleTest extends TestCase
{
    use RefreshDatabase;

    protected function superAdmin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_super' => true]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    protected function limitedUser(array $abilities = []): User
    {
        $role = Role::create(['name' => 'Limited', 'slug' => 'limited']);
        $role->syncAbilities($abilities);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_guest_cannot_access_roles(): void
    {
        $this->get(route('admin.roles.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_grant_gets_forbidden(): void
    {
        $user = $this->limitedUser(['dashboard.view']);

        $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();
    }

    public function test_super_admin_can_create_role_with_abilities(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Store Manager',
            'abilities' => ['orders' => ['view', 'edit']],
        ]);

        $response->assertRedirect(route('admin.roles.index'));

        $role = Role::where('name', 'Store Manager')->firstOrFail();
        $this->assertEqualsCanonicalizing(['orders.view', 'orders.edit'], $role->abilityKeys());
    }

    public function test_update_syncs_abilities(): void
    {
        $admin = $this->superAdmin();
        $role = Role::create(['name' => 'Support', 'slug' => 'support']);
        $role->syncAbilities(['orders.view']);

        $this->actingAs($admin)->put(route('admin.roles.update', $role), [
            'name' => 'Support',
            'abilities' => ['orders' => ['edit']],
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertEquals(['orders.edit'], $role->fresh()->abilityKeys());
    }

    public function test_delete_is_blocked_while_users_attached(): void
    {
        $admin = $this->superAdmin();
        $role = Role::create(['name' => 'Staff', 'slug' => 'staff']);
        User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($admin)->delete(route('admin.roles.destroy', $role));

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('error');
        $this->assertNotNull($role->fresh()); // role AND user survive (legacy deleted them)
    }

    public function test_super_admin_role_is_protected(): void
    {
        $admin = $this->superAdmin();
        $super = Role::where('slug', 'super-admin')->firstOrFail();

        $this->actingAs($admin)->delete(route('admin.roles.destroy', $super))
            ->assertSessionHas('error');

        $this->assertNotNull($super->fresh());
    }

    public function test_unknown_ability_keys_are_ignored(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Sneaky',
            'abilities' => ['roles' => ['view', 'hack-the-planet']],
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertEquals(['roles.view'], Role::where('name', 'Sneaky')->firstOrFail()->abilityKeys());
    }
}
