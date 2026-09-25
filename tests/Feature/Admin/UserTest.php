<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — staff users + profile tests (D5, original).
 */
class UserTest extends TestCase
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

    public function test_user_without_grant_gets_forbidden(): void
    {
        $role = Role::create(['name' => 'Limited', 'slug' => 'limited']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_index_supports_search_and_role_filter(): void
    {
        $admin = $this->superAdmin();
        $role = Role::create(['name' => 'Staff', 'slug' => 'staff']);
        User::factory()->create(['name' => 'Zara Ahmed', 'email' => 'zara@example.com', 'role_id' => $role->id]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Zara']));

        $response->assertOk();
        $response->assertSee('Zara Ahmed');

        $filtered = $this->actingAs($admin)->get(route('admin.users.index', ['role' => $role->id]));
        $filtered->assertOk()->assertSee('Zara Ahmed');
    }

    public function test_create_user_with_role(): void
    {
        $admin = $this->superAdmin();
        $role = Role::create(['name' => 'Staff', 'slug' => 'staff']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Staffer',
            'email' => 'staff@dde-mart.local',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role_id' => $role->id,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'staff@dde-mart.local', 'role_id' => $role->id]);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $admin = $this->superAdmin();
        $role = Role::create(['name' => 'Staff', 'slug' => 'staff']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Clone',
            'email' => $admin->email,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role_id' => $role->id,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_cannot_delete_self(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $response->assertSessionHas('error');
        $this->assertNotNull($admin->fresh());
    }

    public function test_cannot_delete_last_super_admin(): void
    {
        $admin = $this->superAdmin();

        // A non-super HR manager with delete rights tries to remove the only super-admin.
        $hrRole = Role::create(['name' => 'HR', 'slug' => 'hr']);
        $hrRole->syncAbilities(['users.delete']);
        $hr = User::factory()->create(['role_id' => $hrRole->id]);

        $response = $this->actingAs($hr)->delete(route('admin.users.destroy', $admin));

        $response->assertSessionHas('error');
        $this->assertNotNull($admin->fresh());

        // With two super-admins, removing one is fine.
        $second = $this->superAdmin();
        $this->actingAs($hr)->delete(route('admin.users.destroy', $second))
            ->assertSessionHas('success');
        $this->assertNull($second->fresh());
    }

    public function test_cannot_change_own_role(): void
    {
        $admin = $this->superAdmin();
        $other = Role::create(['name' => 'Staff', 'slug' => 'staff']);

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role_id' => $other->id,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertTrue($admin->fresh()->isSuperAdmin());
    }

    public function test_profile_update_and_password_change(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('My profile');

        $this->actingAs($admin)->put(route('admin.profile.update'), [
            'name' => 'Renamed Admin',
            'email' => $admin->email,
        ])->assertRedirect(route('admin.profile.edit'));

        $this->assertEquals('Renamed Admin', $admin->fresh()->name);

        // Wrong current password → rejected.
        $this->actingAs($admin)->put(route('admin.profile.update'), [
            'name' => 'Renamed Admin',
            'email' => $admin->email,
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
            'current_password' => 'wrong',
        ])->assertSessionHasErrors('current_password');

        // Right current password → changed.
        $this->actingAs($admin)->put(route('admin.profile.update'), [
            'name' => 'Renamed Admin',
            'email' => $admin->email,
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
            'current_password' => 'password',
        ])->assertSessionHas('success');

        $this->assertTrue($this->app['hash']->check('newsecret123', $admin->fresh()->password));
    }
}
